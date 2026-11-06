<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Notifications\TemporaryPasswordNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'correo' => ['required', 'email', 'max:100'],
        ]);

        $usuario = Usuario::query()
            ->where('correo', $request->input('correo'))
            ->where('activo', true)
            ->first();

        try {
            if ($usuario) {
                $contrasenaTemporal = Str::password(16);

                DB::transaction(function () use ($usuario, $contrasenaTemporal): void {
                    $usuario->contrasena = Hash::make($contrasenaTemporal);
                    $usuario->debe_cambiar_contrasena = true;
                    $usuario->save();
                    $usuario->notify(new TemporaryPasswordNotification($contrasenaTemporal));
                });
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['correo' => 'No se pudo enviar el correo. Verifica la configuración de correo e inténtalo de nuevo.'])
                ->withInput();
        }

        return back()->with('status', 'Si el correo está asociado a una cuenta activa, recibirás una contraseña temporal. Deberás cambiarla al iniciar sesión.');
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
            ->get()
            ->first(fn (Usuario $usuario) => $usuario->nombre === $credentials['usuario']
                || mb_strtolower($usuario->correo) === mb_strtolower($credentials['usuario']));

        if (! $usuario || ! $this->passwordMatches($usuario, $credentials['contrasena'])) {
            throw ValidationException::withMessages([
                'usuario' => 'El usuario o la contraseña no coinciden.',
            ]);
        }

        if (! $usuario->activo) {
            throw ValidationException::withMessages([
                'usuario' => $usuario->rol === 'admin'
                    ? 'La cuenta de administrador está pendiente de aprobación o desactivada.'
                    : 'La cuenta está desactivada.',
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

        if ($usuario->debe_cambiar_contrasena) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contrasena_actual' => ['required', 'string'],
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $usuario = $request->user();

        if (! $this->passwordMatches($usuario, $data['contrasena_actual'])) {
            throw ValidationException::withMessages([
                'contrasena_actual' => 'La contraseña actual no es correcta.',
            ]);
        }

        $usuario->contrasena = Hash::make($data['contrasena']);
        $usuario->debe_cambiar_contrasena = false;
        $usuario->save();

        return redirect()->route('dashboard')->with('status', 'La contraseña fue actualizada.');
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
            'telefono' => ['nullable', 'string', 'max:20'],
            'contrasena' => ['required', 'string', 'min:6', 'confirmed'],
            'rol' => ['required', 'in:empleado,admin'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, espacios, guiones y apóstrofes.',
            'correo.unique' => 'Ya existe una cuenta con ese correo.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden.',
            'rol.in' => 'Selecciona Empleado o Administrador.',
        ]);

        $tienda = DB::table('tiendas')
            ->where('id_tienda', 1)
            ->where('activo', 1)
            ->value('id_tienda');

        if (! $tienda) {
            throw ValidationException::withMessages([
                'correo' => 'No hay una sucursal activa para asociar la cuenta.',
            ]);
        }

        Usuario::query()->create([
            'nombre' => $data['nombre'],
            'correo' => $data['correo'],
            'telefono' => $data['telefono'] ?? null,
            'contrasena' => Hash::make($data['contrasena']),
            'rol' => $data['rol'],
            'id_tienda' => $tienda,
            'activo' => $data['rol'] === 'admin' ? 0 : 1,
            'creado_en' => now()->toDateString(),
        ]);

        $status = $data['rol'] === 'admin'
            ? 'La solicitud de cuenta de administrador fue enviada. Un superadministrador debe aprobarla antes de que puedas iniciar sesión.'
            : 'La cuenta fue creada. Ya puedes iniciar sesión.';

        return redirect()->route('login')->with('status', $status);
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
