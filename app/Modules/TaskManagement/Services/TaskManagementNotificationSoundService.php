<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\AppSetting;
use App\Modules\TaskManagement\Enums\NotificationSystemSound;
use App\Modules\TaskManagement\Models\NotificationSoundAsset;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskManagementNotificationSoundService
{
    public const SETTINGS_GROUP = 'task_management';

    public const SETTINGS_KEY = 'notification_sound';

    public const MAX_UPLOAD_KILOBYTES = 5120;

    /**
     * @return array{
     *     enabled: bool,
     *     source: string,
     *     system_sound: string,
     *     custom: array{media_id: int|null, file_name: string|null, has_file: bool},
     *     system_sounds: array<int, array{value: string, label: string, url: string}>
     * }
     */
    public function settingsPayload(): array
    {
        $payload = $this->rawPayload();

        return [
            'enabled' => $this->isEnabled($payload),
            'source' => $this->source($payload),
            'system_sound' => $this->systemSoundValue($payload),
            'custom' => $this->customMeta($payload),
            'system_sounds' => NotificationSystemSound::options(),
        ];
    }

    /**
     * @return array{enabled: bool, source: string|null, system_sound: string|null, url: string|null}
     */
    public function playbackConfig(): array
    {
        $payload = $this->rawPayload();

        if (! $this->isEnabled($payload)) {
            return [
                'enabled' => false,
                'source' => null,
                'system_sound' => null,
                'url' => null,
            ];
        }

        if ($this->source($payload) === 'custom') {
            $media = $this->resolveCustomMedia($payload);

            if ($media !== null) {
                return [
                    'enabled' => true,
                    'source' => 'custom',
                    'system_sound' => null,
                    'url' => route('tasks.notification-sound.custom', absolute: false),
                ];
            }
        }

        $sound = $this->resolvedSystemSound($payload);

        return [
            'enabled' => true,
            'source' => 'system',
            'system_sound' => $sound->value,
            'url' => $sound->publicUrl(),
        ];
    }

    public function writePolicy(bool $enabled, string $source, string $systemSound): void
    {
        if (! in_array($source, ['system', 'custom'], true)) {
            throw new InvalidArgumentException('Notification sound source must be system or custom.');
        }

        $sound = NotificationSystemSound::tryFrom($systemSound);

        if ($sound === null) {
            throw new InvalidArgumentException('The selected system notification sound is invalid.');
        }

        $payload = $this->rawPayload();

        if ($source === 'custom' && $this->resolveCustomMedia($payload) === null) {
            throw new InvalidArgumentException('Upload a custom notification sound before selecting custom upload.');
        }

        AppSetting::put(self::SETTINGS_GROUP, self::SETTINGS_KEY, [
            'enabled' => $enabled,
            'source' => $source,
            'system_sound' => $sound->value,
            'custom_media_id' => $payload['custom_media_id'] ?? null,
        ]);
    }

    public function uploadCustomSound(UploadedFile $file): Media
    {
        $asset = NotificationSoundAsset::singleton();
        $previous = $asset->customSoundMedia();

        $asset->clearMediaCollection('custom_sound');

        $media = $asset->addMedia($file)->toMediaCollection('custom_sound');

        if ($previous !== null && $previous->id !== $media->id) {
            $previous->delete();
        }

        $payload = $this->rawPayload();

        AppSetting::put(self::SETTINGS_GROUP, self::SETTINGS_KEY, [
            'enabled' => $this->isEnabled($payload),
            'source' => 'custom',
            'system_sound' => $this->systemSoundValue($payload),
            'custom_media_id' => $media->id,
        ]);

        return $media;
    }

    public function deleteCustomSound(): void
    {
        $asset = NotificationSoundAsset::singleton();
        $asset->clearMediaCollection('custom_sound');

        $payload = $this->rawPayload();

        AppSetting::put(self::SETTINGS_GROUP, self::SETTINGS_KEY, [
            'enabled' => $this->isEnabled($payload),
            'source' => 'system',
            'system_sound' => $this->systemSoundValue($payload),
            'custom_media_id' => null,
        ]);
    }

    public function customMedia(): ?Media
    {
        return $this->resolveCustomMedia($this->rawPayload());
    }

    /**
     * @return array<string, mixed>
     */
    protected function rawPayload(): array
    {
        return AppSetting::payload(self::SETTINGS_GROUP, self::SETTINGS_KEY);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function isEnabled(array $payload): bool
    {
        if (! array_key_exists('enabled', $payload)) {
            return true;
        }

        return (bool) $payload['enabled'];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function source(array $payload): string
    {
        $source = $payload['source'] ?? 'system';

        return in_array($source, ['system', 'custom'], true) ? $source : 'system';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function systemSoundValue(array $payload): string
    {
        return $this->resolvedSystemSound($payload)->value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolvedSystemSound(array $payload): NotificationSystemSound
    {
        $value = $payload['system_sound'] ?? null;

        return NotificationSystemSound::tryFrom(is_string($value) ? $value : '')
            ?? NotificationSystemSound::default();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{media_id: int|null, file_name: string|null, has_file: bool}
     */
    protected function customMeta(array $payload): array
    {
        $media = $this->resolveCustomMedia($payload);

        return [
            'media_id' => $media?->id,
            'file_name' => $media?->file_name,
            'has_file' => $media !== null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveCustomMedia(array $payload): ?Media
    {
        $mediaId = $payload['custom_media_id'] ?? null;

        if (! is_numeric($mediaId)) {
            return NotificationSoundAsset::singleton()->customSoundMedia();
        }

        $media = Media::query()->find((int) $mediaId);

        if ($media === null) {
            return NotificationSoundAsset::singleton()->customSoundMedia();
        }

        $asset = NotificationSoundAsset::singleton();

        if ($media->model_type !== $asset->getMorphClass() || (int) $media->model_id !== (int) $asset->id) {
            return null;
        }

        if ($media->collection_name !== 'custom_sound') {
            return null;
        }

        return $media;
    }
}
