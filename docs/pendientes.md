# Pendientes

Última verificación: 2026-10-02, código hasta `931b1de7` y base local.
Evidencia y elementos retirados: [auditoría de pendientes](auditoria-pendientes-2026-10-02.md).
Producción y la apariencia en navegador no se verificaron. Al resolver un punto,
retirarlo de este archivo con evidencia de la corrección.

## Prioridad: acceso e integridad de datos

### Usuario con rol pero sin empleado
- **Confirmado en código:** `EnsureUserHasRole::handle()` valida roles, pero no
  comprueba que exista empleado. `DataNavBar::obtenerCantidadFichasPorFirmar()`
  accede a `auth()->user()->empleado->firmaProyectoPendientes()` sin comprobar nulo.
  El sidebar invoca ese método cuando corresponde al permiso del usuario.
- **Falta:** aplicar la política de acceso para usuarios sin empleado y probar
  tanto el sidebar como las rutas directas. Conservar la posibilidad de cerrar sesión.
  La ubicación de la llamada peligrosa es `app/Clases/DataNavBar.php`, no la vista.

### Estados duplicados y búsqueda de estados borrados
- **Confirmado en la base local:** 16 nombres tienen dos filas activas cada uno.
  Las cantidades de producción deben consultarse por separado.
- **Confirmado en código:** `ProyectoSeeder` usa `TipoEstado::firstOrCreate()` y
  el modelo usa `SoftDeletes`. Si solo existe una fila borrada, crea otra; no
  agrega duplicados en cada ejecución si ya encuentra una fila activa.
  Esto no demuestra cómo se originaron los duplicados activos actuales.
- **Falta:** deduplicar reapuntando las referencias, revisar las consultas por
  nombre que usan un único id y definir restauración/búsqueda con `withTrashed()`.
  `EstadosProyecto::ids()` mitiga el panel, pero no todas las consultas.

### Seeders que sobrescriben configuración existente
- **PersonalSeeder:** usa `updateOrCreate()` para NOTIFICACIONES POA; sobrescribe
  rol activo, contraseña y datos de empleado, fija facultad 4, departamento 9 y
  categoría 2. También ejecuta `syncRoles()`.
- **Falta:** preservar cuentas existentes y resolver catálogos por nombre.
  Cambiar solo a `firstOrCreate()` no evita que `syncRoles()` reemplace sus roles.
- **Hallazgo adicional:** `ProyectoSeeder` actualiza `PROYECTO_DEFAULT`, elimina
  sus etapas y las vuelve a crear. Reejecutarlo puede sustituir la configuración
  y los identificadores de etapas existentes.
- **Falta:** hacer que el seeder preserve flujos configurados y comprobar las
  referencias históricas antes de cualquier reconstrucción.

## Informe final INF-001

### Aplicabilidad limitada a dos tipos
- **Reproducido con el servicio actual:** admite `DESARROLLO_LOCAL_REGIONAL` y
  `VOLUNTARIADO`; rechaza `SEGUMIENTO_A_EGRESADOS`, `ALINEAMIENTO_CURRICULAR`,
  `PRACTICAS_EDUCATIVAS_INTEGRALES`, `PPS_VOLUNTARIADO_GESTION_RIESGO` y proyectos
  sin tipo.
- **Dónde:** `InformeFinalProyectoWorkflowService::aplicaInformeFinalInf001()`.
- **Falta:** alinear la regla con los tipos que deben cerrar mediante INF-001,
  manteniendo los cierres propios de ENF y Servicio Tecnológico. Verificar también
  la configuración de etapas de cierre de cada tipo.
- **Pruebas existentes:** `test_el_inf001_alcanza_a_las_categorias_de_proyecto_del_formato`
  y `test_un_proyecto_sin_tipo_de_accion_puede_cerrar_su_ciclo`.

