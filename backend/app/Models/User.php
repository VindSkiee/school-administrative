<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\RecordsActivity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;


class User extends Authenticatable implements JWTSubject
{
    use RecordsActivity;

    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'avatar',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Jika kolom avatar di database ADA isinya
                if (!empty($this->avatar)) {
                    
                    // Jika itu URL dari luar (misal: Google/SSO)
                    if (str_starts_with($this->avatar, 'http')) {
                        return $this->avatar;
                    }
                    
                    // Langsung paksa jadikan URL tanpa mengecek exists()
                    return asset('storage/' . $this->avatar);
                }
                
                return null;
            }
        );
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    // --- JWT Methods ---

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * Di sini kita tanamkan 'role' ke dalam token agar hemat query DB di Middleware
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }

    // --- Relationships ---

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function principal(): HasOne
    {
        return $this->hasOne(Principal::class);
    }
}
