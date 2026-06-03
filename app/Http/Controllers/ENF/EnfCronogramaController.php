<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Http\Requests\ENF\StoreEnfCronogramaRequest;
use App\Models\ENF\EnfCronograma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnfCronogramaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EnfCronograma::with(['accion', 'responsable'])->latest()->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Formulario de cronograma ENF pendiente de interfaz.']);
    }

    public function store(StoreEnfCronogramaRequest $request): JsonResponse
    {
        return response()->json(EnfCronograma::create($request->validated()), 201);
    }

    public function show(int $cronograma): JsonResponse
    {
        return response()->json(EnfCronograma::with(['accion', 'responsable'])->findOrFail($cronograma));
    }

    public function update(Request $request, int $cronograma): JsonResponse
    {
        $record = EnfCronograma::findOrFail($cronograma);
        $record->update($request->validate((new StoreEnfCronogramaRequest())->rules()));

        return response()->json($record->fresh());
    }

    public function edit(int $cronograma): JsonResponse
    {
        return $this->show($cronograma);
    }

    public function destroy(int $cronograma): JsonResponse
    {
        EnfCronograma::findOrFail($cronograma)->delete();

        return response()->json(status: 204);
    }
}
