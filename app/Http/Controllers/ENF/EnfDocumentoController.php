<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Models\ENF\EnfDocumento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnfDocumentoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EnfDocumento::with(['accion', 'subidoPor'])->latest()->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Formulario de documentos ENF pendiente de interfaz.']);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enf_accion_id' => ['required', 'exists:enf_acciones,id'],
            'tipo_documento' => ['nullable', 'string', 'max:120'],
            'nombre' => ['required', 'string', 'max:220'],
            'ruta' => ['required', 'string', 'max:500'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'tamano_bytes' => ['nullable', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string'],
        ]);
        $data['subido_por_usuario_id'] = $request->user()?->id;

        return response()->json(EnfDocumento::create($data), 201);
    }

    public function show(int $documento): JsonResponse
    {
        return response()->json(EnfDocumento::with(['accion', 'subidoPor', 'firmas'])->findOrFail($documento));
    }

    public function update(Request $request, int $documento): JsonResponse
    {
        $record = EnfDocumento::findOrFail($documento);
        $record->update($request->validate([
            'enf_accion_id' => ['sometimes', 'required', 'exists:enf_acciones,id'],
            'tipo_documento' => ['nullable', 'string', 'max:120'],
            'nombre' => ['sometimes', 'required', 'string', 'max:220'],
            'ruta' => ['sometimes', 'required', 'string', 'max:500'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'tamano_bytes' => ['nullable', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string'],
        ]));

        return response()->json($record->fresh());
    }

    public function edit(int $documento): JsonResponse
    {
        return $this->show($documento);
    }

    public function destroy(int $documento): JsonResponse
    {
        EnfDocumento::findOrFail($documento)->delete();

        return response()->json(status: 204);
    }
}
