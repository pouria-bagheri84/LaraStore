<?php

namespace App\Enums;

enum ProductVariationTypeEnum: string
{
    case SELECT = 'Select';
    case RADIO = 'Radio';
    case IMAGE = 'Image';

    public static function labels(): array
    {
        return [
            self::SELECT->value => __('Select'),
            self::RADIO->value => __('Radio'),
            self::IMAGE->value => __('Image'),
        ];
    }
}
