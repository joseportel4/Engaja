<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPdf\PdfBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->registerPdfMacros();
    }

    private function registerPdfMacros(): void
    {
        /*
         * Aplica o cabeçalho e rodapé institucionais via API nativa do PdfBuilder
         * (lida pelo BrowsershotDriver como headerTemplate/footerTemplate do Puppeteer)
         * e define as margens que reservam o espaço para eles.
         *
         * Uso:
         *   Portrait  → ->withAlfaEjaBrand()                   (margens padrão: 28 14 22 14)
         *   Landscape → ->withAlfaEjaBrand(35, 10, 25, 10)
         */
        PdfBuilder::macro('withAlfaEjaBrand', function (
            int $marginTop = 28,
            int $marginRight = 14,
            int $marginBottom = 22,
            int $marginLeft = 14,
        ): PdfBuilder {
            /** @var PdfBuilder $this */
            $this->margins($marginTop, $marginRight, $marginBottom, $marginLeft);

            $headerPath = public_path('images/Alfa-Eja Header.png');

            if (! file_exists($headerPath)) {
                return $this;
            }

            $headerB64 = 'data:image/png;base64,'.base64_encode(file_get_contents($headerPath));
            $footerPath = public_path('images/Alfa-Eja Footer.png');

            /*
             * Posicionamento absoluto garante que o header fique colado ao topo
             * e o footer centralizado, independente do espaçamento padrão do
             * contexto isolado em que o Puppeteer renderiza header e footer.
             */
            $reset = '<style>'
                .'*{margin:0;padding:0;box-sizing:border-box}'
                .'html,body{width:100%;height:100%;font-size:0;line-height:0;overflow:hidden}'
                .'</style>';

            $this->headerHtml(
                "<html><head>{$reset}</head>"
                ."<body style='position:relative'>"
                ."<img src='{$headerB64}' style='position:absolute;top:0;left:0;width:100%;display:block'>"
                .'</body></html>'
            );

            if (file_exists($footerPath)) {
                $footerB64 = 'data:image/png;base64,'.base64_encode(file_get_contents($footerPath));
                $this->footerHtml(
                    "<html><head>{$reset}</head>"
                    ."<body style='position:relative;width:100%;height:100%'>"
                    ."<img src='{$footerB64}' style='position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:61mm;display:block'>"
                    .'</body></html>'
                );
            }

            return $this;
        });
    }
}
