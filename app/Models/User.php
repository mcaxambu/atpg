<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\CompanyAccessInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'invited_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invited_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCompany(): bool
    {
        return $this->role === UserRole::Company;
    }

    /**
     * Convidado que ainda nao definiu a senha nao consegue entrar.
     */
    public function hasActivatedAccess(): bool
    {
        return filled($this->password);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::Admin);
    }

    public function scopeCompanies(Builder $query): Builder
    {
        return $query->where('role', UserRole::Company);
    }

    public function sendCompanyInvitation(): void
    {
        $this->forceFill(['invited_at' => now()])->save();

        // O convite reaproveita o token de redefinicao de senha, entao ele
        // precisa ser criado pelo broker — a notificacao so o transporta.
        $this->notify(new CompanyAccessInvitation(
            Password::broker()->createToken($this)
        ));
    }
}
