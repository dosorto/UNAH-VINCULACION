<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Http\Requests\ENF\StoreEnfSistematizacionRequest;
use App\Models\ENF\EnfSistematizacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnfSistematizacionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EnfSistematizacion::with(['accion', 'informeFinal'])->latest()->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Formulario de sistematizacion ENF pendiente de interfaz.']);
    }

    public function store(StoreEnfSistematizacionRequest $request): JsonResponse
    {
        return response()->json(EnfSistematizacion::create($request->validated()), 201);
    }

    public function show(int $sistematizacion): JsonResponse
    {
        return response()->json(
            EnfSistematizacion::with(['accion', 'informeFinal', 'documentos', 'fases'])
                ->findOrFail($sistematizacion)
        );
    }

    public function update(Request $request, int $sistematizacion): JsonResponse
    {
        $record = EnfSistematizacion::findOrFail($sistematizacion);
        $record->update($request->validate((new StoreEnfSistematizacionRequest())->rules()));

        return response()->json($record->fresh());
    }

    public function edit(int $sistematizacion): JsonResponse
    {
        return $this->show($sistematizacion);
    }

    public function destroy(int $sistematizacion): JsonResponse
    {
        EnfSistematizacion::findOrFail($sistematizacion)->delete();

        return response()->json(status: 204);
    }
}
