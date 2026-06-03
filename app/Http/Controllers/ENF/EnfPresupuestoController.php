<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Http\Requests\ENF\StoreEnfPresupuestoRequest;
use App\Models\ENF\EnfPresupuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnfPresupuestoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EnfPresupuesto::with(['accion', 'detalles'])->latest()->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Formulario de presupuesto ENF pendiente de interfaz.']);
    }

    public function store(StoreEnfPresupuestoRequest $request): JsonResponse
    {
        return response()->json(EnfPresupuesto::create($request->validated()), 201);
    }

    public function show(int $presupuesto): JsonResponse
    {
        return response()->json(EnfPresupuesto::with(['accion', 'detalles'])->findOrFail($presupuesto));
    }

    public function update(Request $request, int $presupuesto): JsonResponse
    {
        $record = EnfPresupuesto::findOrFail($presupuesto);
        $record->update($request->validate((new StoreEnfPresupuestoRequest())->rules()));

        return response()->json($record->fresh());
    }

    public function edit(int $presupuesto): JsonResponse
    {
        return $this->show($presupuesto);
    }

    public function destroy(int $presupuesto): JsonResponse
    {
        EnfPresupuesto::findOrFail($presupuesto)->delete();

        return response()->json(status: 204);
    }
}
