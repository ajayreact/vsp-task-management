<?php

function write_tone(string $path, array $segments, int $sampleRate = 44100): void
{
    $frames = '';

    foreach ($segments as [$freq, $duration]) {
        $n = (int) ($sampleRate * $duration);

        for ($i = 0; $i < $n; $i++) {
            $t = $i / $sampleRate;
            $env = min(1.0, $t * 12) * min(1.0, ($duration - $t) * 12);
            $val = (int) round(32767 * 0.35 * $env * sin(2 * M_PI * $freq * $t));
            $val = max(-32768, min(32767, $val));
            $frames .= pack('s', $val);
        }
    }

    $dir = dirname($path);

    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $dataSize = strlen($frames);
    $header = pack(
        'a4Va4a4VvvVVvva4V',
        'RIFF',
        36 + $dataSize,
        'WAVE',
        'fmt ',
        16,
        1,
        1,
        $sampleRate,
        $sampleRate * 2,
        2,
        16,
        'data',
        $dataSize,
    );

    file_put_contents($path, $header.$frames);
}

$base = __DIR__.'/../public/audio/notifications';

write_tone($base.'/classic-chime.wav', [[880, 0.18], [1174.7, 0.22]]);
write_tone($base.'/soft-bell.wav', [[659.25, 0.35], [783.99, 0.45]]);
write_tone($base.'/digital-alert.wav', [[1200, 0.08], [900, 0.08], [1200, 0.12]]);
write_tone($base.'/success-tone.wav', [[523.25, 0.12], [659.25, 0.12], [783.99, 0.18]]);
write_tone($base.'/gentle-notification.wav', [[440, 0.28], [554.37, 0.32]]);

echo "Generated notification sounds in {$base}\n";
