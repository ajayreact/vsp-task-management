<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Modules\TaskManagement\Support\ShareLinkPreviewMeta;
use GdImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

/**
 * Public Open Graph preview image for WhatsApp / Facebook / LinkedIn / X.
 *
 * Composes a clean 1200×630 white canvas with ONLY the user-supplied V logo
 * (public/images/branding/share-preview/v-logo.png). No text, borders, or cards.
 */
class SharePreviewImageController extends Controller
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** Bump when layout/composition changes so caches regenerate. */
    public const LAYOUT_VERSION = 8;

    /** Target visual height for the cropped V mark (px). */
    private const LOGO_TARGET_HEIGHT = 360;

    /** Minimum padding from canvas edges after centering. */
    private const EDGE_PAD = 72;

    public function __invoke(Request $request): Response
    {
        $sourcePath = ShareLinkPreviewMeta::sourceLogoAbsolutePath();

        abort_unless(is_file($sourcePath), 404);

        $version = ShareLinkPreviewMeta::sourceLogoVersion();
        $cacheKey = hash('sha256', self::LAYOUT_VERSION.'|'.$version);
        $cachePath = storage_path('app/share-preview/og-'.$cacheKey.'.png');

        if (! is_file($cachePath)) {
            File::ensureDirectoryExists(dirname($cachePath));
            $this->compose($sourcePath, $cachePath);
        }

        $binary = File::get($cachePath);

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
            'ETag' => '"'.$cacheKey.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    private function compose(string $sourcePath, string $destinationPath): void
    {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if ($canvas === false) {
            abort(500, 'Unable to create preview canvas.');
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $bg = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $bg);

        $logo = $this->loadImage($sourcePath);

        if ($logo === false) {
            imagedestroy($canvas);
            abort(500, 'Unable to read V logo for share preview.');
        }

        $cropped = $this->cropToContent($logo);
        imagedestroy($logo);

        if ($cropped === false) {
            imagedestroy($canvas);
            abort(500, 'Unable to crop V logo for share preview.');
        }

        imagealphablending($cropped, true);
        imagesavealpha($cropped, true);

        $cropW = imagesx($cropped);
        $cropH = imagesy($cropped);

        $maxW = self::WIDTH - (self::EDGE_PAD * 2);
        $maxH = self::HEIGHT - (self::EDGE_PAD * 2);
        $targetH = min(self::LOGO_TARGET_HEIGHT, $maxH);
        $scale = $targetH / max($cropH, 1);

        if ($cropW * $scale > $maxW) {
            $scale = $maxW / max($cropW, 1);
        }

        $destW = max(1, (int) round($cropW * $scale));
        $destH = max(1, (int) round($cropH * $scale));

        $scaled = imagecreatetruecolor($destW, $destH);

        if ($scaled === false) {
            imagedestroy($cropped);
            imagedestroy($canvas);
            abort(500, 'Unable to scale V logo for share preview.');
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefilledrectangle($scaled, 0, 0, $destW, $destH, $transparent);
        imagealphablending($scaled, true);

        imagecopyresampled($scaled, $cropped, 0, 0, 0, 0, $destW, $destH, $cropW, $cropH);
        imagedestroy($cropped);
        $this->sharpen($scaled);

        $logoX = (int) round((self::WIDTH - $destW) / 2);
        $logoY = (int) round((self::HEIGHT - $destH) / 2);
        imagecopy($canvas, $scaled, $logoX, $logoY, 0, 0, $destW, $destH);
        imagedestroy($scaled);

        imagepng($canvas, $destinationPath, 6);
        imagedestroy($canvas);
    }

    private function sharpen(GdImage $image): void
    {
        if (! function_exists('imageconvolution')) {
            return;
        }

        $matrix = [
            [-1, -1, -1],
            [-1, 16, -1],
            [-1, -1, -1],
        ];

        imageconvolution($image, $matrix, 8, 0);
    }

    /**
     * Crop opaque/content bounding box (alpha or non-background pixels).
     */
    private function cropToContent(GdImage $source): GdImage|false
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($source, $x, $y);
                $a = ($rgba & 0x7F000000) >> 24;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                if ($a >= 120) {
                    continue;
                }

                if ($r <= 18 && $g <= 18 && $b <= 18) {
                    continue;
                }

                if ($r >= 245 && $g >= 245 && $b >= 245 && $a <= 10) {
                    continue;
                }

                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX < $minX || $maxY < $minY) {
            $minX = 0;
            $minY = 0;
            $maxX = $width - 1;
            $maxY = $height - 1;
        }

        $pad = 4;
        $minX = max(0, $minX - $pad);
        $minY = max(0, $minY - $pad);
        $maxX = min($width - 1, $maxX + $pad);
        $maxY = min($height - 1, $maxY + $pad);

        $cropW = $maxX - $minX + 1;
        $cropH = $maxY - $minY + 1;

        $cropped = imagecreatetruecolor($cropW, $cropH);

        if ($cropped === false) {
            return false;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);
        imagealphablending($cropped, true);

        imagecopy($cropped, $source, 0, 0, $minX, $minY, $cropW, $cropH);

        return $cropped;
    }

    /**
     * @return GdImage|false
     */
    private function loadImage(string $path): mixed
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return false;
        }

        $image = match ($info[2] ?? null) {
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };

        if ($image instanceof GdImage) {
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        return $image;
    }
}
