<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'usuario' => ['required', 'string', 'max:120'],
            'contrasena' => ['required', 'string'],
        ]);

        $usuario = Usuario::query()
            ->where('correo', $credentials['usuario'])
            ->orWhere('nombre', $credentials['usuario'])
            ->first();

        if (! $usuario || ! $usuario->activo || ! $this->passwordMatches($usuario, $credentials['contrasena'])) {
            throw ValidationException::withMessages([
                'usuario' => 'El usuario o la contraseña no coinciden.',
            ]);
        }

        if (! $this->isHashedPassword($usuario->contrasena)) {
            $usuario->contrasena = Hash::make($credentials['contrasena']);
        }
        $usuario->ultimo_acceso = now()->toDateString();
        $usuario->ip_ultimo = $request->ip();
        $usuario->save();

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:120',
                'regex:/\A[\p{L}\p{M}]+(?:[ \'-][\p{L}\p{M}]+)*\z/u',
            ],
            'correo' => ['required', 'string', 'email', 'max:100', 'unique:usuarios,correo'],
            'contrasena' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, espacios, guiones y apóstrofes.',
            'correo.unique' => 'Ya existe una cuenta con ese correo.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $tienda = DB::table('tiendas')
            ->where('activo', 1)
            ->orderBy('id_tienda')
            ->value('id_tienda');

        if (! $tienda) {
            throw ValidationException::withMessages([
                'correo' => 'No hay sucursales activas para asociar la cuenta.',
            ]);
        }

        Usuario::query()->create([
            'nombre' => $data['nombre'],
            'correo' => $data['correo'],
            'contrasena' => Hash::make($data['contrasena']),
            'rol' => 'empleado',
            'id_tienda' => $tienda,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        return redirect()->route('login')->with('status', 'La cuenta fue creada. Ya puedes iniciar sesión.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function passwordMatches(Usuario $usuario, string $password): bool
    {
        $stored = (string) $usuario->contrasena;

        if ($this->isHashedPassword($stored)) {
            return Hash::check($password, $stored);
        }

        return hash_equals($stored, $password);
    }

    private function isHashedPassword(string $password): bool
    {
        return str_starts_with($password, '$2y$')
            || str_starts_with($password, '$argon2i$')
            || str_starts_with($password, '$argon2id$');
    }
}
