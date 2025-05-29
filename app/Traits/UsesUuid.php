<?php

namespace App\Traits;

use illuminate\Support\Str;

trait UsesUuid
{
    public static function boot(): void
    {
        parent::boot();
        self::creating(function ($model): void {
            $model->uuid = Str::uuid()->toString();
        });
    }
}
