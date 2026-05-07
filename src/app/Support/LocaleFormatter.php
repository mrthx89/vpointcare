<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class LocaleFormatter
{
    public static function date(mixed $value): string
    {
        return self::formatDate($value, LocaleManager::format('date'));
    }

    public static function shortDate(mixed $value): string
    {
        return self::formatDate($value, LocaleManager::format('date_short'));
    }

    public static function dateTime(mixed $value): string
    {
        return self::formatDate($value, LocaleManager::format('datetime'));
    }

    public static function time(mixed $value): string
    {
        return self::formatDate($value, LocaleManager::format('time'));
    }

    public static function shortDateTime(mixed $value): string
    {
        return self::formatDate($value, LocaleManager::format('datetime_short'));
    }

    public static function tableDateFormat(): string
    {
        return LocaleManager::format('date');
    }

    public static function tableDateTimeFormat(): string
    {
        return LocaleManager::format('datetime');
    }

    public static function dateInputFormat(): string
    {
        return LocaleManager::format('date_input');
    }

    public static function number(mixed $value, ?int $precision = null, ?int $maxPrecision = null): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $number = (float) $value;
        $formatted = Number::format($number, precision: $precision, maxPrecision: $maxPrecision, locale: LocaleManager::regional());

        return $formatted === false ? (string) $value : $formatted;
    }

    public static function percent(mixed $value, int $precision = 0): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return self::number($value, $precision) . '%';
    }

    public static function rupiah(mixed $value, int $precision = 0): string
    {
        if ($value === null || $value === '') {
            return 'Rp -';
        }

        return 'Rp ' . self::number($value, $precision);
    }

    private static function formatDate(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        try {
            return Carbon::parse($value)->translatedFormat($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
