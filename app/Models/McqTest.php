<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class McqTest extends Model
{
    protected $fillable = [
        'title',
        'description',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(McqQuestion::class)->orderBy('order');
    }
}
