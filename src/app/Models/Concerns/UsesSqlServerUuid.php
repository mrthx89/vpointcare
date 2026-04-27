<?php

namespace App\Models\Concerns;

trait UsesSqlServerUuid
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $primaryKey = 'Id';
}
