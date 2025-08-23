<?php

namespace App\Policies;

use App\Models\SoapNote;
use App\Models\User;

class SoapNotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SoapNote $soapNote): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SoapNote $soapNote): bool
    {
        return $user->is_admin || ($user->id === $soapNote->author_id && $soapNote->state === 'draft');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SoapNote $soapNote): bool
    {
        return $user->is_admin || ($user->id === $soapNote->author_id && $soapNote->state === 'draft');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SoapNote $soapNote): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SoapNote $soapNote): bool
    {
        return false;
    }

    public function finalize(User $user, SoapNote $soapNote): bool
    {
        return $user->is_admin || ($user->id === $soapNote->author_id && $soapNote->state === 'draft');
    }
}