### Uniformar etiquetas presupuestarias del registro
- El INF-001 ya usa `ConceptosPresupuestoInf001`, con los 12 conceptos oficiales.
- **Pendiente menor:** `AporteInstitucional::getConceptoLabelAttribute()` reconoce
  12 claves, pero mantiene las letras antiguas c–g y deja los cinco conceptos
  adicionales sin letra. Uniformar los consumidores del registro sin alterar
  el cálculo ya corregido del INF-001.

## Flujos y firmas: decisiones pendientes

### Firma aprobada al crear el borrador
- **Confirmado en código:** `App\Models\Proyecto\EmpleadoProyecto::boot()` crea
  la firma como `Aprobado` si el empleado coordinador corresponde al usuario
  autenticado; en otro caso la crea `Pendiente`. No asigna fecha de firma.
- La ficha puede usar la imagen del perfil como respaldo.
- **Falta decidir:** si la firma debe permanecer pendiente hasta enviar. Revisar
  `FichaActualizacion::puedeSerEliminada()` y el historial antes de cambiarlo.
  Este punto trata del momento de aprobación; la omisión del sello ya está resuelta.

### Cargo de la revisión inicial de Pasantías
- **Confirmado localmente:** etapa 1, `PASANTIAS_ETAPA_01`, usa `Coordinador Proyecto`.
- La migración inicial elige el primer cargo «Proyecto»; el método
  `PasantiaWorkflowService::repararCargoInicialSiEsNecesario()` también busca el
  cargo de coordinador cuando falta la configuración.
- **Falta decidir:** si la revisión es administrativa, asignar el cargo apropiado
  y ajustar la reparación automática. Si corresponde a coordinación, conservar
  la regla vigente de firma sin sello. No es por sí solo un defecto de renderizado.

### Cargo de la etapa de Dirección DVUS
- **Confirmado localmente:** etapa 12 `ETAPA_05`, llamada `Director Vinculacion`,
  usa `Revisor Vinculacion`; la etapa 18 `DIRECTOR_VINCULACION` sí usa el cargo
  `Director Vinculacion`.
- La constancia busca la firma del Director y tiene respaldo en la última etapa
  de inscripción. La diferencia de nombres no demuestra un fallo de emisión.
- **Falta:** verificar que el cargo y la autoridad emisora de cada flujo sean
  los institucionalmente previstos, también en producción. No agregar el cargo
  al selector sin revisar la decisión documentada en la auditoría FORM-DVUS-001.

## Datos que requieren revisión

### Proyectos con objetivo vacío y actividades
- **Resultado local actual:** la consulta devuelve el proyecto 32. No devuelve
  el #81 mencionado en el reporte anterior. Esto no demuestra recuperación ni
  pérdida de datos: el registro puede ser un borrador incompleto.
- **Falta:** revisar esos expedientes y consultar producción/logs antes de pedir
  recaptura o restaurar valores. Los logs pueden contener algunos valores de
  sentencias fallidas; no se garantiza recuperar todo el formulario.

```sql
SELECT p.id FROM proyecto p
WHERE (p.objetivo_general IS NULL OR p.objetivo_general = '')
  AND EXISTS (SELECT 1 FROM actividades a WHERE a.proyecto_id = p.id)
  AND p.deleted_at IS NULL;
```

### Reporte anterior del proyecto 64
- **Resultado local actual:** `programa_pertenece` tiene 15 caracteres y no
  contiene «Programa/Estrategia al que Pertenece». El input enlaza la propiedad,
  que inicia vacía; no se encontró una asignación automática de la etiqueta.
- **Falta:** confirmar el estado en el entorno del reporte original antes de
  atribuirlo a un defecto del formulario. No está reproducido localmente.

### Perfil de NOTIFICACIONES POA
- **Confirmado localmente:** la cuenta de servicio tiene un registro de empleado.
- **Falta decidir:** si debe conservar ese perfil y sus asignaciones académicas.
  El seeder todavía vuelve a establecerlas.

