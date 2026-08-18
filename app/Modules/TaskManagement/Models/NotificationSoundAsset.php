<?php

namespace App\Modules\TaskManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NotificationSoundAsset extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'tm_notification_sound_assets';

    public static function singleton(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('custom_sound')
            ->singleFile()
            ->useDisk('local');
    }

    public function replaceCustomSound(string $path, string $fileName): Media
    {
        $this->clearMediaCollection('custom_sound');

        return $this->addMedia($path)
            ->usingFileName($fileName)
            ->toMediaCollection('custom_sound');
    }

    public function customSoundMedia(): ?Media
    {
        return $this->getFirstMedia('custom_sound');
    }
}
