<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;
    protected $table = 'quizzes';

    protected $fillable = [
        'section_id',
        'position'
    ];

    public function section(){
        return $this->belongsTo(Section::class);
    }

    public function quizquestion(){
        return $this->hasMany(QuizQuestion::class);
    }
}
