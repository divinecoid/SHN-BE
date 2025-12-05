<?php

namespace App\Enums;

class NotificationType
{
    public const SALES_ORDER = 'sales_order';
    public const WORK_ORDER = 'work_order';
    public const WORK_ORDER_PLANNING = 'work_order_planning';
    public const WORK_ORDER_ACTUAL = 'work_order_actual';

    public static function values(): array
    {
        return [
            self::SALES_ORDER,
            self::WORK_ORDER,
            self::WORK_ORDER_PLANNING,
            self::WORK_ORDER_ACTUAL,
        ];
    }

    public static function isValid(?string $type): bool
    {
        return $type !== null && in_array($type, self::values(), true);
    }

    public static function label(string $type): string
    {
        $map = [
            self::SALES_ORDER => 'Sales Order',
            self::WORK_ORDER => 'Work Order',
            self::WORK_ORDER_PLANNING => 'Work Order Planning',
            self::WORK_ORDER_ACTUAL => 'Work Order Actual',
        ];
        return $map[$type] ?? $type;
    }
}

