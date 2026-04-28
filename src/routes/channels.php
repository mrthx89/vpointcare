<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('waha-agents', function ($user) {
    return ['id' => $user->Id ?? $user->id ?? uniqid(), 'name' => $user->NamaPengguna ?? $user->name ?? 'Agent'];
});
