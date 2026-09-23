# Pendientes

Problemas detectados y aún sin resolver. Cada entrada indica dónde está, por
qué importa y cómo resolverla. Al cerrar uno, bórralo de aquí en el mismo commit.

Última revisión: 2026-09-23.

---

## Bloquea el commit

### `.env.testing` modificado
- **Qué:** está versionado y se cambió `DB_USERNAME=nexo` → `root` con contraseña
  vacía para poder correr los tests en local.
- **Riesgo:** si se commitea, cambia las credenciales de testing de todo el equipo.
- **Resolver:** revertir (`git checkout .env.testing`) y crear el usuario `nexo`
  en MySQL local, o sacar el archivo del control de versiones y dejar un
  `.env.testing.example`.

---

## Informe final INF-001

Afecta por igual al FORM-DVUS-001 y al FORM-DVUS-015, que comparten el INF-001
sin ninguna rama condicional.

Los defectos de esta sección tienen su especificación en
`tests/Feature/InformeFinalInf001FormatoOficialTest.php`: los tests rojos pasan
a verde al arreglarlos.

Orden recomendado: aplicabilidad → catálogo presupuestario → clasificación por
clave → base del 3 % → actividades por resultado.

### Aplicabilidad: la lista blanca deja fuera tipos de acción
- **Dónde:** `InformeFinalProyectoWorkflowService::TIPOS_ACCION_INF_001`.
- **Qué:** solo admite `DESARROLLO_LOCAL_REGIONAL` y `VOLUNTARIADO`. Quedan
  fuera, sin tener formato de cierre propio, `SEGUMIENTO_A_EGRESADOS` (el formato
  lo lista en I.7 como «Seguimiento a graduados»), `ALINEAMIENTO_CURRICULAR`,
  `PRACTICAS_EDUCATIVAS_INTEGRALES` y `PPS_VOLUNTARIADO_GESTION_RIESGO`.
  Los proyectos heredados sin tipo reciben Desarrollo local con la migración
  `2026_09_17_000001_backfill_tipo_accion_on_legacy_proyectos`.
- **Resolver:** invertir la regla y excluir solo los tipos con cierre propio
  (`EDUCACION_NO_FORMAL`, `PRESTACION_SERVICIOS_TECNICOS`).
- **Tests:** `test_el_inf001_alcanza_a_las_categorias_de_proyecto_del_formato`,
  `test_un_proyecto_sin_tipo_de_accion_puede_cerrar_su_ciclo`.

### Catálogo presupuestario incompleto
- **Dónde:** `AporteInstitucional::getConceptoLabelAttribute`.
- **Qué:** define 7 conceptos y el apartado X del formato exige 12. Faltan
  c) consultorías, d) alimentación, e) viáticos/estipendios, g) combustible y
  j) insumos adquiridos por estudiantes. Las letras además están desalineadas
  (el formato llama f) a movilización; el sistema, c).
- **Resolver:** ampliar el catálogo a los 12 con las letras oficiales. Es
  prerrequisito de los dos siguientes.
- **Test:** `test_el_catalogo_presupuestario_cubre_los_doce_conceptos_del_formato`.

### Costos indirectos detectados buscando texto
- **Dónde:** `InformeFinalProyecto::getSubtotalUnahBaseAttribute`,
  `getInfraestructuraUnahAttribute`, `getServiciosUnahAttribute`.
- **Qué:** clasifican con `str_contains($concepto, 'servicio')` sobre texto libre.
  «c) Contratación de servicios profesionales» (40 000) sale de la base y se
  contabiliza como costo indirecto de servicios públicos.
- **Resolver:** clasificar por la clave del catálogo
  (`costos_indirectos_infraestructura` / `costos_indirectos_servicios`).
- **Test:** `test_un_concepto_de_consultoria_no_se_clasifica_como_costo_indirecto`.

