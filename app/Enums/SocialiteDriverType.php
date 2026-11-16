<?php

namespace App\Enums;

enum SocialiteDriverType: string {
case GOOGLE = 'google';
case GOOGLE_ICON = 'patient/images/google.svg';

    public static function getAll(): array {
        return [
            self::GOOGLE->value,
        ];
    }
}
