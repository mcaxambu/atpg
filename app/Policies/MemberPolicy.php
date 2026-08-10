<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

/**
 * A associacao gerencia todo mundo; a empresa gerencia apenas os proprios
 * colaboradores.
 */
class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCompany();
    }

    public function view(User $user, Member $member): bool
    {
        return $this->owns($user, $member);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || (bool) $user->company_id;
    }

    public function update(User $user, Member $member): bool
    {
        return $this->owns($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return $this->owns($user, $member);
    }

    /**
     * Aprovar e rejeitar sao decisoes editoriais da associacao. Uma empresa
     * nao publica o proprio colaborador direto no diretorio.
     */
    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }

    private function owns(User $user, Member $member): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCompany()
            && $member->company_id !== null
            && $member->company_id === $user->company_id;
    }
}
