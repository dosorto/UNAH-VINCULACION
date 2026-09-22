<?php

use App\Support\InformeFinal\ConceptosPresupuestoInf001;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TIPOS_ANEXO = ['materiales', 'encuestas', 'procesamiento', 'fotografias', 'videos', 'difusion', 'asistencia', 'manuales', 'guias', 'actas', 'otros'];

    /**
     * Alinea el INF-001 con el formato oficial:
     * - Apartado X: cada fila del presupuesto se identifica por su concepto oficial
     *   (`concepto_codigo`); `origen_fondos` queda solo para la «Descripción del origen
     *   de los fondos» del formato.
     * - Apartado VI: cada actividad realizada se asocia al resultado al que contribuye.
     * - Apartado III: la bitácora de cada estudiante es un tipo de anexo propio.
     */
    public function up(): void
    {
        Schema::table('informe_final_presupuesto_detalles', function (Blueprint $table) {
            $table->string('concepto_codigo', 60)->nullable()->after('fuente');
        });

        Schema::table('informe_final_actividades', function (Blueprint $table) {
            $table->foreignId('informe_final_resultado_id')->nullable()->after('actividad_id')
                ->constrained('informe_final_resultados', indexName: 'inf_actividad_resultado_fk')->nullOnDelete();
        });

        Schema::table('informe_final_anexos', function (Blueprint $table) {
            $table->enum('tipo', [...self::TIPOS_ANEXO, 'bitacoras'])->change();
        });

        $editables = DB::table('informe_final_proyectos')->whereIn('estado', ['BORRADOR', 'RECHAZADO'])->pluck('id')->all();

        foreach (DB::table('informe_final_presupuesto_detalles')->orderBy('id')->get() as $fila) {
            $esEditable = in_array($fila->informe_final_proyecto_id, $editables, true);
            $cambios = [];

            if ($fila->origen_fondos === 'contrapartes_proyecto') {
                // Suma de los aportes que se capturaban por contraparte: en los borradores
                // pasa a «g) Otros gastos» para que se desglose por concepto sin perder el monto.
                if ($esEditable) {
                    $cambios = [
                        'concepto_codigo' => 'otros_gastos',
                        'concepto' => ConceptosPresupuestoInf001::etiqueta('CONTRAPARTE', 'otros_gastos'),
                        'unidad' => 'Global',
                        'origen_fondos' => null,
                    ];
                }
            } else {
                $codigo = ConceptosPresupuestoInf001::codigoDesdeTexto($fila->fuente, $fila->concepto);

                if ($esEditable && $fila->fuente === 'UNAH' && ConceptosPresupuestoInf001::esIndirecto($codigo)) {
                    // k) y l) ahora se calculan (3% de a–b); la fila guardada ya no aplica.
                    DB::table('informe_final_presupuesto_detalles')->where('id', $fila->id)->delete();
                    continue;
                }

                $cambios['concepto_codigo'] = $codigo;
                if ($fila->origen_fondos === 'registro_proyecto') {
                    $cambios['origen_fondos'] = null;
                }
            }

            if ($cambios !== []) {
                DB::table('informe_final_presupuesto_detalles')->where('id', $fila->id)->update($cambios);
            }
        }
    }

    public function down(): void
    {
        DB::table('informe_final_anexos')->where('tipo', 'bitacoras')->update(['tipo' => 'otros']);

        Schema::table('informe_final_anexos', function (Blueprint $table) {
            $table->enum('tipo', self::TIPOS_ANEXO)->change();
        });

        Schema::table('informe_final_actividades', function (Blueprint $table) {
            $table->dropForeign('inf_actividad_resultado_fk');
            $table->dropColumn('informe_final_resultado_id');
        });

        Schema::table('informe_final_presupuesto_detalles', function (Blueprint $table) {
            $table->dropColumn('concepto_codigo');
        });
    }
};
