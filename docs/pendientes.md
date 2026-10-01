# Pendientes

Problemas detectados y aún sin resolver. Cada entrada indica dónde está, por
qué importa y cómo resolverla. Al cerrar uno, bórralo de aquí en el mismo commit.

Última revisión: 2026-10-01.

---

## Auditoría FORM-DVUS-001 (2026-10-01)

Lo hecho está en `docs/auditoria-form-dvus-001.md`. Falta:

### Fusionar los roles `DIRECCION DIVUS` y `Director Vinculacion`
- **Qué:** hay dos roles para la Dirección DVUS. `DIRECCION DIVUS` (con errata)
  se creó a mano y no lo usa el código; el canónico es `Director Vinculacion`.
  Quien busca «Dirección DVUS» en «Rol con acceso» no encuentra ninguno.
- **Decidido:** conservar `Director Vinculacion` y migrar a él usuarios y
  permisos del duplicado. No renombrar.
- **Paso 1, antes de escribir la migración:** contar en producción, con SQL de
  solo lectura, usuarios, permisos y referencias de cada rol. Las consultas
  completas están en `docs/auditoria-form-dvus-001.md`; la principal es:

  ```sql
  SELECT r.id, r.name,
         (SELECT COUNT(*) FROM model_has_roles m WHERE m.role_id = r.id AND m.model_type = 'App\\Models\\User') AS usuarios,
         (SELECT COUNT(*) FROM role_has_permissions rp WHERE rp.role_id = r.id) AS permisos,
         (SELECT COUNT(*) FROM users u WHERE u.active_role_id = r.id) AS usuarios_con_rol_activo
  FROM roles r
  WHERE r.name IN ('Director Vinculacion', 'DIRECCION DIVUS');
  ```
- **Paso 2, la migración** tiene que reapuntar, además de `model_has_roles` y
  `role_has_permissions`:
  - `users.active_role_id`;
  - `flujos_aprobacion_etapas.rol_revisor_id`;
  - `rol_requerido = 'DIRECCION DIVUS'` de las filas **pendientes** de
    `firma_proyecto`, `enf_revisiones` y `programa_revisiones`. La autorización
    compara ese nombre, y sin reapuntarlo esas firmas se quedan sin quién las
    apruebe.

  Las filas históricas se dejan intactas. Heredar los permisos del duplicado
  solo si el equipo lo confirma.

### Etapa `PASANTIAS_ETAPA_01` con cargo «Coordinador Proyecto»
- **Qué:** la migración `2026_09_07_000001` le asignó el primer cargo con
  descripción «Proyecto» por id, que es «Coordinador Proyecto».
  `PasantiaWorkflowService.php:340-361` lo usa para derivar el rol revisor.
  Con la regla «el coordinador no lleva sello», quien firme esa revisión ya no
  guarda sello. Hoy no se nota: los PDF de Pasantías no dibujan sellos.
- **Decidir:** si esa revisión es una etapa administrativa con sello, asignarle
  un cargo administrativo y ajustar `PasantiaWorkflowService.php:345-361`.

### Etapa DVUS del FORM-DVUS-001 con cargo `Revisor Vinculacion`
- **Qué:** en la base local, la etapa «Director Vinculacion» tiene cargo
  `Revisor Vinculacion`. La constancia de registro busca primero la firma con
  cargo `Director Vinculacion` y, si no la encuentra, usa la de la última etapa
  de inscripción.
- **Por qué no se corrige desde la UI:** el selector de cargos no ofrece
  `Director Vinculacion` (decisión: no se agrega), y una etapa REVISION creada
  desde la UI recibe `Revisor Vinculacion`.
- **Verificar en producción:**

  ```sql
  SELECT e.id, e.nombre, t.nombre AS cargo
  FROM flujos_aprobacion_etapas e
  JOIN cargo_firma c ON c.id = e.cargo_firma_id
  JOIN tipo_cargo_firma t ON t.id = c.tipo_cargo_firma_id
  WHERE e.nombre LIKE '%Director Vinculacion%';
  ```

### PDF canónico del INF-001 con sello del coordinador
- **Qué:** `InformeFinalProyectoWorkflowService::enviarInformeFinal()` guarda un
  PDF fijo al enviar. La vista y la descarga se generan al vuelo y ya salen sin
  ese sello, pero un archivo guardado antes conserva el sello del coordinador si
  ya había una firma aprobada con ese cargo.
- **Verificar y regenerar solo esos documentos:**

  ```sql
  SELECT DISTINCT d.id, d.proyecto_id, d.documento_url
  FROM proyecto_documento d
  JOIN firma_proyecto fp ON fp.firmable_id = d.id AND fp.firmable_type LIKE '%DocumentoProyecto'
  JOIN cargo_firma cf ON cf.id = fp.cargo_firma_id
  JOIN tipo_cargo_firma t ON t.id = cf.tipo_cargo_firma_id
  WHERE d.tipo_documento = 'Informe Final' AND t.nombre = 'Coordinador Proyecto'
    AND fp.estado_revision = 'Aprobado' AND fp.sello_id IS NOT NULL;
  ```

