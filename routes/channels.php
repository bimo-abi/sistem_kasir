<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('transactions', function (User $user): bool {
    return in_array($user->role, ['admin', 'kasir'], true);
});
