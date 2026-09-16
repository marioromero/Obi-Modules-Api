<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Core\app\Services\UfService;
use Tests\TestCase;

class UfServiceTest extends TestCase
{
    private UfService $ufService;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.bcentral.token', 'test-token');
        config()->set('services.mindicador.timeout', 5);
        config()->set('services.bcentral.timeout', 10);

        $this->ufService = $this->app->make(UfService::class);
    }

    private function fakeBcentralResponse(string $value): array
    {
        return [
            'Codigo' => 0,
            'Descripcion' => 'Success',
            'Series' => [
                'seriesId' => 'F073.UFF.PRE.Z.D',
                'Obs' => [
                    [
                        'indexDateString' => '15-05-2024',
                        'value' => $value,
                        'statusCode' => 'OK',
                    ],
                ],
            ],
            'SeriesInfos' => [],
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

    public function test_does_not_fall_back_when_mindicador_has_no_data_for_the_date(): void
    {
        Http::fake([
            'mindicador.cl/*' => Http::response(['serie' => []]),
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentralResponse('1.00')),
        ]);

        $response = $this->ufService->getUfByDate('15-05-2024');

        $this->assertFalse($response->success);
        $this->assertSame(404, $response->code);

        Http::assertSentCount(1);
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

        $response = $this->withHeaders(['X-Internal-Key' => env('TRUSTED_INTERNAL_API_KEY')])
            ->get('api/core/v1/uf?date=15-05-2024');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'code' => 200,
                'data' => 37342.66,
            ]);
    }
}
