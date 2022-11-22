<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'course_id', 'rate',"message"
    ];
    protected $table = 'reviews';

    public function user(){
        return $this->belongsTo(User::class)->select(array('id', 'first_name', 'last_name', 'avatar'));
    }

    public function course(){
        return $this->belongsTo(Course::class);
    }
}
