<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $users = User::query()
            ->when($search, fn ($q, $term) => $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'search' => $search]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'client'])],
            'verified' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => $request->boolean('verified') ? now() : null,
        ]);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created.");
    }

    public function show(User $user)
    {
        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'client'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->has('verify_email') && $request->boolean('verify_email')) {
            $user->email_verified_at = $user->email_verified_at ?? now();
        }

        if ($request->has('unverify_email') && $request->boolean('unverify_email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} updated.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function impersonate(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot impersonate yourself.');
        }

        session()->put('impersonate', $user->id);
        session()->put('impersonating_as', auth()->id());

        return redirect()->route('dashboard')->with('impersonating', $user->name);
    }

    public function stopImpersonating()
    {
        abort_unless(session()->has('impersonate'), 403);

        $originalId = session()->pull('impersonating_as');
        session()->forget('impersonate');

        if ($originalId) {
            auth()->loginUsingId($originalId);
        }

        return redirect()->route('admin.users.index')->with('success', 'Stopped impersonating.');
    }
}
