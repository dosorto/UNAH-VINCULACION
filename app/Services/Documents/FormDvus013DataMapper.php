<?php

namespace App\Services\Documents;

use App\Models\Pasantia;

class FormDvus013DataMapper
{
    public function fill(DocxTemplateEditor $editor, Pasantia $r): void
    {
        $put = fn ($t, $row, $cell, $value) => $editor->setCell($t, $row, $cell, $value);
        foreach (['Y', 'm', 'd'] as $i => $format) $put(1, 2, $i + 2, $r->fecha_registro?->format($format));
        foreach (['facultad_centro','escuela_departamento','carrera','numero_cuenta','nombre_estudiante','celular_estudiante','correo_institucional','correo_personal'] as $i => $field) $put(1, $i + 3, 3, $r->{$field});
        $put(2, 1, 3, $r->tipo_pasantia === 'Nacional' ? 'X' : '');
        $put(2, 1, 5, $r->tipo_pasantia === 'Internacional' ? 'X' : '');
        foreach (['fecha_inicio' => 2, 'fecha_finalizacion' => 5] as $field => $column) {
            foreach (['d', 'm', 'Y'] as $i => $format) $put(2, 4, $column + $i, $r->{$field}?->format($format));
        }
        foreach (['duracion_semanas','total_horas','horas_semanales'] as $i => $field) $put(2, $i + 5, 3, $r->{$field});
        foreach (['pasantia_obligatoria' => 9, 'otorga_creditos' => 11] as $field => $row) {
            $put(2, $row, 3, $r->{$field} === true ? 'X' : '');
            $put(2, $row, 4, $r->{$field} === false ? 'X' : '');
        }
        $put(2, 12, 3, $r->otorga_creditos ? $r->cantidad_creditos : '');
        foreach (['Presencial','100% virtual (teletrabajo)','Híbrida (presencial + teletrabajo)'] as $i => $label) $put(2, 14, $i + 2, $r->modalidad_ejecucion === $label ? 'X' : '');
        $put(2, 16, 3, $r->resumen_responsabilidades);
        $put(2, 17, 3, $r->area_departamento);
        // Fill the fixed rows first; extra subjects get complete rows, never truncated.
        $subjects = $r->asignaturas ?? (filled($r->codigo_asignatura) || filled($r->nombre_asignatura) ? [['codigo' => $r->codigo_asignatura, 'nombre' => $r->nombre_asignatura]] : []);
        $extra = max(0, count($subjects) - 3);
        for ($i = 0; $i < $extra; $i++) $editor->cloneRow(2, 21 + $i);
        for ($i = 0; $i < max(3, count($subjects)); $i++) {
            $put(2, 19 + $i, 3, $subjects[$i]['codigo'] ?? '');
            $put(2, 19 + $i, 4, $subjects[$i]['nombre'] ?? '');
        }
        $put(2, 22 + $extra, 3, $r->descripcion_conocimientos_teoricos);
        $put(2, 23 + $extra, 2, $r->habilidades_desarrollar);
        $put(2, 26 + $extra, 2, $r->pasantia_remunerada === true ? 'X' : '');
        $put(2, 26 + $extra, 3, $r->pasantia_remunerada === false ? 'X' : '');
        $put(2, 25 + $extra, 4, $r->pasantia_remunerada ? $r->monto_remuneracion : '');
        foreach ([
            [1,2,'Nombre completo: ', 'nombre_institucion'], [2,2,'Dirección de la sede principal: ', 'direccion_institucion'],
            [3,2,'Ciudad: ', 'ciudad_institucion'], [3,3,'País: ', 'pais_institucion'], [4,2,'Nombre completo del representante legal: ', 'representante_legal'],
            [5,2,'Número de teléfono: ', 'telefono_representante'], [5,3,'Correo electrónico recursos humanos: ', 'correo_rrhh'],
            [12,2,'Nombre completo: ', 'nombre_contacto_directo'], [13,2,'Número de celular: ', 'celular_contacto_directo'],
            [13,3,'Correo electrónico: ', 'correo_contacto_directo'], [14,2,'', 'cargo_contacto_directo'], [15,2,'', 'grado_academico_contacto_directo'],
            [18,2,'', 'compromisos_institucion'],
        ] as [$row,$cell,$label,$field]) $put(3,$row,$cell,$label.$r->{$field});
        foreach (['Gobierno Nacional','Gobierno Municipal','ONG','Sociedad civil organizada','Sector Privado','Internacional'] as $i => $label) $put(3,7,$i+2,$r->tipo_institucion === $label ? 'X' : '');
        foreach (['Agricultura, alimentación y silvicultura','Energía y minería','Producción','Sectores de servicios privados','Infraestructura, construcción y sectores relacionados','Educación e investigación','Servicios y función públicos','Transporte, transporte marítimo y aéreo'] as $i => $label) $put(3,8+intdiv($i,2),$i%2 === 0 ? 3 : 5,$r->sector_institucion === $label ? 'X' : '');
        foreach (['carta_formal_solicitud','carta_intenciones','convenio_marco'] as $i => $value) $put(3,17,$i+2,$r->tipo_instrumento === $value ? 'X' : '');
        foreach ([[1,2,'Nombre completo: ','nombre_docente_supervisor'],[2,2,'No. de empleado/a: ','numero_empleado_docente'],[2,3,'Número de celular: ','celular_docente'],[3,2,'Correo electrónico: ','correo_docente'],[4,2,'Categoría: ','categoria_docente'],[4,3,'Departamento al que pertenece: ','departamento_docente'],[5,2,'Jornada laboral: ','jornada_laboral_docente'],[5,3,'Ubicación del cubículo en la UNAH: ','ubicacion_cubiculo_docente']] as [$row,$cell,$label,$field]) $put(4,$row,$cell,$label.$r->{$field});
        foreach (['coordinador','supervisor','estudiante'] as $i => $role) {
            $put(5,2,$i+1,'Nombre: '.$r->{'nombre_firma_'.$role});
            $put(5,3,$i+1,'');
            $image = \App\Support\Fichas\FirmaImagen::resolver($r->{'firma_'.$role}, true);
            if (isset($image['path']) && is_file($image['path'])) $editor->setCellImage(5,3,$i+1,$image['path']);
        }
        foreach (['adjunta_carta_formalizacion','adjunta_convenio_marco'] as $i => $field) {
            $put(6,$i+2,3,$r->{$field} === true ? 'X' : '');
            $put(6,$i+2,4,$r->{$field} === false ? 'X' : '');
        }
    }
}
