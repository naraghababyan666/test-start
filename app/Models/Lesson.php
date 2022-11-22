<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'title',
        'duration',
        'start_time',
        'course_id',
        'address',
        'article',
        'video_url',
        'position',
    ];
    protected $hidden = ["section"];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function section()
    {
        return $this->hasMany(SectionLesson::class, 'lesson_id', 'id');
    }


}
