<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'birthday',
        'is_active',
        'date_archived_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_archived_at' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getIsEmailVerifiedAttribute(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function getIsArchivedAttribute(): bool
    {
        return ! is_null($this->date_archived_at);
    }

    public function profile() { return $this->hasOne(Profile::class); }
}
