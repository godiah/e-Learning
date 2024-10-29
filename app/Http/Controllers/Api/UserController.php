<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use AuthorizesRequests;
    
    public function assignRole(Request $request, User $user)
    {
        $this->authorize('create', User::class);
        $request->validate([
            'roleName' => 'required|string|exists:roles,name'
        ]);

        $role = Role::where('name', $request->roleName)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);

        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function getUserRoles(User $user)
    {
        return response()->json(['roles' => $user->roles]);
    }

    public function createAdmin(Request $request)
    {
        $this->authorize('create', User::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $user->roles()->attach($adminRole);

        return response()->json(['message' => 'Admin created successfully', 'user' => $user], 201);
    }
}
