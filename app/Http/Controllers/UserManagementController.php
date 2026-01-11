<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    private const ROLES = ['admin', 'rd', 'pur', 'sc'];

    /**
     * Display the user management view.
     */
    public function index(): Response
    {
        $users = User::query()
            ->orderBy('user_login')
            ->get(['user_login', 'employee_name', 'role', 'is_active']);

        return Inertia::render('Admin/UserManagement', [
            'users' => $users,
            'roles' => self::ROLES,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'max:255'],
            'user_login' => ['required', 'string', 'max:255', 'unique:proj1_user,user_login'],
            'role' => ['required', Rule::in(self::ROLES)],
            'is_active' => ['required', 'boolean'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'employee_name' => $validated['employee_name'],
            'user_login' => $validated['user_login'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }

    /**
     * Update user profile details.
     */
    public function update(Request $request, string $user_login): RedirectResponse
    {
        $user = User::where('user_login', $user_login)->firstOrFail();

        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(self::ROLES)],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->user()->user_login === $user_login && ! $validated['is_active']) {
            return back()->withErrors([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }

        if ($request->user()->user_login === $user_login && $validated['role'] !== $user->role) {
            return back()->withErrors([
                'role' => 'You cannot change your own role.',
            ]);
        }

        $user->employee_name = $validated['employee_name'];
        $user->role = $validated['role'];
        $user->is_active = $validated['is_active'];
        $user->save();

        return back();
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, string $user_login): RedirectResponse
    {
        $user = User::where('user_login', $user_login)->firstOrFail();

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return back();
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, string $user_login): RedirectResponse
    {
        if ($request->user()->user_login === $user_login) {
            return back()->withErrors([
                'user_login' => 'You cannot delete your own account.',
            ]);
        }

        $user = User::where('user_login', $user_login)->firstOrFail();
        $user->delete();

        return back();
    }
}
