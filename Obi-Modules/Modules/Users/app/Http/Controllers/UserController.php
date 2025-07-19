<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\app\Http\BaseApiController;
use Modules\Users\app\Http\Requests\StoreUserRequest;
use Modules\Users\app\Http\Requests\UpdateUserRequest;
use Modules\Users\Models\User;

class UserController extends BaseApiController
{

    public function index()
    {
        $users = User::all();

        return $this->success($users, 'Listado de usuarios');
    }

    public function show(User $user)
    {
        return $this->success($user, 'Usuario obtenido correctamente');
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        // Generar username único compuesto por la primera letra del nombre + el apellido
        // o si ya existe se usa primera y segunda letra del nombre + apellido
        $usernameBase = Str::lower(
            Str::substr($validated['name'], 0, 1) .
            Str::slug($validated['lastname'], '')
        );
        $username = $usernameBase;
        $extra    = 1;

        while (User::where('username', $username)->exists()) {
            $username = Str::lower(
                Str::substr($validated['name'], 0, ++$extra) .
                Str::slug($validated['lastname'], '')
            );

            if ($extra >= Str::length($validated['name'])) {
                $suffix   = User::where('username', 'like', "{$usernameBase}%")->count();
                $username = "{$usernameBase}{$suffix}";
                break;
            }
        }

        // Password: username + año actual (hash)
        $plainPassword = $username . now()->year;
        $passwordHash  = Hash::make($plainPassword);

        // Crear usuario
        $user = User::create([
            'name'      => $validated['name'],
            'lastname'  => $validated['lastname'],
            'dni'       => $validated['dni'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'],
            'gender'    => $validated['gender'],
            'role_id'   => $validated['role_id'],
            'username'  => $username,
            'password'  => $passwordHash,
            'status_id' => 1,
        ]);

        return $this->success($user, 'Usuario creado correctamente', 201);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return $this->success($user, 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->success(null, 'Usuario eliminado correctamente', 200);
    }

    public function disable(User $user)
    {
        $user->status_id = 3;
        $user->save();

        return $this->success($user, 'Usuario deshabilitado correctamente');
    }
}
