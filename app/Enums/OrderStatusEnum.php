<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case DRAFT = 'draft';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public static function labels()
    {
        return[
            self::DRAFT->value => __('Draft'),
            self::PAID->value => __('Paid'),
            self::SHIPPED->value => __('Shipped'),
            self::DELIVERED->value => __('Delivered'),
            self::CANCELLED->value => __('Cancelled'),
        ];
    }
}
