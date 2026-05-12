<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;     

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'google_id',
        'avatar',
        'whatsapp_number',
        'city',
        'role',
        'is_verified_student',
        'is_admin',
        'document_id_card',
        'document_student_proof',
        'university',
        'field_of_study',
        'title',
        'bio',
        'portfolio_url',
        'skills',
        'hourly_rate',
        'completed_jobs',
        'job_success_rate',
        'total_votes',
        'average_rating',
    ];

    /**
     * The attributes that should be appends for serialization.
     *
     * @var list<string>
     */
    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            return asset('storage/' . $this->avatar);
        }

        return null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'skills' => 'array',
            'is_admin' => 'boolean',
            'is_verified_student' => 'boolean',
        ];
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'client_id');
    }

    public function offers()
    {
        return $this->hasMany(RequestOffer::class, 'provider_id');
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'provider_id');
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function categories()
    {
        return $this->belongsToMany(ServiceCategory::class, 'provider_services', 'user_id', 'service_category_id');
    }
}