### Proyectos que ya perdieron datos (#81 y otros)
- **Qué:** los textos que se perdieron no se pueden recuperar: iban en un
  `UPDATE` revertido y el formulario los descartó al redirigir. El docente tiene
  que volver a capturarlos.
- **Qué sí se puede recuperar:** el `laravel.log` de producción contiene la
  sentencia que falló, con sus valores (por ejemplo, la contraparte).
- **Detectar los proyectos afectados:**

  ```sql
  SELECT p.id, p.nombre_proyecto FROM proyecto p
  WHERE (p.objetivo_general IS NULL OR p.objetivo_general = '')
    AND EXISTS (SELECT 1 FROM actividades a WHERE a.proyecto_id = p.id)
    AND p.deleted_at IS NULL;
  ```

### El borrador ya tiene una firma «Aprobado» del coordinador
- **Dónde:** `EmpleadoProyecto::boot()`.
- **Qué:** al registrar al coordinador se crea su firma ya aprobada, sin fecha.
  Aunque guardar el borrador ya no firma, el PDF de un borrador muestra la
  imagen de la firma; solo la marca de agua lo distingue.
- **Evaluar:** crearla Pendiente hasta el envío. Revisar antes
  `FichaActualizacion::puedeSerEliminada()` y el historial, que cuentan con esa
  firma.

### Menores
- Por uniformidad, `AutoridadEmisoraConstanciaResolver.php:27` y
  `AutoridadEmisoraConstanciaRegistroResolver.php:29` pueden leer el sello con
  `FirmaProyecto::selloParaDocumento()`. No cambia el resultado: son cargos
  administrativos.
- Si ENF empieza a escribir firmas en `EnfFirma`, reutilizar
  `CargoFirma::admiteSello()` (también tiene `cargo_firma_id`).
- El texto de ayuda de Configuración → Flujos dice que una etapa de Revisión
  «solo pasa a la siguiente etapa». En realidad `aprobarFirmaPorEtapa()` guarda
  firma y sello en ambos tipos.

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
- **Migraciones de la auditoría del FORM-DVUS-001:**
  - `2026_10_01_000001_ampliar_textos_de_contrapartes_y_resultados`: TEXT en
    compromisos, indicador y medio de verificación;
  - `2026_10_01_000002_revocar_administrar_asignaturas_de_docente`.

  En la base local de desarrollo también está pendiente
  `2026_09_29_000001_add_tipo_firma_to_firma_proyecto_table`.
- **Cargo «Coordinador Proyecto» retirado del selector de flujos:** comprobar
  en producción que ninguna etapa lo use; si alguna lo usa, migrarla antes.
  Desde la auditoría del 2026-10-01, una etapa con ese cargo nunca guarda sello
  (`CargoFirma::admiteSello`).

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
- **Suite completa:** en Windows nunca terminó (supera los 600 s). En macOS
  termina en ~90 s. El 2026-10-01 fallaban 18 tests, los mismos con y sin los
  cambios de la auditoría:
  - `ProyectoVinculacionFormularioTest` (4): resultado sin plazo, beneficiario
    vacío, `calcTotales` y catálogo de anexos. Están desactualizados; por
    ejemplo, usan la propiedad `indigenas_mujeres`, que ya no existe.
  - `FormDvus001PdfLayoutTest` (1): nota de documentos adjuntos.
  - `FichaFirmaDelFirmanteRealTest` (2): `firmasParaFicha()`.
  - `InformeFinalINF001Test` (3) e `InformeFinalInf001FormatoOficialTest` (2):
    ver la sección del INF-001.
  - `NewUserOnboardingTest` (2): inicio de sesión.
  - `PasantiaLivewireTest` (2).
  - `PpsServicioSocialWorkflowTest` (1).
  - `EnfDocumentoArchivoTest` (1).

  `HistorialProyectoWorkflowStageResubmissionIntegrationTest` y
  `EnfWorkflowResumptionTest` ya pasan.
- **Fallo de ENF que llegó con `origin/efrain`** (merge del 2026-09-16):
  `EnfDocumentoArchivoTest::test_envio_final_guarda_el_archivo_del_paso_10_antes_de_iniciar_el_flujo`.
  Error: «No hay etapas configuradas para este proceso ENF»; al escenario le
  falta el flujo de cierre.

---

## Menores

- Textos sin tilde en `ConfiguracionFlujosProyectos::projectFlowCatalog()`:
  «Practica», «accion», «Educacion», «vinculacion».
