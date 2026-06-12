<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Mockery;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\PdfFactory;
use Tests\TestCase;

class PdfRemoteInstanceTest extends TestCase
{
    /**
     * Sem host remoto configurado (ex.: ambiente local), o builder default não
     * recebe customização de Browsershot — comportamento local preservado.
     */
    public function test_nao_configura_browsershot_remoto_sem_host(): void
    {
        config(['laravel-pdf.browsershot.remote_instance.host' => null]);
        PdfFactory::resetDefaultBuilder();

        (new AppServiceProvider($this->app))->boot();

        $this->assertNull(PdfFactory::defaultBuilder()->getCustomizeBrowsershotCallback());
    }

    /**
     * Com host remoto definido (produção), o builder default aponta para o
     * Chromium remoto e aplica o timeout amplo, herdado por todos os Pdf::view().
     */
    public function test_configura_remote_e_timeout_com_host(): void
    {
        config([
            'laravel-pdf.browsershot.remote_instance.host' => 'browserless',
            'laravel-pdf.browsershot.remote_instance.port' => 3000,
            'dashboard.pdf.timeout' => 120,
        ]);
        PdfFactory::resetDefaultBuilder();

        (new AppServiceProvider($this->app))->boot();

        $callback = PdfFactory::defaultBuilder()->getCustomizeBrowsershotCallback();
        $this->assertNotNull($callback);

        $browsershot = Mockery::mock(Browsershot::class);
        $browsershot->shouldReceive('setRemoteInstance')->once()->with('browserless', 3000)->andReturnSelf();
        $browsershot->shouldReceive('noSandbox')->once()->andReturnSelf();
        $browsershot->shouldReceive('timeout')->once()->with(120)->andReturnSelf();

        $callback($browsershot);
    }
}
