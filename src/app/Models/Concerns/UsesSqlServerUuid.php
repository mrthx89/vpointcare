<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait UsesSqlServerUuid
{
    public static function bootUsesSqlServerUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::orderedUuid();
            }
        });
    }

    public function initializeUsesSqlServerUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->timestamps = false;
        $this->primaryKey = 'Id';
    }
}