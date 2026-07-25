<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LonaPhotoProcessor
{
    private const MAX_DIMENSION = 1920;
    private const MAX_PIXELS = 60000000;
    private const JPEG_QUALITY = 78;

    public function process(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $dimensions = @getimagesize($path);

        if (!$dimensions || empty($dimensions[0]) || empty($dimensions[1])) {
            throw ValidationException::withMessages([
                'foto' => 'No se pudo leer la foto. Si es HEIC/HEIF, espera a que termine la conversión antes de guardar.',
            ]);
        }

        $pixels = (int) $dimensions[0] * (int) $dimensions[1];
        if ($pixels > self::MAX_PIXELS) {
            throw ValidationException::withMessages([
                'foto' => 'La foto tiene una resolución demasiado grande. El máximo es 60 megapíxeles.',
            ]);
        }

        $raw = @file_get_contents($path);
        $source = $raw === false ? false : @imagecreatefromstring($raw);
        unset($raw);

        if (!$source) {
            throw ValidationException::withMessages([
                'foto' => 'El formato no pudo convertirse. Usa JPG, PNG o WebP; HEIC/HEIF se convierte automáticamente en el formulario.',
            ]);
        }

        $source = $this->applyExifOrientation($source, $path, $file->getMimeType());
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, self::MAX_DIMENSION / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        ob_start();
        imagejpeg($target, null, self::JPEG_QUALITY);
        $jpeg = ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        if ($jpeg === false) {
            throw ValidationException::withMessages([
                'foto' => 'No fue posible comprimir la fotografía.',
            ]);
        }

        $storagePath = 'lonas/'.now()->format('Y/m').'/'.Str::uuid().'.jpg';
        if (!Storage::disk('local')->put($storagePath, $jpeg)) {
            throw ValidationException::withMessages([
                'foto' => 'No fue posible guardar la fotografía.',
            ]);
        }

        return [
            'path' => $storagePath,
            'original_name' => $file->getClientOriginalName(),
            'original_bytes' => $file->getSize(),
            'final_bytes' => strlen($jpeg),
        ];
    }

    private function applyExifOrientation($image, string $path, ?string $mime)
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        if ($angle === 0) {
            return $image;
        }

        $rotated = @imagerotate($image, $angle, 0);
        if ($rotated) {
            imagedestroy($image);
            return $rotated;
        }

        return $image;
    }
}