## Panel estadístico

- **Cobertura:** `config/nexo.php` no declara Servicio Tecnológico, Ficha de
  Actualización ni DAFT en `dashboard.formularios`. Agregar adaptadores según
  su motor de estados y firmas.
- **Filtro por centro/departamento:** Pasantías y PPS no tienen las columnas
  enlazadas en `FormularioMotorComun`; se muestran cifras institucionales y la
  vista lo advierte. Falta el vínculo estructurado para filtrar correctamente.
- **Revisión visual:** comprobar cada rol, modo oscuro y sidebar plegado.
  No se verificó el navegador en esta auditoría.

## Pruebas y despliegue

### Cuatro fallos reproducidos el 2026-10-02
- `ProyectoVinculacionFormularioTest`:
  - `test_beneficiario_vacio_se_convierte_en_cero`;
  - `test_calc_totales_no_lanza_error_y_calcula_subtotales`;
  - `test_catalogo_de_anexos_contiene_los_cuatro_tipos_solicitados`.
- `FormDvus001PdfLayoutTest::test_documentos_adjuntos_incluyen_los_cuatro_requisitos_y_la_nota`.
- Los dos primeros usan contadores antiguos (`indigenas_mujeres`, etc.); el
  formulario actual suma `hombres` y `mujeres`. El tercero espera nombres sin
  los prefijos numéricos actuales. El cuarto espera «(cualquiera de los dos)»,
  frase ausente de una nota que conserva el mismo requisito.
- **Falta:** actualizar las expectativas y conservar pruebas del comportamiento
  actual. No se reprodujo un defecto funcional nuevo a partir de estos cuatro fallos.

### Base de pruebas y fallos históricos por verificar
- La selección sin dependencia de MySQL completó **69 pruebas aprobadas y 4
  fallidas (231 aserciones)**.
- La selección dependiente de MySQL se detuvo en el primer error de conexión:
  `Access denied for user 'nexo'@'localhost'` (0 aserciones; 244 pruebas pendientes).
  La base local de la aplicación sí permitió consultas de lectura.
- **Falta:** corregir el acceso a `vinculacion_testing` y ejecutar la suite completa.
  No se confirma el antiguo total de 18 fallos.
- Revalidar los reportes anteriores de `FichaFirmaDelFirmanteRealTest`,
  `InformeFinalINF001Test`, `InformeFinalInf001FormatoOficialTest`,
  `NewUserOnboardingTest`, `PasantiaLivewireTest`, `PpsServicioSocialWorkflowTest`
  y `EnfDocumentoArchivoTest`.
- En INF-001, el test de cupos aún omite datos obligatorios del estudiante y el
  test de ODS exige un texto antiguo. El test ENF desactiva el flujo del formulario;
  revisar su escenario antes de atribuir el fallo al guardado de anexos.

### Verificación por entorno
- **Base local:** `migrate:status` no reporta migraciones pendientes, incluidas
  `2026_09_22_000002_alinear_inf001_con_formato_oficial`, las del 29-09 y 01-10,
  y `2026_10_02_000001_merge_legacy_direccion_divus_role`.
- **Producción:** falta verificar el estado de migraciones, responsables de las
  etapas y catálogos de tipos de acción y anexos. No inferir su estado del local.
- Si faltan catálogos, ejecutar solo los seeders necesarios tras revisarlos;
  el `db:seed` completo incluye las sobrescrituras descritas arriba.

## Ajustes menores

- Corregir la ayuda de Configuración → Flujos que afirma que Revisión «solo
  pasa a la siguiente etapa»: el flujo registra firmas también en revisiones.
- Corregir tildes en `ConfiguracionFlujosProyectos::projectFlowCatalog()`.
- Uniformar los resolutores de constancias para leer el sello mediante
  `FirmaProyecto::selloParaDocumento()`. Es mantenimiento; no se reprodujo un
  fallo de constancia por este acceso directo.
