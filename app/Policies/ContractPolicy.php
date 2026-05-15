<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contract $contract): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Contract $contract): bool
    {
        return true;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return true;
    }

    public function addItem(User $user, Contract $contract): bool
    {
        return true;
    }

    public function removeItem(User $user, Contract $contract): bool
    {
        return true;
    }

    public function cancel(User $user, Contract $contract): bool
    {
        return true;
    }

    public function viewHistory(User $user, Contract $contract): bool
    {
        return true;
    }
}
