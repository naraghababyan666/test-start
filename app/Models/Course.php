<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'cover_image',
        'promo_video',
        'title',
        'description',
        'sub_title',
        'language',
        'type',
        'status',
        'category_id',
        'max_participants',
        'level',
        'definition',
        'trainer_id',
        'price',
        'currency',
        'updated_at',
        'created_at'
    ];

    protected $appends = ["rate"];
    protected $hidden = ["rates"];

// Course statuses
    public const DRAFT = 1;
    public const UNDER_REVIEW = 2;
    public const APPROVED = 3;
    public const DECLINED = 4;
    public const DELETED = 5;

// Course types
    public const ONLINE = 1;
    public const OFFLINE = 2;
    public const ONLINE_WEBINAR = 3;
    public const CONSULTATION = 4;

// Course levels
    public const ALL_LEVELS = 1;
    public const BEGINNER = 2;
    public const MIDDLE = 3;
    public const ADVANCED = 4;


    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'course_id', 'id');

    }

    public function trainer()
    {
        return $this->hasOne(Trainer::class, 'id', 'trainer_id');
    }
    public function sections()
    {
        return $this->hasMany(Section::class, 'course_id', 'id');
    }

    public static function getStatus($id = 0, $isAll = false)
    {
        $statuses = [
            self::DRAFT => "Draft",
            self::UNDER_REVIEW => "Under review",
            self::APPROVED => "Approved",
            self::DECLINED => "Declined",
            self::DELETED => "Deleted",
        ];
        if ($isAll) {
           return array_flip($statuses);
        }
        return $statuses[$id];
    }

    public static function getType($id = 0, $isAll = false)
    {
        $types = [
            self::ONLINE => "Online",
            self::OFFLINE => "Offline",
            self::ONLINE_WEBINAR => "Online webinar",
            self::CONSULTATION => "Consultation",
        ];
        if ($isAll) {
            return array_flip($types);
        }
        return $types[$id];
    }

    public static function getLevels($id = 0, $isAll = false)
    {
        $levels = [
            self::ALL_LEVELS => "All Levels",
            self::BEGINNER => "Beginner",
            self::MIDDLE => "Middle",
            self::ADVANCED => "Advanced",
        ];
        if ($isAll) {
            return array_flip($levels);
        }
        return $levels[$id];
    }

    public function rates(){
        return $this->hasMany(Review::class);
    }
    public function getRateAttribute()
    {
        return   Review::query()->where("course_id",$this->id)->average("rate");
    }

    public function basket(){
        return $this->hasMany(BasketList::class);
    }
    public static function getNamesArray($data)
    {
        $names = [];
        foreach (self::recursiveFind($data, "title") as $value) {
            $names [] = $value;
        }
        return $names;
    }
    public static function getChildrenArray($data)
    {
        $names = [];
        foreach (self::recursiveFind($data, "id") as $value) {
            $names [] = $value;
        }
        return $names;
    }

    public static function recursiveFind(array $haystack, $needle)
    {
        $iterator = new \RecursiveArrayIterator($haystack);
        $recursive = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                yield $value;
            }
        }
    }

}
