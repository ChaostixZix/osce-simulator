<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoapNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'osce_case_id',
        'author_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'state'
    ];

    public function osceCase()
    {
        return $this->belongsTo(OsceCase::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
