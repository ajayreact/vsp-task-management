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
 * Composes a compact 1200×630 card from the user-supplied V logo
 * (public/images/branding/share-preview/v-logo.png) plus dynamic share text.
 * Never uses the full "VSP TASK MANAGEMENT" wordmark logos.
 */
class SharePreviewImageController extends Controller
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /** Bump when layout/composition changes so caches regenerate. */
    public const LAYOUT_VERSION = 7;

    /** Target visual height for the cropped V mark (px). */
    private const LOGO_TARGET_HEIGHT = 320;

    private const LEFT_PANEL_WIDTH = 400;

    private const TEXT_PAD_X = 40;

    private const TEXT_PAD_RIGHT = 48;

    public function __invoke(Request $request): Response
    {
        $sourcePath = ShareLinkPreviewMeta::sourceLogoAbsolutePath();

        abort_unless(is_file($sourcePath), 404);

        $brand = $this->sanitizeLine((string) $request->query('brand', config('app.name', 'VSP CRM')), 48);
        $subtitle = $this->sanitizeLine((string) $request->query('line', ''), 90);
        $host = $this->sanitizeLine((string) $request->query('host', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'app.vspcrm.in'), 64);

        if ($brand === '') {
            $brand = (string) config('app.name', 'VSP CRM');
        }

        if ($host === '') {
            $host = 'app.vspcrm.in';
        }

        $version = ShareLinkPreviewMeta::sourceLogoVersion();
        $cacheKey = hash('sha256', implode('|', [
            self::LAYOUT_VERSION,
            $version,
            $brand,
            $subtitle,
            $host,
        ]));
        $cachePath = storage_path('app/share-preview/og-'.$cacheKey.'.png');

        if (! is_file($cachePath)) {
            File::ensureDirectoryExists(dirname($cachePath));
            $this->compose($sourcePath, $cachePath, $brand, $subtitle, $host);
        }

        $binary = File::get($cachePath);

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
            'ETag' => '"'.$cacheKey.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    private function compose(
        string $sourcePath,
        string $destinationPath,
        string $brand,
        string $subtitle,
        string $host,
    ): void {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if ($canvas === false) {
            abort(500, 'Unable to create preview canvas.');
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $panel = imagecolorallocate($canvas, 241, 245, 249);
        $bg = imagecolorallocate($canvas, 255, 255, 255);
        $divider = imagecolorallocate($canvas, 226, 232, 240);

        imagefilledrectangle($canvas, 0, 0, self::LEFT_PANEL_WIDTH - 1, self::HEIGHT, $panel);
        imagefilledrectangle($canvas, self::LEFT_PANEL_WIDTH, 0, self::WIDTH, self::HEIGHT, $bg);
        imageline($canvas, self::LEFT_PANEL_WIDTH, 40, self::LEFT_PANEL_WIDTH, self::HEIGHT - 40, $divider);

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
        $scale = self::LOGO_TARGET_HEIGHT / max($cropH, 1);
        $maxLogoW = self::LEFT_PANEL_WIDTH - 80;
        if ($cropW * $scale > $maxLogoW) {
            $scale = $maxLogoW / max($cropW, 1);
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

        $logoX = (int) round((self::LEFT_PANEL_WIDTH - $destW) / 2);
        $logoY = (int) round((self::HEIGHT - $destH) / 2);
        imagecopy($canvas, $scaled, $logoX, $logoY, 0, 0, $destW, $destH);
        imagedestroy($scaled);

        $textX = self::LEFT_PANEL_WIDTH + self::TEXT_PAD_X;
        $textMaxWidth = self::WIDTH - $textX - self::TEXT_PAD_RIGHT;

        $brandFont = $this->fontPath(bold: true);
        $regularFont = $this->fontPath(bold: false);

        $brandSize = 48;
        $subtitleSize = 26;
        $hostSize = 22;

        $brandLine = $this->fitText($brand, $brandFont, $brandSize, $textMaxWidth);
        $subtitleLines = $subtitle !== ''
            ? $this->wrapText($subtitle, $regularFont, $subtitleSize, $textMaxWidth, 2)
            : [];
        $hostLine = $this->fitText($host, $regularFont, $hostSize, $textMaxWidth);

        $lineGap = 14;
        $brandH = $this->textHeight($brandFont, $brandSize, $brandLine);
        $subtitleHeights = array_map(
            fn (string $line) => $this->textHeight($regularFont, $subtitleSize, $line),
            $subtitleLines,
        );
        $subtitleBlockH = array_sum($subtitleHeights)
            + (max(count($subtitleLines) - 1, 0) * 8);
        $hostH = $this->textHeight($regularFont, $hostSize, $hostLine);

        $textBlockH = $brandH
            + ($subtitleBlockH > 0 ? $lineGap + $subtitleBlockH : 0)
            + $lineGap + $hostH;

        $textY = (int) round((self::HEIGHT - $textBlockH) / 2);

        $ink = imagecolorallocate($canvas, 15, 23, 42);
        $muted = imagecolorallocate($canvas, 71, 85, 105);
        $soft = imagecolorallocate($canvas, 100, 116, 139);

        $this->drawText($canvas, $brandFont, $brandSize, $textX, $textY + $brandH, $ink, $brandLine);
        $cursor = $textY + $brandH;

        foreach ($subtitleLines as $index => $subtitleLine) {
            $cursor += ($index === 0 ? $lineGap : 8) + $subtitleHeights[$index];
            $this->drawText($canvas, $regularFont, $subtitleSize, $textX, $cursor, $muted, $subtitleLine);
        }

        $cursor += $lineGap + $hostH;
        $this->drawText($canvas, $regularFont, $hostSize, $textX, $cursor, $soft, $hostLine);

        imagepng($canvas, $destinationPath, 6);
        imagedestroy($canvas);
    }

    private function sharpen(GdImage $image): void
    {
        if (! function_exists('imageconvolution')) {
            return;
        }

        // Mild unsharp so resampled marks stay crisp in WhatsApp thumbnails.
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

                // Skip fully transparent pixels.
                if ($a >= 120) {
                    continue;
                }

                // Skip near-black padding (common for V marks on black squares).
                if ($r <= 18 && $g <= 18 && $b <= 18) {
                    continue;
                }

                // Skip near-white padding.
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
            // Fallback: keep full image if content detection fails.
            $minX = 0;
            $minY = 0;
            $maxX = $width - 1;
            $maxY = $height - 1;
        }

        // Small breathing room around the mark (not large empty frames).
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

    private function fontPath(bool $bold): ?string
    {
        $candidates = $bold
            ? [
                resource_path('fonts/DejaVuSans-Bold.ttf'),
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
                'C:\\Windows\\Fonts\\arialbd.ttf',
                'C:\\Windows\\Fonts\\segoeuib.ttf',
                'C:\\Windows\\Fonts\\arial.ttf',
            ]
            : [
                resource_path('fonts/DejaVuSans.ttf'),
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
                'C:\\Windows\\Fonts\\arial.ttf',
                'C:\\Windows\\Fonts\\segoeui.ttf',
            ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, ?string $font, float $size, int $maxWidth, int $maxLines): array
    {
        $text = trim($text);

        if ($text === '' || $maxLines < 1) {
            return [];
        }

        if ($this->measureWidth($font, $size, $text) <= $maxWidth) {
            return [$text];
        }

        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if ($this->measureWidth($font, $size, $candidate) <= $maxWidth) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $lines[] = $this->fitText($word, $font, $size, $maxWidth);
                $current = '';
            }

            if (count($lines) >= $maxLines) {
                break;
            }
        }

        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
        }

        if ($lines === []) {
            return [$this->fitText($text, $font, $size, $maxWidth)];
        }

        // If leftover words remain, truncate the last visible line.
        $consumed = implode(' ', $lines);
        if (mb_strlen($consumed) < mb_strlen($text)) {
            $lastIndex = count($lines) - 1;
            $lines[$lastIndex] = $this->fitText($lines[$lastIndex], $font, $size, $maxWidth);
        }

        return $lines;
    }

    private function fitText(string $text, ?string $font, float $size, int $maxWidth): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if ($font === null || ! function_exists('imagettfbbox')) {
            return mb_strlen($text) > 48 ? mb_substr($text, 0, 45).'…' : $text;
        }

        if ($this->measureWidth($font, $size, $text) <= $maxWidth) {
            return $text;
        }

        $ellipsis = '…';
        $low = 0;
        $high = mb_strlen($text);
        $best = $ellipsis;

        while ($low <= $high) {
            $mid = (int) floor(($low + $high) / 2);
            $candidate = rtrim(mb_substr($text, 0, $mid)).$ellipsis;
            if ($this->measureWidth($font, $size, $candidate) <= $maxWidth) {
                $best = $candidate;
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return $best;
    }

    private function measureWidth(?string $font, float $size, string $text): int
    {
        if ($font === null || $text === '' || ! function_exists('imagettfbbox')) {
            return strlen($text) * 10;
        }

        $box = imagettfbbox($size, 0, $font, $text);
        if ($box === false) {
            return strlen($text) * 10;
        }

        return (int) abs($box[2] - $box[0]);
    }

    private function textHeight(?string $font, float $size, string $text): int
    {
        if ($text === '') {
            return 0;
        }

        if ($font === null || ! function_exists('imagettfbbox')) {
            return (int) round($size * 1.2);
        }

        $box = imagettfbbox($size, 0, $font, $text);
        if ($box === false) {
            return (int) round($size * 1.2);
        }

        return (int) abs($box[7] - $box[1]);
    }

    private function drawText(GdImage $canvas, ?string $font, float $size, int $x, int $baselineY, int $color, string $text): void
    {
        if ($text === '') {
            return;
        }

        if ($font !== null && function_exists('imagettftext')) {
            imagettftext($canvas, $size, 0, $x, $baselineY, $color, $font, $text);

            return;
        }

        // Last-resort built-in font (lower quality; kept so previews never blank).
        imagestring($canvas, 5, $x, max(0, $baselineY - 14), $text, $color);
    }

    private function sanitizeLine(string $value, int $maxChars): string
    {
        $value = trim(preg_replace("/\s+/u", ' ', $value) ?? '');
        $value = str_replace(["\0", "\r", "\n"], '', $value);

        if (mb_strlen($value) > $maxChars) {
            return mb_substr($value, 0, $maxChars - 1).'…';
        }

        return $value;
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
