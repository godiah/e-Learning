<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminCreationPolicy
{
    use HandlesAuthorization;
    /**
     * ONLY SuperAdmin.
     */
    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can assign roles.
     */
    public function assignRole(User $user)
    {
        // Allow only users with 'superadmin' or 'admin-user-mgt' roles
        return $user->hasRole('admin') || $user->hasRole('admin-user-mgt');
    }
    
    public function __construct()
    {
        //
    }
    
}
