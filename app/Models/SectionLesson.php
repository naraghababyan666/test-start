<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionLesson extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [

        'lesson_id',
        'section_id',
    ];

    public function section()
    {
        return $this->hasMany(Section::class, "section_id", "id");
    }

    public function lesson()
    {
        return $this->hasMany(Lesson::class, "lesson_id", "id");
    }
}