### Base del 3 % incorrecta
- **Dónde:** `InformeFinalProyecto::getSubtotalUnahBaseAttribute`.
- **Qué:** el formato dice «calculado sobre la sumatoria de los conceptos a – b»
  (horas docentes + horas de estudiantes). El sistema usa todo el subtotal.
  Con a=100 000, b=20 000, d=50 000, g=30 000 da 6 000 en vez de 3 600 por
  partida: un 66,7 % de más, dos veces. Infla el aporte institucional reportado.
- **Resolver:** restringir la base a los conceptos a) y b).
- **Test:** `test_los_costos_indirectos_se_calculan_sobre_horas_docentes_y_estudiantes`.

### Actividades no asociadas a su resultado
- **Dónde:** tabla `informe_final_actividades`.
- **Qué:** el apartado VI pone «Detalle de las actividades realizadas» dentro de
  cada RESULTADO. La tabla no tiene `informe_final_resultado_id` (las acciones
  emergentes sí lo tienen), así que el documento las imprime en una tabla global.
- **Resolver:** migración con la FK, selector de resultado en el wizard y
  agrupar la tabla por resultado en `inf-001-document.blade.php`.
- **Test:** `test_cada_actividad_realizada_se_asocia_a_su_resultado`.

### Tres fallos preexistentes en `InformeFinalINF001Test`
Ya fallaban antes de los cambios recientes.
- `test_cupos_por_sexo_y_resumen_planificado_registrado_pendiente` — desactualizado:
  registra estudiantes manuales sin carrera, correo ni horas (obligatorios desde
  antes) ni apellidos (obligatorios al registrar desde 2026-09-16). Basta con
  completar esos `set()` en el test.
- `test_instrumento_de_contraparte_se_precarga_en_anexos_sin_duplicar_archivo`
- `test_ods_planificado_es_solo_lectura_y_ods_de_ejecucion_permanece_editable`
  (espera el texto «Cargado desde el registro del proyecto», que la vista ya no emite)

---

## Datos y seeders

### `tipo_estado` duplicado
- **Qué:** 33 filas con los 16 nombres repetidos. El seeder usa `firstOrCreate`
  sobre un modelo con `SoftDeletes`, así que cada `db:seed` añade otras 16.
- **Mitigado:** el panel estadístico usa `EstadosProyecto::ids()`, que devuelve
  todos los ids de un nombre. El resto del sistema que haga
  `TipoEstado::where('nombre', …)->first()` cuenta solo la mitad.
- **Resolver:** que el seeder busque también entre los borrados
  (`withTrashed()`) y deduplicar la tabla reapuntando las FK de
  `estado_proyecto` y `cargo_firma`.

### `PersonalSeeder` pisa datos
- **Qué:** fuerza `active_role_id = 1`, `centro_facultad_id = 4` y
  `departamento_academico_id = 9` en NOTIFICACIONES POA, y deja
  `categoria_id => 2` escrito a mano (los usuarios de prueba, en cambio,
  resuelven la categoría por nombre).
- **Consecuencia:** correr el seeder sobre un dump cambia el rol activo de esa
  cuenta y la ubica en Ciencias Jurídicas.
- **Resolver:** `firstOrCreate` en vez de `updateOrCreate` para esa cuenta y
  resolver la categoría por nombre.

### Posible placeholder guardado como valor en el FORM-DVUS-015
- **Qué:** el `programa_pertenece` del proyecto 64 contiene «Programa/Estrategia
  al que Pertenece» repetido ~15 veces: el texto de la etiqueta del campo.
- **Resolver:** confirmar si el formulario guarda la etiqueta al dejar el campo
  vacío o si fue un dato de prueba tecleado así.

### NOTIFICACIONES POA con perfil de empleado
- Es una cuenta de servicio (`notificacionespoa@unah.edu.hn`) y tiene empleado
  con facultad y departamento asignados. Revisar si debe tenerlos.

---

## Seguridad y acceso

### Usuarios sin empleado reciben un 500 en vez de un 403
- **Dónde:** `resources/views/components/panel/navbar/one-item.blade.php` llama
  a `empleado->firmaProyectoPendientes()` sin comprobar nulo.
