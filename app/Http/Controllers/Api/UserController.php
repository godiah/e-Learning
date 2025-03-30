<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Mail\AdminCreatedMail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // Authorization check - only admins can access this endpoint
        // $request->user()->authorizeRoles(['admin', 'admin-user-mgt']);

        // Get query parameters for relationships
        $relations = $request->query('include', []);

        if (!is_array($relations)) {
            $relations = explode(',', $relations);
        }

        // Validate allowed relations
        $allowedRelations = [
            'courses',
            'enrollments',
            'lessonProgress',
            'reviews',
            'discussions',
            'discussionReplies',
            'instructorApplication',
            'affiliateApplication',
            'roles'
        ];

        $relations = array_intersect($relations, $allowedRelations);

        // Get users with pagination
        $users = User::with($relations)
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function assignRole(Request $request, User $user)
    {
        $this->authorize('assignRole', $user);

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
        // Authorize the action to ensure only a SuperAdmin can create an Admin
        $this->authorize('create', User::class);

        // Fetch the available admin roles
        $roles = Role::whereIn('name', [
            'admin-user-mgt',
            'admin-content-mgt',
            'admin-financial-mgt'
        ])->pluck('name');

        // Validate request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|in:' . $roles->implode(','),
        ]);

        // Generate a random password
        $randomPassword = Str::random(12);

        // Create the new Admin user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($randomPassword),
        ]);

        // Assign the specified role to the new admin
        $assignedRole = Role::where('name', $validatedData['role'])->firstOrFail();
        $user->roles()->attach($assignedRole);

        // Send an email to the new admin with their credentials
        Mail::to($user->email)->send(new AdminCreatedMail([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $assignedRole->name,
            'password' => $randomPassword,
        ]));

        // Return a success response
        return response()->json([
            'message' => 'Admin created successfully',
            'user' => $user,
            'assigned_role' => $assignedRole->name,
        ], 201);
    }

    public function createRole(Request $request)
    {
        $this->authorize('create', User::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $newRole = Role::create([
            'name' => $validatedData['name'],
        ]);

        return response()->json(['message' => 'New Role created successfuly.'], 201);
    }
}
