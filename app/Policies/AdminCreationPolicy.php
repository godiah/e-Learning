<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminCreationPolicy
{
    use HandlesAuthorization;
    /**
     * Create a new policy instance.
     */


    public function create(User $user)
    {
        return $user->hasRole('admin');
    }
    
    public function __construct()
    {
        //
    }
    
}
