<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AiSettings
{
    private const CACHE_KEY = 'mpengaturan_ai_default_v2';

    public static function get(): ?object
    {
        $settings = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): ?array {
            $row = DB::table('MPengaturanAi')
                ->where('KodePengaturan', 'DEFAULT')
                ->where('NonAktif', false)
                ->first();

            return $row ? (array) $row : null;
        });

        return is_array($settings) ? (object) $settings : null;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('mpengaturan_ai_default');
    }
}
