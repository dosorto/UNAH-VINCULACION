<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Http\Requests\ENF\StoreEnfInformeFinalRequest;
use App\Models\ENF\EnfInformeFinal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnfInformeFinalController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EnfInformeFinal::with(['accion', 'aprobadoPor'])->latest()->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Formulario de informe final ENF pendiente de interfaz.']);
    }

    public function store(StoreEnfInformeFinalRequest $request): JsonResponse
    {
        return response()->json(EnfInformeFinal::create($request->validated()), 201);
    }

    public function show(int $informeFinal): JsonResponse
    {
        return response()->json(
            EnfInformeFinal::with(['accion', 'participantesFinales', 'accionesEjecutadas', 'accionesNoEjecutadas'])
                ->findOrFail($informeFinal)
        );
    }

    public function update(Request $request, int $informeFinal): JsonResponse
    {
        $record = EnfInformeFinal::findOrFail($informeFinal);
        $record->update($request->validate((new StoreEnfInformeFinalRequest())->rules()));

        return response()->json($record->fresh());
    }

    public function edit(int $informeFinal): JsonResponse
    {
        return $this->show($informeFinal);
    }

    public function destroy(int $informeFinal): JsonResponse
    {
        EnfInformeFinal::findOrFail($informeFinal)->delete();

        return response()->json(status: 204);
    }
}
