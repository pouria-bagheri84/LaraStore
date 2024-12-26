<?php

namespace App\Enums;

enum ProductStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public static function labels(): array
    {
        return [
            self::DRAFT->value => __('Draft'),
            self::PUBLISHED->value => __('Published'),
        ];
    }

    public static function colors(): array
    {
        return [
            'gray' => self::DRAFT->value,
            'success' => self::PUBLISHED->value,
        ];
    }
}
