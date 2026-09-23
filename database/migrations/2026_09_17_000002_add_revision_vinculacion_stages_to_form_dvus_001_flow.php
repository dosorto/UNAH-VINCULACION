<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Asegura que el flujo del FORM-DVUS-001 incluya las etapas de Vinculación.
 *
 * - Si no existe un flujo activo con codigo_formulario FORM-DVUS-001, lo crea
 *   con el recorrido completo: Enlace Vinculación, Jefe de Departamento,
 *   Director de centro, Revisor de Vinculación y Director de Vinculación.
 * - Si existe, solo agrega Revisor y Director de Vinculación cuando falten,
 *   al final y sin tocar las etapas que ya se configuraron a mano.
 *
 * Las etapas nuevas se envían a todos los usuarios del rol
 * (requiere_asignacion = false) para que el flujo funcione sin más
 * configuración; el responsable fijo se puede definir después en
 * Configuración → Flujos. No afecta a los expedientes ya enviados, que
 * conservan su recorrido.
 *
 * Esta migración se ejecutó en algunas bases el 17 de septiembre de 2026 sin
 * llegar al repositorio; se restituye con el mismo nombre para que las bases
 * que ya la registraron no la repitan. Corre antes de la que añade
 * configuracion_vigente, así que no puede darla por supuesta.
 */
