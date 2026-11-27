<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Core\app\Services\UfHistoryRepository;
use Modules\Core\app\Services\UfHistoryService;
use Modules\Core\app\Services\UfService;
use Tests\TestCase;

class UfHistoryServiceTest extends TestCase
{
    private UfHistoryRepository $repository;

    private UfHistoryService $history;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('local');

        config()->set('services.bcentral.token', 'test-token');
        config()->set('services.uf.history_enabled', true);
        config()->set('services.uf.history_path', 'uf/uf-history-test.json');

        $this->repository = $this->app->make(UfHistoryRepository::class);
        $this->history = $this->app->make(UfHistoryService::class);
    }

    private function fakeBcentral(array $observations): array
    {
        $obs = [];

        foreach ($observations as $date => $value) {
            $obs[] = [
                'indexDateString' => $date,
                'value' => (string) $value,
                'statusCode' => 'OK',
            ];
        }

        return [
            'Codigo' => 0,
            'Descripcion' => 'Success',
            'Series' => [
                'seriesId' => 'F073.UFF.PRE.Z.D',
                'Obs' => $obs,
            ],
            'SeriesInfos' => [],
        ];
    }

    public function test_initialize_creates_json_with_the_last_five_months(): void
    {
        Http::fake([
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentral([
                '27-06-2026' => 40844.79,
                '28-06-2026' => 40844.79,
                '27-11-2026' => 41419.22,
            ])),
        ]);

        $written = $this->history->initialize(5, '2026-11-27');

        $this->assertSame(3, $written);
        $this->assertTrue(Storage::disk('local')->exists('uf/uf-history-test.json'));

        $data = json_decode(Storage::disk('local')->get('uf/uf-history-test.json'), true);

        $this->assertSame('F073.UFF.PRE.Z.D', $data['series']);
        $this->assertSame('2026-06-27', $data['first_date']);
        $this->assertSame('2026-11-27', $data['last_date']);
        $this->assertSame(40844.79, $data['values']['2026-06-28']['valor']);
        $this->assertSame(41419.22, $data['values']['2026-11-27']['valor']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'firstdate=2026-06-27')
                && str_contains($request->url(), 'lastdate=2026-11-27');
        });
    }

    public function test_resolve_uses_file_and_extends_forward_when_needed(): void
    {
        $this->repository->merge([
            ['fecha' => '2026-11-26', 'valor' => 41391.69],
        ]);

        Http::fake([
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentral([
                '27-11-2026' => 41419.22,
            ])),
        ]);

        /** @var UfService $ufService */
        $ufService = $this->app->make(UfService::class);

        $first = $ufService->getUfDashboard('2026-11-27');

        $this->assertTrue($first['success']);
        $this->assertSame(41419.22, $first['valor']);
        $this->assertSame('archivo', $first['origen']);
        $this->assertSame('banco_central', $first['fuente']);

        // El archivo se extendió y ahora la misma fecha no vuelve a consultar.
        $second = $ufService->getUfDashboard('2026-11-27');

        $this->assertSame($first['valor'], $second['valor']);
        $this->assertSame('archivo', $second['origen']);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'firstdate=2026-11-27')
                && str_contains($request->url(), 'lastdate=2026-11-27');
        });

        $this->assertSame('2026-11-27', $this->repository->lastDate());
    }

    public function test_existing_file_hit_does_not_call_any_api(): void
    {
        $this->repository->merge([
            ['fecha' => '2026-07-16', 'valor' => 40844.79],
        ]);

        /** @var UfService $ufService */
        $ufService = $this->app->make(UfService::class);

        $result = $ufService->getUfDashboard('2026-07-16');

        $this->assertTrue($result['success']);
        $this->assertSame(40844.79, $result['valor']);
        $this->assertSame('archivo', $result['origen']);
        $this->assertSame('2026-07-16', $result['fecha_valor']);

        Http::assertNothingSent();
    }

    public function test_resolve_extends_backwards_for_older_dates(): void
    {
        $this->repository->merge([
            ['fecha' => '2026-07-10', 'valor' => 40844.79],
        ]);

        Http::fake([
            'si3.bcentral.cl/*' => Http::response($this->fakeBcentral([
                '09-07-2026' => 40844.79,
            ])),
        ]);

        /** @var UfService $ufService */
        $ufService = $this->app->make(UfService::class);

        $result = $ufService->getUfDashboard('2026-07-09');

        $this->assertTrue($result['success']);
        $this->assertSame(40844.79, $result['valor']);
        $this->assertSame('archivo', $result['origen']);
        $this->assertSame('2026-07-09', $this->repository->firstDate());
    }
}
