<?php

namespace App\Mail;

use App\Models\Certificado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificadoEmitidoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $nome;
    public string $acao;
    public int $certificadoId;
    public ?string $logoData = null;

    public function __construct(string $nome, string $acao, int $certificadoId)
    {
        $this->nome = $nome;
        $this->acao = $acao;
        $this->certificadoId = $certificadoId;
    }

    public function build(): self
    {
        $bannerPath = public_path('images/ppt-banner.png');

        $certificado = Certificado::with('modelo')->findOrFail($this->certificadoId);

        $pdf = app('dompdf.wrapper');
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'dpi' => 72,
            'defaultMediaType' => 'print',
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadView('certificados.pdf', ['certificado' => $certificado]);

        $pdfContent = $pdf->output();

        return $this->subject('Seu Certificado do Engaja: '.$this->acao)
            ->view('emails.certificados.emitido')
            ->with(['bannerPath' => $bannerPath])
            ->attachData($pdfContent, 'Certificado_Engaja.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