return new class extends Migration
{
    private const FORMULARIO = 'FORM-DVUS-001';

    /** Recorrido completo, para cuando el flujo no existe. */
    private const ETAPAS = [
        ['nombre' => 'Enlace Vinculacion', 'rol' => 'Enlace Vinculacion', 'cargo' => 'Enlace Vinculacion', 'tipo' => 'APROBACION', 'intermedio' => false, 'cierre' => false],
        ['nombre' => 'Jefe Departamento', 'rol' => 'Jefe Departamento', 'cargo' => 'Jefe Departamento', 'tipo' => 'APROBACION', 'intermedio' => false, 'cierre' => false],
        ['nombre' => 'Director centro', 'rol' => 'Director centro', 'cargo' => 'Director centro', 'tipo' => 'APROBACION', 'intermedio' => false, 'cierre' => false],
        ['nombre' => 'Revisor Vinculacion', 'rol' => 'Revisor Vinculacion', 'cargo' => 'Revisor Vinculacion', 'tipo' => 'REVISION', 'intermedio' => false, 'cierre' => false],
        // Cada etapa con su propio cargo: si dos comparten cargo, un expediente
        // heredado en ese estado encaja en ambas y la adopción no puede
        // elegir en cuál continuar.
        ['nombre' => 'Director Vinculacion', 'rol' => 'Director Vinculacion', 'cargo' => 'Director Vinculacion', 'tipo' => 'REVISION', 'intermedio' => true, 'cierre' => true],
    ];

    /** Las etapas de Vinculación que se agregan a un flujo ya existente. */
    private const ETAPAS_VINCULACION = ['Revisor Vinculacion', 'Director Vinculacion'];

    public function up(): void
    {
        $flujo = DB::table('flujos_aprobacion')
            ->where('proceso', 'PROYECTO')
            ->where('codigo_formulario', self::FORMULARIO)
            ->where('activo', true)
            ->orderBy('id')
            ->first();

        if (! $flujo) {
            $this->crearFlujo();

            return;
        }

        $vigentes = DB::table('flujos_aprobacion_etapas as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.rol_revisor_id')
            ->where('e.flujo_aprobacion_id', $flujo->id)
            ->when($this->tieneVigencia(), fn ($query) => $query->where('e.configuracion_vigente', true))
            ->get(['e.orden', 'e.codigo', 'e.nombre', 'r.name as rol']);

        foreach (self::ETAPAS as $etapa) {
            if (! in_array($etapa['nombre'], self::ETAPAS_VINCULACION, true)) {
                continue;
            }

            // Ya está si alguna etapa vigente atiende ese rol o se llama igual.
            $existe = $vigentes->contains(fn ($vigente): bool => $vigente->rol === $etapa['rol']
                || mb_strtolower(trim((string) $vigente->nombre)) === mb_strtolower($etapa['nombre']));

            if ($existe) {
                continue;
            }

            $orden = (int) $vigentes->max('orden') + 1;
            $insertada = $this->insertarEtapa((int) $flujo->id, $orden, $etapa, $vigentes->pluck('codigo')->all());

            if ($insertada) {
                $vigentes->push((object) ['orden' => $orden, 'codigo' => $insertada, 'nombre' => $etapa['nombre'], 'rol' => $etapa['rol']]);
            }
        }
    }

    public function down(): void
    {
        // Sin reversión: las etapas pueden tener firmas y expedientes enviados
        // que dependen de ellas. Se retiran desde Configuración → Flujos.
    }

    private function crearFlujo(): void
    {
        $tipoAccion = DB::table('vinculacion_tipos_accion')->where('codigo', 'DESARROLLO_LOCAL_REGIONAL')->value('id');

        if (! $tipoAccion) {
            return;
        }

        $flujoId = DB::table('flujos_aprobacion')->insertGetId([
            'codigo' => $this->codigoFlujoLibre('PROYECTO_FORM_DVUS_001'),
            'nombre' => 'Flujo FORM-DVUS-001 - Desarrollo local y regional',
            'proceso' => 'PROYECTO',
            'codigo_formulario' => self::FORMULARIO,
            'tipo_accion_id' => $tipoAccion,
            'descripcion' => 'Flujo configurable para el FORM-DVUS-001.',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $codigos = [];

        foreach (self::ETAPAS as $indice => $etapa) {
            $codigo = $this->insertarEtapa($flujoId, $indice + 1, $etapa, $codigos);

            if ($codigo) {
                $codigos[] = $codigo;
            }
        }
    }

    /**
     * @param  list<string>  $codigosUsados
     * @return string|null código de la etapa creada, o null si falta su cargo
     */
    private function insertarEtapa(int $flujoId, int $orden, array $etapa, array $codigosUsados): ?string
    {
        $cargo = DB::table('cargo_firma')
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('tipo_cargo_firma.nombre', $etapa['cargo'])
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->whereNull('cargo_firma.deleted_at')
            ->orderBy('cargo_firma.id')
            ->value('cargo_firma.id');

        if (! $cargo) {
            return null;
        }

        $codigo = sprintf('ETAPA_%02d', $orden);
        $sufijo = 1;

        while (in_array($codigo, $codigosUsados, true)) {
            $codigo = sprintf('ETAPA_%02d_%d', $orden, ++$sufijo);
        }

        $fila = [
            'flujo_aprobacion_id' => $flujoId,
            'orden' => $orden,
            'codigo' => $codigo,
            'nombre' => $etapa['nombre'],
            'tipo_etapa' => $etapa['tipo'],
            'aplica_inscripcion' => true,
            'aplica_informe_intermedio' => $etapa['intermedio'],
            'aplica_cierre_proyecto' => $etapa['cierre'],
            'cargo_firma_id' => $cargo,
            'rol_revisor_id' => DB::table('roles')->where('name', $etapa['rol'])->where('guard_name', 'web')->value('id'),
            'usuario_responsable_id' => null,
            'requiere_asignacion' => false,
            'emisor_define_destinatario' => false,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($this->tieneVigencia()) {
            $fila['configuracion_vigente'] = true;
        }

        DB::table('flujos_aprobacion_etapas')->insert($fila);

        return $codigo;
    }

    private function codigoFlujoLibre(string $base): string
    {
        $codigo = $base;
        $sufijo = 1;

        while (DB::table('flujos_aprobacion')->where('codigo', $codigo)->exists()) {
            $codigo = $base.'_'.(++$sufijo);
        }

        return $codigo;
    }

    private function tieneVigencia(): bool
    {
        return Schema::hasColumn('flujos_aprobacion_etapas', 'configuracion_vigente');
    }
};
