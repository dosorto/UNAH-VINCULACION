<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $odsId = DB::table('ods')
            ->where('nombre', 'like', '12%Producción y consumo responsables')
            ->value('id');

        if (! $odsId) {
            return;
        }

        DB::table('ods')
            ->where('id', $odsId)
            ->update([
                'nombre' => '12. Producción y consumo responsables',
                'updated_at' => now(),
            ]);

        $metas = [
            '12.1' => 'Aplicar el Marco Decenal de Programas sobre Modalidades de Consumo y Producción Sostenibles, con la participación de todos los países y bajo el liderazgo de los países desarrollados, teniendo en cuenta el grado de desarrollo y las capacidades de los países en desarrollo',
            '12.2' => 'De aquí a 2030, lograr la gestión sostenible y el uso eficiente de los recursos naturales',
            '12.3' => 'De aquí a 2030, reducir a la mitad el desperdicio de alimentos per cápita mundial en la venta al por menor y a nivel de los consumidores y reducir las pérdidas de alimentos en las cadenas de producción y suministro, incluidas las pérdidas posteriores a la cosecha',
            '12.4' => 'De aquí a 2020, lograr la gestión ecológicamente racional de los productos químicos y de todos los desechos a lo largo de su ciclo de vida, de conformidad con los marcos internacionales convenidos, y reducir significativamente su liberación a la atmósfera, el agua y el suelo a fin de minimizar sus efectos adversos en la salud humana y el medio ambiente',
            '12.5' => 'De aquí a 2030, reducir considerablemente la generación de desechos mediante actividades de prevención, reducción, reciclado y reutilización',
            '12.6' => 'Alentar a las empresas, en especial las grandes empresas y las multinacionales, a adoptar prácticas sostenibles y a integrar la sostenibilidad en sus estrategias comerciales',
            '12.7' => 'Promover prácticas de adquisición pública que sean sostenibles, de conformidad con las políticas y prioridades nacionales',
            '12.8' => 'De aquí a 2030, asegurar que las personas de todo el mundo tengan la información y los conocimientos pertinentes para el desarrollo sostenible y los estilos de vida en armonía con la naturaleza',
            '12.a' => 'Ayudar a los países en desarrollo a fortalecer su capacidad científica y tecnológica para que puedan avanzar en el uso sostenible de los recursos naturales',
            '12.b' => 'Elaborar y aplicar instrumentos para vigilar los efectos en el desarrollo sostenible, a fin de lograr un turismo sostenible que cree puestos de trabajo y promueva la cultura y los productos locales',
            '12.c' => 'Racionalizar los subsidios ineficientes a los combustibles fósiles que fomentan el consumo antieconómico eliminando las distorsiones del mercado, de acuerdo con las circunstancias nacionales, incluso mediante la reestructuración de los sistemas tributarios y la eliminación gradual de los subsidios perjudiciales, cuando existan, para reflejar su impacto ambiental, teniendo plenamente en cuenta las necesidades y condiciones específicas de los países en desarrollo y minimizando los posibles efectos adversos en su desarrollo, de manera que se proteja a los pobres y a las comunidades afectadas',
        ];

        foreach ($metas as $numeroMeta => $descripcion) {
            DB::table('metas_contribuye')->updateOrInsert(
                [
                    'ods_id' => $odsId,
                    'numero_meta' => $numeroMeta,
                ],
                [
                    'descripcion' => $descripcion,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        // Se preservan las metas porque pueden estar asociadas a proyectos o acciones ENF.
    }
};
