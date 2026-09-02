<?php

namespace Tests\Feature;

use Tests\TestCase;

class Form018WordParityTest extends TestCase
{
    public function test_formulario_de_captura_conserva_solo_los_campos_del_form_018(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));

        $this->assertStringNotContainsString('name="departamento_id"', $vista);
        $this->assertStringNotContainsString('name="municipio_id"', $vista);
        $this->assertStringNotContainsString('name="beneficiarios[hombres]"', $vista);
        $this->assertStringNotContainsString('name="beneficiarios[mujeres]"', $vista);
        $this->assertStringNotContainsString('name="descripcion_participantes"', $vista);
        $this->assertStringNotContainsString('name="metodologia"', $vista);
        $this->assertStringNotContainsString('name="bibliografia"', $vista);
        $this->assertStringNotContainsString('name="eje_unah_ids[]"', $vista);
        $this->assertStringNotContainsString('name="modalidad_id"', $vista);
        $this->assertStringNotContainsString('name="contraparte[rtn]"', $vista);

        $this->assertStringContainsString('name="beneficiarios[total]"', $vista);
        $this->assertStringContainsString("'Profesores x hora'", $vista);
        $this->assertStringContainsString("'Administrativo'", $vista);
        $this->assertStringContainsString("'Servicios'", $vista);
        $this->assertStringContainsString("'aporte_unah'", $vista);
        $this->assertStringContainsString("'cantidad' => 6", $vista);
        $this->assertSame(2, substr_count($vista, "'cantidad' => 5"));
        $this->assertStringContainsString('@for ($i = 0; $i < 9; $i++)', $vista);
    }

    public function test_form_018_solo_ofrece_los_cuatro_tipos_del_documento_oficial(): void
    {
        $controlador = file_get_contents(app_path('Http/Controllers/ENF/EnfAccionController.php'));

        preg_match('/private const TIPOS_ACCION_FORM_018 = \[(.*?)\];/s', $controlador, $coincidencia);

        $this->assertNotEmpty($coincidencia);
        $this->assertStringContainsString("'Proyecto de educacion continua'", $coincidencia[1]);
        $this->assertStringContainsString("'Diplomado'", $coincidencia[1]);
        $this->assertStringContainsString("'Congreso'", $coincidencia[1]);
        $this->assertStringContainsString("'Seminario'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Certificado universitario'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Programa de educacion continua'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Curso'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Taller'", $coincidencia[1]);
    }

    public function test_form_018_no_depende_de_daft_y_form_016_conserva_su_referencia(): void
    {
        $vistaForm018 = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));
        $documentoForm016 = file_get_contents(resource_path('views/enf/acciones/partials/form-016-document.blade.php'));
        $controlador = file_get_contents(app_path('Http/Controllers/ENF/EnfAccionController.php'));
        $mapeadorForm018 = file_get_contents(app_path('Services/Documents/FormDvus018DataMapper.php'));

        $this->assertStringNotContainsString('DAFT', $vistaForm018);
        $this->assertStringNotContainsString('approvedProgram', $vistaForm018);
        $this->assertStringNotContainsString('ProgramaCertificacion', $controlador);
        $this->assertStringNotContainsString('programasAprobadosEducacionContinua', $controlador);
        $this->assertStringNotContainsString('$counterpart?->rtn', $mapeadorForm018);
        $this->assertStringContainsString('Asignado por la DAFT', $documentoForm016);
    }

    public function test_participacion_universitaria_se_agrega_desde_modal_y_calcula_totales(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));

        $this->assertStringContainsString('data-participacion-summary', $vista);
        $this->assertStringContainsString('data-participacion-summary-totals', $vista);
        $this->assertStringContainsString('data-open-participacion-modal', $vista);
        $this->assertStringContainsString('<select data-participacion-tipo', $vista);
        $this->assertStringContainsString('data-participacion-hombres', $vista);
        $this->assertStringContainsString('data-participacion-mujeres', $vista);
        $this->assertStringContainsString('data-participacion-cantidad', $vista);
        $this->assertStringContainsString('const updateParticipacionTotal = () =>', $vista);
        $this->assertStringContainsString('data-edit-participacion=', $vista);
        $this->assertStringContainsString('data-remove-participacion=', $vista);
        $this->assertStringNotContainsString('data-participacion-list-modal', $vista);
        $this->assertStringContainsString('Sin participación registrada.', $vista);
        $this->assertStringContainsString('registeredRows.reduce', $vista);
    }

    public function test_autoguardado_aisla_cada_borrador_form_018(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));
        $controlador = file_get_contents(app_path('Http/Controllers/ENF/EnfAccionController.php'));

        $this->assertStringContainsString("['_token', '_method', 'borrador_autoguardado_id'].includes(field.name)", $vista);
        $this->assertStringContainsString("formData.delete('borrador_autoguardado_id')", $vista);
        $this->assertStringContainsString('storageKey = `enf-accion-form-draft-${draftRecordId}`', $vista);
        $this->assertStringContainsString('data-form-update-url-template=', $vista);
        $this->assertStringContainsString("methodField.value = 'PUT'", $vista);
        $this->assertStringContainsString('if (serverAutosaveInFlight)', $vista);
        $this->assertStringContainsString('if (!submittingAfterAutosave)', $vista);
        $this->assertStringContainsString('$recordId = $accion;', $controlador);
        $this->assertStringNotContainsString('$recordId = $accion ?: $request->integer(\'borrador_autoguardado_id\');', $controlador);
    }

    public function test_archivos_del_paso_10_se_envian_con_el_guardado_multipart(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));

        $this->assertStringContainsString('enctype="multipart/form-data" novalidate', $vista);
        $this->assertStringContainsString('type="submit" data-submit-step', $vista);
        $this->assertStringContainsString('type="button" data-send-step', $vista);
        $this->assertStringContainsString('Guardar como Borrador', $vista);
        $this->assertStringContainsString('Enviar para Firmar', $vista);
        $this->assertStringContainsString('name="guardar_solo_borrador" value="1" data-save-draft-field', $vista);
        $this->assertStringContainsString('name="es_nuevo_borrador" value="{{ $editingAccion ? \'0\' : \'1\' }}"', $vista);
        $this->assertStringContainsString('const hasPendingDocumentFiles = () =>', $vista);
        $this->assertStringContainsString('Archivos seleccionados. Guarde el borrador o envíe para firmar para subirlos.', $vista);
        $this->assertStringContainsString("if (event.target.matches('[data-doc-upload-file]'))", $vista);
        $this->assertStringContainsString('if (draftRecordId) {', $vista);
        $this->assertStringContainsString('Promise.resolve(serverAutosavePromise)', $vista);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(form)', $vista);
        $this->assertStringContainsString('.finally(() => finalSubmit())', $vista);
        $this->assertStringContainsString('.finally(() => openSendReviewOrSubmit())', $vista);
        $this->assertStringContainsString("saveDraftField.value = sendForSignature ? '0' : '1'", $vista);
    }

    public function test_guardar_form_018_redirige_al_historial_y_notifica_el_resultado(): void
    {
        $controlador = file_get_contents(app_path('Http/Controllers/ENF/EnfAccionController.php'));
        $request = file_get_contents(app_path('Http/Requests/ENF/StoreEnfAccionRequest.php'));

        $this->assertStringContainsString("'guardar_solo_borrador' => ['nullable', 'boolean']", $request);
        $this->assertStringContainsString("'es_nuevo_borrador' => ['nullable', 'boolean']", $request);
        $this->assertStringContainsString('! $guardarSoloBorrador && app(EnfWorkflowService::class)->enviarInscripcion', $controlador);
        $this->assertStringContainsString('$mostrarComoActualizado = $actualizado && ! $request->boolean(\'es_nuevo_borrador\');', $controlador);
        $this->assertStringContainsString("->title(\$mostrarComoActualizado ? 'Borrador actualizado' : 'Borrador guardado')", $controlador);
        $this->assertStringContainsString('se actualizó correctamente.', $controlador);
        $this->assertStringContainsString('se guardó correctamente.', $controlador);
        $this->assertStringContainsString("->route('proyectosDocente', ['tipo' => 'educacion_no_formal'])", $controlador);
    }

    public function test_resultados_se_gestionan_en_modal_y_se_muestran_en_tabla(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));

        $this->assertStringContainsString('data-resultados-list', $vista);
        $this->assertStringContainsString('data-resultados-fields class="hidden"', $vista);
        $this->assertStringContainsString('data-open-resultado-modal=', $vista);
        $this->assertSame(3, substr_count($vista, "'clave' =>"));
        $this->assertStringContainsString('Resultados de corto plazo', $vista);
        $this->assertStringContainsString('Resultados de mediano plazo', $vista);
        $this->assertStringContainsString('Resultados de largo plazo / impacto', $vista);
        $this->assertStringContainsString('data-grupo="{{ $grupoResultado[\'clave\'] }}"', $vista);
        $this->assertStringContainsString('table-fixed', $vista);
        $this->assertStringContainsString('[overflow-wrap:anywhere]', $vista);
        $this->assertStringContainsString('data-resultado-modal', $vista);
        $this->assertStringContainsString('data-save-resultado', $vista);
        $this->assertStringContainsString('data-edit-resultado=', $vista);
        $this->assertStringContainsString('data-remove-resultado=', $vista);
        $this->assertStringContainsString('<div data-resultado-objetivo', $vista);
        $this->assertStringNotContainsString('<input type="number" min="1" data-resultado-objetivo', $vista);
        $this->assertStringContainsString('El número se asigna automáticamente.', $vista);
        $this->assertStringContainsString('const siguienteOrdenResultadoCortoPlazo = () =>', $vista);
        $this->assertStringContainsString('const compactarResultados = (tipo) =>', $vista);
        $this->assertStringNotContainsString('resultadoInputs.objetivo_orden.required', $vista);
        $this->assertStringContainsString('const renderResultados = () =>', $vista);
        $this->assertStringContainsString('const openResultadoModal = (tipo, index = null) =>', $vista);
        $this->assertStringContainsString('const stepSevenIsComplete = (panel) =>', $vista);
        $this->assertStringContainsString("['corto', 'mediano', 'largo']", $vista);
        $this->assertStringContainsString('input[name="ods_ids[]"]:checked', $vista);
        $this->assertStringContainsString('input[name="meta_contribuye_ids[]"]:checked', $vista);
        $this->assertStringContainsString("field.closest('[data-resultados-fields]')", $vista);
        $this->assertStringContainsString('name="resultados[{{ $resultadoIndex }}][descripcion]"', $vista);
        $this->assertStringContainsString('name="resultados[{{ $resultadoIndex }}][indicador]"', $vista);
    }

    public function test_documento_form_018_mantiene_los_desgloses_del_word(): void
    {
        $documento = file_get_contents(resource_path('views/enf/acciones/partials/form-018-document.blade.php'));

        $this->assertStringContainsString("\$participacion('Profesores x hora', 'hombres')", $documento);
        $this->assertStringContainsString("\$participacion('Administrativo', 'hombres')", $documento);
        $this->assertStringContainsString("\$participacion('Servicios', 'hombres')", $documento);
        $this->assertStringContainsString('Profesionales universitarios otros CES', $documento);
        $this->assertStringContainsString('Personas con discapacidades', $documento);
        $this->assertStringContainsString('Nota: El documento 1 obligatorio.', $documento);
        $this->assertStringNotContainsString('RTN / identificación internacional', $documento);
    }

    public function test_documento_form_018_reserva_el_pie_en_pdf_y_compacta_la_vista(): void
    {
        $documento = file_get_contents(resource_path('views/enf/acciones/partials/form-018-document.blade.php'));

        $this->assertStringContainsString('min-height: 10.98in;', $documento);
        $this->assertStringContainsString('.form018-shell.screen-document .form018-page', $documento);
        $this->assertStringContainsString('min-height: 0;', $documento);
        $this->assertStringContainsString('.form018-shell.screen-document .form018-footer', $documento);
        $this->assertStringContainsString('position: static;', $documento);
        $this->assertStringContainsString('.form018-shell.is-pdf .form018-auto-row', $documento);
        $this->assertStringContainsString('.form018-shell.is-pdf .form018-footer', $documento);
        $this->assertStringNotContainsString('display: none !important;', $documento);
        $this->assertSame(11, substr_count($documento, '<section class="form018-page">'));
    }
}
