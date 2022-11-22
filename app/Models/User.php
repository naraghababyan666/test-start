<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'role_id',
        'company_name',
        'tax_identity_number',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {

    }

    public static function isSuperAdmin($id)
    {
        return User::query()->where("id", $id)->where("role_id", Role::SUPER_ADMIN)->exists();
    }

    public static function isTrainerOrTrainingCenter($id)
    {
        return User::query()->where("id", $id)->whereIn("role_id", [Role::TRAINER, Role::TRAINING_CENTER])->exists();
    }

    public static function isModerator($id)
    {

        return User::query()->where("id", $id)->where("role_id", Role::MODERATOR)->exists();

    }

    public static function findModerator()
    {
        $moderator = User::query()->where("role_id", Role::MODERATOR)->random(1);
        if ($moderator) {
            return $moderator->id;
        }
        return false;
    }

    public function studentcourses()
    {
        return $this->hasMany(StudentCourses::class);
    }

    public function trainermeta(){
        return $this->hasOne(TrainerMeta::class);
    }

    public function basketlist()
    {
        return $this->hasMany(BasketList::class);
    }

    public function wishlist()
    {
        return $this->hasMany(WishList::class);
    }
}
