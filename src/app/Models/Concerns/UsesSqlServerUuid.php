<?php

namespace App\Models\Concerns;

trait UsesSqlServerUuid
{
    public function initializeUsesSqlServerUuid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->timestamps = false;
        $this->primaryKey = 'Id';
    }
}