<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use LaravelEG\Laravel\Traits\UserOnline;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, UserOnline;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'reporting_authority_id',
        'is_active',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Your existing model code
    public function setCache($key, $value = null, $expirationTime = 3600)
    {
        // Provide a default value if $value is not passed
        if ($value === null) {
            $value = 'default_value';  // Use a reasonable default or adjust as needed
        }

        Cache::put($key, $value, $expirationTime);
    }

    public function getCache($key)
    {
        return Cache::get($key);
    }

    public function assignedCompanies()
    {
        return $this->hasMany(AssignCompanies::class, 'user_id');
    }

    public function leadProvideby()
    {
        return $this->hasMany(Lead::class, 'lead_provide_by')->latest('id');
    }

    public function dispositionHistory()
    {
        return $this->hasMany(Disposition::class);
    }

    // Relationships
    public function reportingAuthority()
    {
        return $this->belongsTo(User::class, 'reporting_authority_id')->withDefault([
            'name' => 'No Reporting Authority'
        ]);
    }

    /**
     * Dummy method to prevent highideas/laravel-users-online package 
     * from throwing an exception during logout.
     */
    public function pullCache()
    {
        // Do nothing, we handle cache natively now
    }
}
