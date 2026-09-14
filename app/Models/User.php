<?php

namespace App\Models;

use App\Enums\AdminModule;
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
        'abilities',
        'company_id',
        'member_id',
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
            'abilities' => 'array',
            'invited_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCompany(): bool
    {
        return $this->role === UserRole::Company;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }

    /**
     * Perfil de colunista deste acesso, se houver.
     *
     * Nao existe usuario "colunista": quem assina coluna e a empresa ou o
     * membro por tras do acesso, marcado como colunista no cadastro. Por isso
     * a busca passa pelo vinculo, e nao por um papel proprio.
     */
    public function columnist(): ?Columnist
    {
        $cadastro = $this->company ?? $this->member;

        $perfil = $cadastro?->columnist;

        return $perfil && $perfil->is_active ? $perfil : null;
    }

    /**
     * Admin sem lista de modulos e a diretoria plena: enxerga o painel inteiro.
     * E tambem o estado de todo admin criado antes desta coluna existir.
     */
    public function hasFullAccess(): bool
    {
        return $this->isAdmin() && blank($this->abilities);
    }

    public function canAccessModule(AdminModule|string $module): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        if ($this->hasFullAccess()) {
            return true;
        }

        $module = $module instanceof AdminModule ? $module : AdminModule::tryFrom($module);

        return $module !== null && in_array($module->value, $this->abilities ?? [], true);
    }

    /**
     * @return array<int, AdminModule>
     */
    public function allowedModules(): array
    {
        if ($this->hasFullAccess()) {
            return AdminModule::all();
        }

        return array_values(array_filter(
            AdminModule::all(),
            fn (AdminModule $module) => $this->canAccessModule($module)
        ));
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
