<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Modules\TaskManagement\Support\ShareLinkPreviewMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

/**
 * Public Open Graph preview image for WhatsApp / Facebook / LinkedIn / X.
 *
 * Composes a clean 1200×630 card from the user-supplied V logo
 * (public/images/branding/share-preview/v-logo.png). Never uses the full
 * "VSP TASK MANAGEMENT" wordmark logos.
 */
class SharePreviewImageController extends Controller
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    public function __invoke(Request $request): Response
    {
        $sourcePath = ShareLinkPreviewMeta::sourceLogoAbsolutePath();

        abort_unless(is_file($sourcePath), 404);

        $version = ShareLinkPreviewMeta::sourceLogoVersion();
        $cachePath = storage_path('app/share-preview/og-'.$version.'.png');

        if (! is_file($cachePath)) {
            File::ensureDirectoryExists(dirname($cachePath));
            $this->compose($sourcePath, $cachePath);
        }

        $binary = File::get($cachePath);

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
            'ETag' => '"'.$version.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    private function compose(string $sourcePath, string $destinationPath): void
    {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if ($canvas === false) {
            abort(500, 'Unable to create preview canvas.');
        }

        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        // Soft light background (professional, not pure white glare).
        $bg = imagecolorallocate($canvas, 248, 250, 252);
        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $bg);

        // Subtle inner card.
        $card = imagecolorallocate($canvas, 255, 255, 255);
        $border = imagecolorallocate($canvas, 226, 232, 240);
        $padX = 72;
        $padY = 56;
        imagefilledrectangle($canvas, $padX, $padY, self::WIDTH - $padX, self::HEIGHT - $padY, $card);
        imagerectangle($canvas, $padX, $padY, self::WIDTH - $padX, self::HEIGHT - $padY, $border);

        $logo = $this->loadImage($sourcePath);

        if ($logo === false) {
            imagedestroy($canvas);
            abort(500, 'Unable to read V logo for share preview.');
        }

        imagesavealpha($logo, true);

        $logoW = imagesx($logo);
        $logoH = imagesy($logo);

        // Keep the V mark prominent but not oversized (~42% of card height).
        $maxW = (int) (self::WIDTH * 0.42);
        $maxH = (int) (self::HEIGHT * 0.42);
        $scale = min($maxW / max($logoW, 1), $maxH / max($logoH, 1));
        $destW = max(1, (int) round($logoW * $scale));
        $destH = max(1, (int) round($logoH * $scale));
        $destX = (int) round((self::WIDTH - $destW) / 2);
        $destY = (int) round((self::HEIGHT - $destH) / 2);

        imagecopyresampled($canvas, $logo, $destX, $destY, 0, 0, $destW, $destH, $logoW, $logoH);

        imagepng($canvas, $destinationPath, 6);

        imagedestroy($logo);
        imagedestroy($canvas);
    }

    /**
     * @return \GdImage|false
     */
    private function loadImage(string $path): mixed
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return false;
        }

        return match ($info[2] ?? null) {
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };
    }
}
