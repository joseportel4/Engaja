<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    @page { size: A4 landscape; margin: 0; }
    body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
    .page {
      position: relative;
      width: 100%;
      height: 100%;
      page-break-after: always;
      overflow: hidden;
    }
    .page:last-of-type {
      page-break-after: auto;
    }
    .bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .text-layer {
      position: absolute;
      inset: 12%;
      color: #111;
      font-size: 20px;
      line-height: 1.4;
      white-space: pre-wrap;
    }
  </style>
</head>
<body>
  @php
    $modelo = $certificado->modelo;
    $frenteFile = $modelo?->imagem_frente ? public_path('storage/'.$modelo->imagem_frente) : null;
    $versoFile  = $modelo?->imagem_verso ? public_path('storage/'.$modelo->imagem_verso) : null;

    $toBase64Reduced = function ($filePath, $maxWidth = 2600, $quality = 92) {
        if (! $filePath || ! file_exists($filePath)) {
            return null;
        }
        $info = getimagesize($filePath);
        if (! $info) {
            return null;
        }
        $mime = $info['mime'] ?? 'image/jpeg';
        $createFn = match ($mime) {
            'image/png'  => 'imagecreatefrompng',
            'image/gif'  => 'imagecreatefromgif',
            default      => 'imagecreatefromjpeg',
        };
        if (!function_exists($createFn)) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
            $data = base64_encode(file_get_contents($filePath));
            return "data:image/{$ext};base64,{$data}";
        }
        $src = @$createFn($filePath);
        if (!$src) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
            $data = base64_encode(file_get_contents($filePath));
            return "data:image/{$ext};base64,{$data}";
        }
        $origW = imagesx($src);
        $origH = imagesy($src);
        $scale = $origW > $maxWidth ? ($maxWidth / $origW) : 1;
        $newW = (int)($origW * $scale);
        $newH = (int)($origH * $scale);
        $dst = imagecreatetruecolor($newW, $newH);
        // white background for PNG/GIF transparency
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        ob_start();
        imagejpeg($dst, null, $quality);
        $data = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);
        return 'data:image/jpeg;base64,'.base64_encode($data);
    };

    $frenteUrl = $toBase64Reduced($frenteFile);
    $versoUrl  = $toBase64Reduced($versoFile);
  @endphp

  <div class="page">
    @if($frenteUrl)
      <img src="{{ $frenteUrl }}" class="bg" alt="Frente">
    @endif
    <div class="text-layer">{!! nl2br(e($certificado->texto_frente)) !!}</div>
  </div>

  @if($versoUrl || $certificado->texto_verso)
  <div class="page">
    @if($versoUrl)
      <img src="{{ $versoUrl }}" class="bg" alt="Verso">
    @endif
    <div class="text-layer">{!! nl2br(e($certificado->texto_verso)) !!}</div>
  </div>
  @endif
</body>
</html>
