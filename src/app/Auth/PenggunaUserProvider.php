<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Str;

class PenggunaUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        if (! $this->isValidPenggunaIdentifier($identifier)) {
            return null;
        }

        return parent::retrieveById($identifier);
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        if (! $this->isValidPenggunaIdentifier($identifier)) {
            return null;
        }

        return parent::retrieveByToken($identifier, $token);
    }

    private function isValidPenggunaIdentifier(mixed $identifier): bool
    {
        return is_string($identifier) && Str::isUuid($identifier);
    }
}
