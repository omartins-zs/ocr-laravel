<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    private function roleValue(User $user): string
    {
        return $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Document $document): bool
    {
        $role = $this->roleValue($user);

        if (in_array($role, [UserRole::Admin->value, UserRole::Manager->value, UserRole::Viewer->value], true)) {
            return true;
        }

        return $document->uploaded_by === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($this->roleValue($user), [UserRole::Admin->value, UserRole::Manager->value, UserRole::Operator->value], true);
    }

    public function update(User $user, Document $document): bool
    {
        if (in_array($this->roleValue($user), [UserRole::Admin->value, UserRole::Manager->value], true)) {
            return true;
        }

        return $document->uploaded_by === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return in_array($this->roleValue($user), [UserRole::Admin->value, UserRole::Manager->value], true);
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->roleValue($user) === UserRole::Admin->value;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $this->roleValue($user) === UserRole::Admin->value;
    }

    public function reprocess(User $user, Document $document): bool
    {
        return in_array($this->roleValue($user), [UserRole::Admin->value, UserRole::Manager->value, UserRole::Operator->value], true);
    }
}
