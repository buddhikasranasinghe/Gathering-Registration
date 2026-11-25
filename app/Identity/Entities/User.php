<?php

declare(strict_types=1);

namespace App\Identity\Entities;

use App\Identity\Observers\UserObserver;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Payment;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'batch_id'])]
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
     * The batch this user belongs to.
     *
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Payments recorded by this user.
     *
     * @return HasMany<Payment, $this>
     */
    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    /**
     * Tickets issued by this user.
     *
     * @return HasMany<IssuedTicket, $this>
     */
    public function issuedTickets(): HasMany
    {
        return $this->hasMany(IssuedTicket::class, 'issued_by');
    }

    /**
     * Whether the account can manage gathering participants.
     */
    public function isAdministrator(): bool
    {
        return $this->role === UserRole::SuperAdmin
            || $this->role === UserRole::MainTreasurer
            || $this->role === UserRole::Admin;
    }

    /**
     * Whether the account has the Super Admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Whether the account has the Main Treasurer role.
     */
    public function isMainTreasurer(): bool
    {
        return $this->role === UserRole::MainTreasurer;
    }

    /**
     * Whether the account is restricted to a single batch.
     */
    public function isBatchScoped(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * The role name shown in the interface, including the batch where relevant.
     */
    public function displayRoleLabel(): string
    {
        if ($this->isBatchScoped() && $this->batch !== null) {
            return $this->role->label().' — '.$this->batch->name;
        }

        return $this->role->label();
    }
}
