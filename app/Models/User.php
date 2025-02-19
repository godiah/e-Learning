<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'profile_pic_url',
        'new_email',
        'failed_login_attempts',
        'locked_until',
        'is_instructor',
        'is_affiliate',
        'google_id'
    ];

    public function courses()
    {
        return $this->hasMany(Courses::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }
    
    public function discussionReplies()
    {
        return $this->hasMany(DiscussionReply::class);
    }

    public function instructorApplication()
    {
        return $this->hasOne(InstructorApplication::class);
    }

    public function affiliateApplication()
    {
        return $this->hasOne(Affiliate::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function isAdmin(): bool
    {
        return $this->roles()->where('name', 'admin')->exists();
    }

    public function isInstructor(): bool
    {
        return $this->roles()->where('name', 'instructor')->exists();
    }

    public function isStudent(): bool
    {
        return $this->roles()->where('name', 'student')->exists();
    }

    public function isAffiliate(): bool
    {
        return $this->roles()->where('name', 'affiliate')->exists();
    }

    public function isUserAdmin(): bool
    {
        return $this->roles()->where('name', 'admin-user-mgt')->exists();
    }

    public function isContentAdmin(): bool
    {
        return $this->roles()->where('name', 'admin-content-mgt')->exists();
    }

    public function isFinanceAdmin(): bool
    {
        return $this->roles()->where('name', 'admin-financial-mgt')->exists();
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function affiliate()
    {
        return $this->hasOne(Affiliate::class);
    }


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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
