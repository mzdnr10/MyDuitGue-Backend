<?php

namespace App\Models;

use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ApiClient extends Authenticatable
{
    use Authorizable, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'is_active',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
