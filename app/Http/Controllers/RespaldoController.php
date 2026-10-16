<?php

namespace App\Http\Controllers;

use App\Models\LogRespaldo;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class RespaldoController extends Controller
{
    public function index(): View
    {
        $this->authorizeBackupAccess();

        $historial = DB::table('logs_respaldo as lr')
            ->join('usuarios as u', 'lr.id_usuario', '=', 'u.id_usuario')
            ->orderByDesc('lr.fecha_generacion')
            ->limit(50)
            ->get([
                'lr.fecha_generacion',
                'lr.nombre_archivo',
                'lr.tamano_bytes',
                'lr.tipo_operacion',
                'lr.exito',
                'lr.notas',
                'u.nombre as nombre_usuario',
            ]);
        $ultimo = DB::table('logs_respaldo')
            ->where('exito', true)
            ->orderByDesc('fecha_generacion')
            ->value('fecha_generacion');

        return view('panel.respaldos', compact('historial', 'ultimo'));
    }

    public function exportar(DatabaseBackupService $backupService): BinaryFileResponse
    {
        $this->authorizeBackupAccess();
        $usuario = Auth::user();

        try {
            $backup = $backupService->export();
        } catch (Throwable $exception) {
            try {
                LogRespaldo::query()->create([
                    'id_usuario' => $usuario->id_usuario,
                    'nombre_archivo' => 'ERROR',
                    'tamano_bytes' => 0,
                    'tipo_operacion' => 'exportacion',
                    'exito' => false,
                    'notas' => Str::limit($exception->getMessage(), 1000),
                ]);
            } catch (Throwable $loggingException) {
                report($loggingException);
            }
            throw $exception;
        }

        LogRespaldo::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nombre_archivo' => $backup['filename'],
            'tamano_bytes' => $backup['bytes'],
            'tipo_operacion' => 'exportacion',
            'exito' => true,
            'notas' => null,
        ]);

        return response()->download($backup['path'], $backup['filename'], [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    public function importar(Request $request, DatabaseBackupService $backupService): RedirectResponse
    {
        abort_unless(Auth::user()->rol === 'superadmin', 403);

        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'confirmacion' => ['required', 'in:RESTAURAR BASE DE DATOS'],
        ]);
        $usuario = Auth::user();

        try {
            $resultado = $backupService->restore($validated['archivo'], (int) $usuario->id_usuario);
        } catch (Throwable $exception) {
            try {
                LogRespaldo::query()->create([
                    'id_usuario' => $usuario->id_usuario,
                    'nombre_archivo' => $validated['archivo']->getClientOriginalName(),
                    'tamano_bytes' => $validated['archivo']->getSize(),
                    'tipo_operacion' => 'restauracion',
                    'exito' => false,
                    'notas' => Str::limit($exception->getMessage(), 1000),
                ]);
            } catch (Throwable $loggingException) {
                report($loggingException);
            }
            throw $exception;
        }

        LogRespaldo::query()->create([
            'id_usuario' => $usuario->id_usuario,
            'nombre_archivo' => $validated['archivo']->getClientOriginalName(),
            'tamano_bytes' => $validated['archivo']->getSize(),
            'tipo_operacion' => 'restauracion',
            'exito' => true,
            'notas' => "Restauradas {$resultado['tables']} tablas en {$resultado['database']}.",
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'La base de datos fue restaurada. Inicia sesión nuevamente.');
    }

    private function authorizeBackupAccess(): void
    {
        abort_unless(in_array(Auth::user()->rol, ['admin', 'superadmin'], true), 403);
    }
}
