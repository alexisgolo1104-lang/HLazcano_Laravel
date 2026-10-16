<?php

namespace App\Http\Controllers;

use App\Models\PdfModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PdfController extends Controller
{
    public function ticket(int $id): View
    {
        $usuario = Auth::user();
        $ticket = (new PdfModel())->getTicketCompleto(
            $id,
            $usuario->rol === 'superadmin' ? null : (int) $usuario->id_tienda
        );

        abort_if(! $ticket, 404);

        return view('panel.ticket', $ticket);
    }
}
