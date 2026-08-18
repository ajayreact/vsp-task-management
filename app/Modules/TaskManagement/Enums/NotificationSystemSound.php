<?php

namespace App\Modules\TaskManagement\Enums;

enum NotificationSystemSound: string
{
    case ClassicChime = 'classic_chime';
    case SoftBell = 'soft_bell';
    case DigitalAlert = 'digital_alert';
    case SuccessTone = 'success_tone';
    case GentleNotification = 'gentle_notification';

    public function label(): string
    {
        return match ($this) {
            self::ClassicChime => 'Classic Chime',
            self::SoftBell => 'Soft Bell',
            self::DigitalAlert => 'Digital Alert',
            self::SuccessTone => 'Success Tone',
            self::GentleNotification => 'Gentle Notification',
        };
    }

    public function filename(): string
    {
        return match ($this) {
            self::ClassicChime => 'classic-chime.wav',
            self::SoftBell => 'soft-bell.wav',
            self::DigitalAlert => 'digital-alert.wav',
            self::SuccessTone => 'success-tone.wav',
            self::GentleNotification => 'gentle-notification.wav',
        };
    }

    public function publicUrl(): string
    {
        return '/audio/notifications/'.$this->filename();
    }

    /**
     * @return array<int, array{value: string, label: string, url: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $sound) => [
                'value' => $sound->value,
                'label' => $sound->label(),
                'url' => $sound->publicUrl(),
            ],
            self::cases(),
        );
    }

    public static function default(): self
    {
        return self::ClassicChime;
    }
}
