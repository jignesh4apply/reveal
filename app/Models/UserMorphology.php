<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserMorphology extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'user_id',
        'session_id',
        'gender',
        'age',
        'height',
        'weight',
        'target_weight',
        'report_email',
        'waist',
        'hips',
        'neck',
        'bicep',
        'chest',
        'thighs',
        'calfs',
        'image_fv',
        'image_bv',
        'image_ls',
        'image_rs',
        'generation_status',
        'generation_error',
        'bmi',
        'whr',
        'body_fat',
        'whtr',
        'lmi',
        'composition_score',
        'raw_report_json',
        'report_json',
        'report_generated_at',
        'report_screenshots',
    ];

     protected $casts = [
        'report_json' => 'array',
        'raw_report_json' => 'array',
        'report_screenshots' => 'array',
    ];

    public function getImageFvAttribute($value)
    {
        return $this->getFullImageUrl($value);
    }

    public function getImageBvAttribute($value)
    {
        return $this->getFullImageUrl($value);
    }

    public function getImageLsAttribute($value)
    {
        return $this->getFullImageUrl($value);
    }

    public function getImageRsAttribute($value)
    {
        return $this->getFullImageUrl($value);
    }

    private function getFullImageUrl($path)
    {
        if (empty($path)) {
            return null;
        }

        return asset(Storage::url($path));
    }
}
