<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Core\app\Services\UfService;
use Tests\TestCase;

class UfServiceTest extends TestCase
{
    private UfService $ufService;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set('services.uf.history_enabled', false);
        config()->set('services.bcentral.token', 'test-token');
        config()->set('services.mindicador.timeout', 5);
        config()->set('services.bcentral.timeout', 10);

        $this->ufService = $this->app->make(UfService::class);
    }

    private function fakeBcentralResponse(string $value, string $indexDateString = '15-05-2024'): array
    {
        return [
            'Codigo' => 0,
            'Descripcion' => 'Success',
            'Series' => [
                'seriesId' => 'F073.UFF.PRE.Z.D',
                'Obs' => [
                    [
                        'indexDateString' => $indexDateString,
                        'value' => $value,
                        'statusCode' => 'OK',
                    ],
                ],
            ],
            'SeriesInfos' => [],
        ];
    }

    private function fakeBcentralEmptyResponse(): array
    {
        return [
            'Codigo' => 0,
            'Series' => ['Obs' => []],
        ];
    }

    public function test_uses_mindicador_when_it_responds_within_the_time_limit(): void
    {
        Http::fake([
            'mindicador.cl/*' => Http::response(['serie' => [['valor' => 37342.66]]]),
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('99999.99')),
        ]);

        $response = $this->ufService->getUfByDate('15-05-2024');

        $this->assertTrue($response->success);
        $this->assertSame(37342.66, $response->data);
        $this->assertSame(200, $response->code);

        Http::assertSentCount(1);
    }

    public function test_falls_back_to_banco_central_when_mindicador_times_out(): void
    {
        Http::fake([
            'mindicador.cl/*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 5001 milliseconds');
            },
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('37342.66')),
        ]);

        $response = $this->ufService->getUfByDate('15-05-2024');

        $this->assertTrue($response->success);
        $this->assertSame(37342.66, $response->data);
        $this->assertSame(200, $response->code);
        $this->assertSame('UF para el 2024-05-15 (Banco Central)', $response->message);

        Http::assertSentCount(1);

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'si3.bcentral.cl/SieteRestWS/SieteRestWS.ashx')
                && str_contains($url, 'function=GetSeries')
                && str_contains($url, 'timeseries=F073.UFF.PRE.Z.D')
                && str_contains($url, 'firstdate=2024-05-15')
                && str_contains($url, 'lastdate=2024-05-15')
                && str_contains($url, 'token=test-token');
        });
    }

    public function test_falls_back_to_banco_central_when_mindicador_is_unreachable(): void
    {
        Http::fake([
            'mindicador.cl/*' => function () {
                throw new ConnectionException('cURL error 52: Empty reply from server');
            },
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('37342.66')),
        ]);

        $response = $this->ufService->getUfByDate('15-05-2024');

        $this->assertTrue($response->success);
        $this->assertSame(37342.66, $response->data);
        $this->assertSame(200, $response->code);
    }

    public function test_returns_timeout_error_when_both_sources_time_out(): void
    {
        Http::fake([
            '*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $response = $this->ufService->getUfByDate('15-05-2024');

        $this->assertFalse($response->success);
        $this->assertSame(504, $response->code);
    }

    public function test_falls_back_to_banco_central_when_mindicador_has_no_data_for_the_date(): void
    {
        Http::fake([
            'mindicador.cl/*' => Http::response(['serie' => []]),
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('1.00')),
        ]);

        $response = $this->ufService->getUfByDate('15-05-2024');

        $this->assertTrue($response->success);
        $this->assertSame(1.00, $response->data);
        $this->assertSame(200, $response->code);
    }

    public function test_uses_banco_central_when_mindicador_returns_a_stale_observation(): void
    {
        Http::fake([
            'mindicador.cl/*' => Http::response([
                'serie' => [
                    ['fecha' => '2026-07-09T04:00:00.000Z', 'valor' => 40844.79],
                ],
            ]),
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('40847.51', '16-07-2026')),
        ]);

        $result = $this->ufService->getUfDashboard('2026-07-16');

        $this->assertTrue($result['success']);
        $this->assertSame(40847.51, $result['valor']);
        $this->assertSame('2026-07-16', $result['fecha']);
        $this->assertSame('2026-07-16', $result['fecha_valor']);
        $this->assertSame('banco_central', $result['fuente']);
        $this->assertFalse($result['ultima_publicada']);
    }

    public function test_returns_different_values_for_consecutive_business_days(): void
    {
        Http::fake([
            'mindicador.cl/*' => function ($request) {
                return match (true) {
                    str_contains($request->url(), '16-07-2026') => Http::response([
                        'serie' => [
                            ['fecha' => '2026-07-16T04:00:00.000Z', 'valor' => 40847.51],
                        ],
                    ]),
                    str_contains($request->url(), '17-07-2026') => Http::response([
                        'serie' => [
                            ['fecha' => '2026-07-17T04:00:00.000Z', 'valor' => 40850.23],
                        ],
                    ]),
                    default => Http::response(['serie' => []]),
                };
            },
        ]);

        $first = $this->ufService->getUfDashboard('2026-07-16');
        $second = $this->ufService->getUfDashboard('2026-07-17');

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertSame(40847.51, $first['valor']);
        $this->assertSame(40850.23, $second['valor']);
        $this->assertNotSame($first['valor'], $second['valor']);
    }

    public function test_caches_each_date_independently(): void
    {
        Http::fake([
            'mindicador.cl/*' => function ($request) {
                return match (true) {
                    str_contains($request->url(), '16-07-2026') => Http::response([
                        'serie' => [
                            ['fecha' => '2026-07-16T04:00:00.000Z', 'valor' => 40847.51],
                        ],
                    ]),
                    str_contains($request->url(), '17-07-2026') => Http::response([
                        'serie' => [
                            ['fecha' => '2026-07-17T04:00:00.000Z', 'valor' => 40850.23],
                        ],
                    ]),
                    default => Http::response(['serie' => []]),
                };
            },
        ]);

        $first = $this->ufService->getUfDashboard('2026-07-16');
        $firstAgain = $this->ufService->getUfDashboard('2026-07-16');
        $secondDate = $this->ufService->getUfDashboard('2026-07-17');

        $this->assertSame($first['valor'], $firstAgain['valor']);
        $this->assertSame(40847.51, $first['valor']);
        $this->assertSame(40850.23, $secondDate['valor']);

        // Dos consultas a la misma fecha = 1 request. Otra fecha = request nuevo.
        Http::assertSentCount(2);
    }

    public function test_uses_last_published_value_for_a_weekend(): void
    {
        Http::fake([
            'mindicador.cl/*' => Http::response(['serie' => []]),
            'si3.bcentral.cl/*' => function ($request) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

                if (($query['firstdate'] ?? null) === '2026-07-18'
                    && ($query['lastdate'] ?? null) === '2026-07-18') {
                    return Http::response($this->fakeBcentralEmptyResponse());
                }

                return Http::response($this->fakeBcentralResponse('40847.51', '17-07-2026'));
            },
        ]);

        $result = $this->ufService->getUfDashboard('2026-07-18');

        $this->assertTrue($result['success']);
        $this->assertSame(40847.51, $result['valor']);
        $this->assertSame('2026-07-18', $result['fecha']);
        $this->assertSame('2026-07-17', $result['fecha_valor']);
        $this->assertTrue($result['ultima_publicada']);
    }

    public function test_does_not_repeat_a_stale_value_when_the_gap_exceeds_the_lookback(): void
    {
        Http::fake([
            'mindicador.cl/*' => Http::response([
                'serie' => [
                    ['fecha' => '2026-07-09T04:00:00.000Z', 'valor' => 40844.79],
                ],
            ]),
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralEmptyResponse()),
        ]);

        $result = $this->ufService->getUfDashboard('2026-08-04');

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('valor', $result);
        $this->assertSame('UF_NO_DISPONIBLE', $result['error']['cod']);
    }

    public function test_does_not_reuse_a_previous_business_day_value(): void
    {
        // Jueves 16-07-2026. Ambas fuentes devuelven el valor del miércoles 15.
        // Al ser día hábil, no debe repetirse el valor.
        Http::fake([
            'mindicador.cl/*' => Http::response([
                'serie' => [
                    ['fecha' => '2026-07-15T04:00:00.000Z', 'valor' => 40844.79],
                ],
            ]),
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('40844.79', '15-07-2026')),
        ]);

        $result = $this->ufService->getUfDashboard('2026-07-16');

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('valor', $result);
    }

    public function test_uses_last_published_value_for_a_configured_holiday(): void
    {
        config()->set('services.uf.holidays', ['2026-09-18']);

        Http::fake([
            'mindicador.cl/*' => Http::response(['serie' => []]),
            'si3.bcentral.cl/*' => function ($request) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

                if (($query['firstdate'] ?? null) === '2026-09-18'
                    && ($query['lastdate'] ?? null) === '2026-09-18') {
                    return Http::response($this->fakeBcentralEmptyResponse());
                }

                return Http::response($this->fakeBcentralResponse('40967.24', '17-09-2026'));
            },
        ]);

        $result = $this->ufService->getUfDashboard('2026-09-18');

        $this->assertTrue($result['success']);
        $this->assertSame(40967.24, $result['valor']);
        $this->assertSame('2026-09-17', $result['fecha_valor']);
        $this->assertTrue($result['ultima_publicada']);
    }

    public function test_banco_central_empty_obs_returns_404(): void
    {
        Http::fake([
            'mindicador.cl/*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
            'si3.bcentral.cl/*' => Http::response([
                'Codigo' => 0,
                'Series' => ['Obs' => []],
            ]),
        ]);

        $response = $this->ufService->getUfByDate('15-05-2024');

        $this->assertFalse($response->success);
        $this->assertSame(404, $response->code);
    }

    public function test_uf_endpoint_returns_banco_central_value_when_mindicador_times_out(): void
    {
        Http::fake([
            'mindicador.cl/*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('37342.66')),
        ]);

        $prefix = config('api.gateway_prefix');

        $response = $this->withHeaders(['X-Internal-Key' => env('TRUSTED_INTERNAL_API_KEY')])
            ->get("{$prefix}/core/v1/uf?date=2024-05-15");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'valor' => 37342.66,
                'fecha' => '2024-05-15',
            ]);

        $this->assertStringContainsString(
            'no-store',
            (string) $response->baseResponse->headers->get('Cache-Control')
        );
    }
}
