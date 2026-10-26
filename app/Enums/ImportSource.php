<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportSource: string
{
    case Command = 'command';
    case Upload = 'upload';

    public function label(): string
    {
        return match ($this) {
            self::Command => __('Command'),
            self::Upload => __('Upload'),
        };
    }
}
