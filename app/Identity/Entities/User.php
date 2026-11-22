<?php

declare(strict_types=1);

namespace App\Identity\Entities;

use App\Identity\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
            'role' => UserRole::class,
        ];
    }

    /**
     * Passcodes issued to the email address of this account.
     *
     * @return HasMany<OneTimePasscode, $this>
     */
    public function oneTimePasscodes(): HasMany
    {
        return $this->hasMany(OneTimePasscode::class, 'email', 'email');
    }

    /**
     * Whether the account can manage gathering participants.
     */
    public function isAdministrator(): bool
    {
        return $this->role === UserRole::SuperAdmin || $this->role === UserRole::Admin;
    }
}
