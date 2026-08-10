<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isAdmin() || $user->company_id === $company->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * A empresa edita a propria vitrine (ver CompanyProfileRequest, que limita
     * os campos); o cadastro completo continua sendo do admin.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->isAdmin() || $user->company_id === $company->id;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->isAdmin();
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }
}