- **Qué:** la política correcta es que no entren, pero hoy se impone por
  accidente: revienta la vista. Una pantalla sin sidebar (PDF, descarga) quedaría
  accesible, el usuario no recibe un mensaje útil y ensucia los logs.
- **Resolver:** en `EnsureUserHasRole`, junto a la comprobación de roles, un
  `abort(403)` con mensaje si el usuario no tiene empleado. Con test.

---

## Panel estadístico

- **Formularios sin declarar en `nexo.dashboard.formularios`:**
  `ServicioTecnologico` (usa su propio `EstadoServicioTecnologico`, necesita una
  clase que implemente `FormularioPanel`) y `FichaActualizacion` (motor común,
  pero sus firmas son solo por cargo). Tampoco DAFT (`ProgramaRevision`).
- **Pasantías y PPS sin centro enlazado:** guardan la facultad como texto, así
  que en el panel de un centro su cifra es institucional (se advierte).
- **Verificación visual por rol:** el 23-09 se revisó el panel de administración
  con los datos importados, en modo claro. Falta con cada rol, en modo oscuro y
  con el sidebar plegado. Los tests prueban que renderiza, no cómo se ve.

---

## Antes de desplegar

- **Migraciones restituidas del 17-09:**
  `2026_09_17_000001_backfill_tipo_accion_on_legacy_proyectos` y
  `2026_09_17_000002_add_revision_vinculacion_stages_to_form_dvus_001_flow`. Se
  habían ejecutado en algunas bases sin llegar al repositorio. Donde no constan,
  `php artisan migrate` asigna Desarrollo local a los proyectos sin tipo y
  completa el flujo del FORM-DVUS-001 con las etapas de Vinculación. Revisar
  después en Configuración → Flujos los responsables de las etapas nuevas (se
  crean enviando a todo el rol).

- **Migración nueva:** correr
  `2026_09_11_000001_widen_texto_libre_on_informe_final_proyectos` en cada entorno.
- **Cargo «Coordinador Proyecto» retirado del selector de flujos:** comprobar
  en producción que ninguna etapa lo use; si alguna lo usa, migrarla antes.

  ```sql
  SELECT COUNT(*) FROM flujos_aprobacion_etapas e
    JOIN cargo_firma c ON c.id = e.cargo_firma_id
    JOIN tipo_cargo_firma t ON t.id = c.tipo_cargo_firma_id
   WHERE t.nombre = 'Coordinador Proyecto';
  ```

- **Catálogos sin sembrar tras importar un dump:** correr `php artisan db:seed`
  (o al menos `VinculacionTiposAccionSeeder` y `TipoAnexoSeeder`). Sin el
  primero, 4 de 5 formularios desaparecen de la configuración de flujos y el
  INF-001 no abre; sin el segundo, ningún anexo pasa la validación del
  FORM-DVUS-001. Ver antes la advertencia de `PersonalSeeder`.
- **Suite completa:** nunca terminó en Windows (supera los 600 s). Además de los
  tres del INF-001, ya fallaban `PpsServicioSocialWorkflowTest`,
  `NewUserOnboardingTest` y `HistorialProyectoWorkflowStageResubmissionIntegrationTest`.
- **Dos fallos de ENF que llegaron con `origin/efrain`** (merge del 2026-09-16;
  fallan igual sin los cambios del panel):
  - `EnfWorkflowResumptionTest::test_informe_final_reanuda_desde_la_segunda_etapa`:
    el mock de `Pdf::loadView()` devuelve un `Mockery` y el facade exige
    `Barryvdh\DomPDF\PDF`.
  - `EnfDocumentoArchivoTest::test_envio_final_guarda_el_archivo_del_paso_10_antes_de_iniciar_el_flujo`:
    «No hay etapas configuradas para este proceso ENF»; al escenario le falta
    el flujo de cierre.

---

## Menores

- Textos sin tilde en `ConfiguracionFlujosProyectos::projectFlowCatalog()`:
  «Practica», «accion», «Educacion», «vinculacion».
