<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManageUserRequest;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function users()
    {
        return view('users.index', ['users' => User::orderBy('name')->paginate(20)]);
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function store(ManageUserRequest $request)
    {
        $data = $request->validated();
        $user = new User;
        $user->forceFill(collect($data)->except('password_confirmation')->all())->save();
        Audit::record('user.created', "user:{$user->id}", ['role' => $user->role]);

        return redirect()->route('users.index')->with('status', 'Usuario creado. Entregue la contraseña por un canal seguro.');
    }

    public function update(ManageUserRequest $request, User $user)
    {
        $data = collect($request->validated())->except('password_confirmation')->all();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        DB::transaction(function () use ($user, $data, $request) {
            User::where('role', 'administrador')->orderBy('id')->lockForUpdate()->get();
            if ($user->id === $request->user()->id && (! $data['active'] || $data['role'] !== 'administrador')) {
                throw ValidationException::withMessages(['role' => 'No puede suspenderse ni quitarse su propio acceso de administrador.']);
            }
            $user->forceFill($data);
            if (array_key_exists('password', $data)) {
                $user->remember_token = null;
            }
            $user->save();
            Audit::record('user.updated', "user:{$user->id}", ['role' => $user->role, 'active' => $user->active]);
        });

        return redirect()->route('users.index')->with('status', 'Usuario actualizado.');
    }

    public function profile()
    {
        return view('profile');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(12)],
        ]);
        $user = $request->user();
        if ($user->email !== $data['email']) {
            $user->email_verified_at = null;
        }
        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->remember_token = null;
        }
        $user->save();
        $request->session()->regenerate();
        Audit::record('profile.updated', "user:{$user->id}");

        return back()->with('status', 'Perfil actualizado.');
    }

    public function audit()
    {
        return view('audit', ['events' => DB::table('audit_events')->leftJoin('users', 'users.id', '=', 'audit_events.user_id')->select('audit_events.*', 'users.email')->orderByDesc('audit_events.id')->paginate(30)]);
    }
}
