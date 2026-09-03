<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Policy;
use Illuminate\Auth\Access\HandlesAuthorization;

class PolicyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Policy');
    }

    public function view(AuthUser $authUser, Policy $policy): bool
    {
        return $authUser->can('View:Policy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Policy');
    }

    public function update(AuthUser $authUser, Policy $policy): bool
    {
        return $authUser->can('Update:Policy');
    }

    public function delete(AuthUser $authUser, Policy $policy): bool
    {
        return $authUser->can('Delete:Policy');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Policy');
    }

    public function restore(AuthUser $authUser, Policy $policy): bool
    {
        return $authUser->can('Restore:Policy');
    }

    public function forceDelete(AuthUser $authUser, Policy $policy): bool
    {
        return $authUser->can('ForceDelete:Policy');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Policy');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Policy');
    }

    public function replicate(AuthUser $authUser, Policy $policy): bool
    {
        return $authUser->can('Replicate:Policy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Policy');
    }

}