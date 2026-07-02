<?php

namespace App\Http\Controllers;

use App\Models\Pass;
use App\Services\ApplePassBuilder;
use App\Services\GoogleWalletService;
use Illuminate\Http\Request;

class PassController extends Controller
{
    /** Página pública del pase: se abre en el teléfono del cliente con ambos botones. */
    public function show(Pass $pass)
    {
        $pass->load('client.business');

        return view('pass.show', [
            'pass'           => $pass,
            'appleReady'     => ApplePassBuilder::make()->isConfigured(),
            'googleReady'    => GoogleWalletService::make()->isConfigured(),
        ]);
    }

    /** Descarga el .pkpass (Apple Wallet lo abre automáticamente en iPhone). */
    public function apple(Pass $pass)
    {
        try {
            $binary = ApplePassBuilder::make()->build($pass);
        } catch (\RuntimeException $e) {
            return back()->with('ok', 'Apple Wallet: ' . $e->getMessage());
        }

        return response($binary, 200, [
            'Content-Type'        => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="' . $pass->serial_number . '.pkpass"',
        ]);
    }

    /** Redirige al enlace "Add to Google Wallet". */
    public function google(Pass $pass)
    {
        try {
            $link = GoogleWalletService::make()->saveLink($pass);
        } catch (\RuntimeException $e) {
            return back()->with('ok', 'Google Wallet: ' . $e->getMessage());
        }

        return redirect()->away($link);
    }
}
