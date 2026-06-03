<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Spatie\LaravelPdf\PdfFactory;
use Tests\TestCase;

class PdfRemoteInstanceTest extends TestCase
{
    /**
     * Sem host remoto configurado (ex.: ambiente local), o builder default não
     * recebe customização de Browsershot — comportamento atual preservado.
     */
    public function test_nao_configura_browsershot_remoto_sem_host(): void
    {
        config(['laravel-pdf.browsershot.remote_instance.host' => null]);
        PdfFactory::resetDefaultBuilder();

        (new AppServiceProvider($this->app))->boot();

        $this->assertNull(PdfFactory::defaultBuilder()->getCustomizeBrowsershotCallback());
    }

    /**
     * Com host remoto definido (produção), o builder default passa a aplicar a
     * customização de Browsershot, herdada por todos os Pdf::view().
     */
    public function test_configura_browsershot_remoto_com_host(): void
    {
        config([
            'laravel-pdf.browsershot.remote_instance.host' => 'browserless',
            'laravel-pdf.browsershot.remote_instance.port' => 3000,
        ]);
        PdfFactory::resetDefaultBuilder();

        (new AppServiceProvider($this->app))->boot();

        $this->assertNotNull(PdfFactory::defaultBuilder()->getCustomizeBrowsershotCallback());
    }
}
