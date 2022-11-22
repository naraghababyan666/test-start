<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasketList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id'
    ];

    public function users(){
        return $this->belongsTo(User::class);
    }
    public function courses(){
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}
