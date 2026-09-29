<?php

namespace App\Modules\RecruiterOperations\Speech;

/**
 * Audio produced by a server-side speech provider.
 */
final readonly class TrainingAudio
{
    public function __construct(
        public string $contents,
        public string $mimeType = 'audio/mpeg',
        public string $extension = 'mp3',
    ) {}
}
