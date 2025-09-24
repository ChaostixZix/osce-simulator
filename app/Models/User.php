<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'supabase_id',
        'provider',
        'provider_id',
        'avatar',
        'last_login_at',
        'is_migrated',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'supabase_id',
        'provider_id',
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
            'is_admin' => 'boolean',
            'is_banned' => 'boolean',
            'is_migrated' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) ($this->is_admin ?? false);
    }

    public function isBanned(): bool
    {
        return (bool) ($this->is_banned ?? false);
    }

    public function isMigrated(): bool
    {
        return (bool) ($this->is_migrated ?? false);
    }

    public function getAuthProvider(): ?string
    {
        return $this->provider;
    }

    public function getLastLoginAt(): ?\Illuminate\Support\Carbon
    {
        return $this->last_login_at;
    }

    // Removed forum-related relationships (posts, comments)

    public function onboardingCompletions()
    {
        return $this->hasMany(OnboardingCompletion::class);
    }
}
