<?php

namespace App\Enums;

enum VendorStatusEnum: string
{
    case PENDING = 'pending';

    case APPROVED = 'approved';

    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::APPROVED => __('Approved'),
            self::REJECTED => __('Rejected'),
        };
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('Pending'),
            self::APPROVED->value => __('Approved'),
            self::REJECTED->value => __('Rejected'),
        ];
    }

    public static function colors(): array
    {
        return [
            'gray' => self::PENDING->value,
            'success' => self::APPROVED->value,
            'danger' => self::REJECTED->value,
        ];
    }
}
