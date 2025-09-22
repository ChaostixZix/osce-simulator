<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PatientVisualization extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_prompt',
        'enhanced_prompt',
        'prompt_hash',
        'image_path',
        'image_url',
        'mime_type',
        'generation_options',
        'generated_at',
        'user_id',
        'osce_case_id'
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'generation_options' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function osceCase()
    {
        return $this->belongsTo(OsceCase::class);
    }

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute($value)
    {
        if ($value && Storage::exists($this->image_path)) {
            return Storage::url($this->image_path);
        }
        return null;
    }

    /**
     * Check if the image file still exists
     */
    public function imageExists(): bool
    {
        return $this->image_path && Storage::exists($this->image_path);
    }

    /**
     * Delete the image file along with the model
     */
    public function deleteWithFile(): bool
    {
        if ($this->image_path && Storage::exists($this->image_path)) {
            Storage::delete($this->image_path);
        }
        return $this->delete();
    }
}