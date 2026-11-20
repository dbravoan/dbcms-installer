<?php

declare(strict_types=1);

namespace DavidBravo\ClientArea\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class Player extends Authenticatable
{
    protected $table = 'client_area_players';

    protected $rememberTokenName = '';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
