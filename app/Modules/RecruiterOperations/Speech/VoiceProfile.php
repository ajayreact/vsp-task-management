<?php

namespace App\Modules\RecruiterOperations\Speech;

/**
 * A provider-neutral voice choice such as "indian_english". Each provider maps
 * it to whatever voice it actually has.
 */
final readonly class VoiceProfile
{
    public function __construct(
        public string $key,
        public string $label,
        public string $locale,
        public string $flag = '',
    ) {}

    /**
     * @return array{key: string, label: string, locale: string, flag: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'locale' => $this->locale,
            'flag' => $this->flag,
        ];
    }
}
