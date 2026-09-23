# Estados generales y etapas de los flujos de proyectos

Fecha de documentación: 22 de septiembre de 2026.

### Actualización del 23 de septiembre: excluir expedientes sin envío de la adaptación

El historial administrativo excluía solo el valor literal `Borrador`, mientras
la presentación mostraba también `Autoguardado` como Borrador. Por eso podían
aparecer expedientes sin envío con la acción Adaptar flujo.

Se centralizaron los estados previos al envío (`Borrador`, `Autoguardado` y
las variantes de `PendienteInformacion`) y se excluyen de este listado.
La acción de adaptación también comprueba la elegibilidad. El servidor bloquea
abrir el modal, guardar y adoptar directamente estos expedientes, incluso si
se intenta forzar el modo En revisión. Los expedientes sin estado tampoco
pueden iniciar una adopción.

El modo histórico BORRADOR se conserva como dato de auditoría, pero ya no
permite nuevas adopciones de borradores. No se eliminan proyectos, firmas ni
adopciones anteriores. Los proyectos enviados, incluidos los de subsanación,
mantienen su diagnóstico y sus comprobaciones de adaptación. Los borradores
siguen su creación y envío normales desde el área docente.

La validación focalizada incluye bloqueo sin modificaciones de datos,
ocultamiento del Autoguardado en el listado, llamadas directas al modal y
guardado, y ambos recorridos completos (nuevo y legacy) hasta Finalizado.
Para ejecutar estos últimos se aplicó únicamente en `vinculacion_testing` la
migración preexistente pendiente
`2026_09_22_000001_make_informe_final_contraparte_aportes_nullable.php`.

La auditoría posterior, con dos recorridos continuos hasta Finalizado y una
ejecución conjunta de 387 pruebas (381 aprobadas y 6 fallidas), está registrada
en [Pruebas de flujos desde cero](pruebas-flujos-desde-cero-2026-09-22.md).
Ese informe incluye la corrección del cierre con empleados sin categoría y
las migraciones necesarias para revisar e incorporar `a66c2248`.

Este documento registra los cambios realizados durante la revisión de los
flujos de proyectos, su adaptación desde expedientes anteriores y la separación
entre estado general y etapa. Describe cambios locales de código y validaciones
observadas durante la sesión; no acredita un despliegue en producción ni una
corrección masiva de los datos existentes.

## 1. Problema que motivó la revisión

Un proyecto adaptado mostraba «Enlace Vinculacion», pero no aparecía en la
bandeja del usuario con ese rol. El texto mostrado correspondía al estado
asociado al cargo, mientras la asignación efectiva dependía de la etapa,
el rol requerido, el empleado responsable y el ciclo de revisión.

En la consulta realizada durante la auditoría, el proyecto 53 estaba en una
etapa cuyo rol era Jefe Departamento, aunque su estado decía Enlace Vinculacion.
La etapa con rol Enlace Vinculacion era posterior. Esa observación explica la
discrepancia de esa captura; no demuestra que todos los proyectos tuvieran
el mismo problema ni que todo se resolviera cambiando una etiqueta.

También se detectaron firmas con metadatos de flujo cuya etapa había quedado
nula. Tratarlas como firmas antiguas sin flujo podía abrir una ruta incorrecta
de autorización. Por tanto, se abordaron tanto la presentación como controles
de integridad y autorización. No se reasignó automáticamente el proyecto de
la captura ni se corrigió la configuración institucional de sus responsables.

## 2. Modelo acordado

| Concepto | Significado | Fuente |
| --- | --- | --- |
| Estado general | Situación del expediente durante su ciclo de vida | Estado del proyecto y catálogo `tipo_estado` |
| Etapa | Paso que corresponde atender en un proceso configurado | Flujo, etapas y firmas del ciclo vigente |
| Rol y responsable | Quién puede actuar en ese paso | Reglas de la etapa, firma asignada y autorización del usuario |
| Estado de firma | Situación de una aprobación individual | Firma: pendiente, aprobada o rechazada |

La base de datos ya disponía de referencias separadas para el estado y para
las etapas de las firmas. No se agregó una columna de etapa al proyecto ni se
reconstruyó el esquema. Se eliminó el acoplamiento que hacía que el estado
general de un proyecto configurado siguiera el nombre del cargo revisor.

Los estados generales acordados son:

| Estado | Uso |
| --- | --- |
| Borrador | Proyecto guardado antes del envío |
| En revision | Inscripción enviada, con aprobaciones pendientes |
| Subsanacion | Expediente devuelto para corregir |
| Registrado | Inscripción aprobada al completar el flujo correspondiente |
| Finalizado | Cierre aprobado mediante el proceso correspondiente |

Los valores técnicos conservan la escritura sin tildes indicada en la tabla.
El recorrido habitual es `Borrador → En revision → Registrado → Finalizado`.
La devolución abre la rama `En revision → Subsanacion → En revision`.
El cierre conserva sus requisitos y su proceso documental; no basta con
cambiar una etiqueta para dar un proyecto por finalizado.

Por decisión del usuario no se añadió una transición nueva a **En curso**.
Los registros anteriores con ese valor siguen siendo compatibles y se
presentan como Registrado en las pantallas adaptadas.

## 3. Compatibilidad con información anterior

La normalización está centralizada en
[`EstadoGeneralProyecto`](../app/Support/Proyecto/EstadoGeneralProyecto.php).

| Valores anteriores reconocidos | Estado general presentado |
| --- | --- |
| Borrador, Autoguardado | Borrador |
| En revision, En revisión, En revision final, Coordinador Proyecto, Enlace Vinculacion, Jefe Departamento, Director centro | En revision |
| Subsanacion, Subsanación | Subsanacion |
| Registrado, En curso, Inscrito, Aprobado | Registrado |
| Finalizado | Finalizado |

La comparación del nombre ignora mayúsculas y espacios exteriores. Los nombres
no reconocidos se conservan; no se convierten arbitrariamente a uno de los cinco
estados. Los valores vacíos se presentan como Borrador.

Esta tabla es una regla de compatibilidad, **no una actualización masiva** de
expedientes. El historial mantiene los estados originales. Al guardar un
borrador antiguo Autoguardado, se utiliza Borrador. Los proyectos sin flujo
conservan las rutas heredadas de revisión y sus verificaciones específicas.

Los estados propios de documentos, como Aprobado, no se reemplazaron
globalmente. Tampoco se migraron globalmente los ciclos de ENF, PPS, pasantías
u otros módulos que comparten catálogos o componentes.

## 4. Cambios en el funcionamiento

### 4.1 Proyectos creados con un flujo definido

- El envío inicial establece En revision, sin tomar el estado del primer cargo.
- Las aprobaciones intermedias mantienen En revision; avanza la etapa.
- La aprobación final de inscripción establece Registrado.
- El reenvío tras subsanación abre el ciclo correspondiente y vuelve a En revision.
- Para proyectos, un cargo existente no necesita tener un estado asociado para
  determinar el nuevo estado general. La existencia del cargo sigue validándose.
- Los documentos conservan su lógica específica de estados asociados al cargo.

Estos cambios también alcanzan a proyectos nuevos: no se limitan a la
adaptación. La compatibilidad se comprobó con un recorrido de creación, envío,
bandeja, detalle y aprobaciones hasta Registrado, sin pasar por la adopción.

### 4.2 Adaptación de proyectos anteriores

- El diagnóstico sigue utilizando la información histórica para determinar
  desde qué etapa continuar. Después de elegirla, una adopción en revisión
  utiliza En revision como estado general.
- Se reconoce Registrado como estado compatible con una inscripción completada.
- Se bloquea la adaptación cuando una firma que perdió su etapa conserva
  metadatos de flujo; no se la considera una firma heredada auténtica.
- Se bloquea una adaptación que volvería a exigir una aprobación histórica
  reconocida dentro del tramo futuro, según la correspondencia de cargos que
  utiliza el servicio.
- La evidencia histórica de adopción se conserva antes de normalizar el estado.

No se implementó una inferencia universal de equivalencia entre cargos y etapas.
Si la configuración institucional es ambigua, requiere revisión; la protección
añadida no debe interpretarse como reparación automática de cualquier flujo.

### 4.3 Firmas anteriores y ciclos

La normalización de estados no borra, reemplaza ni vuelve a firmar aprobaciones.
Se incorporaron casos de prueba para conservar tres aprobaciones anteriores
cuando queda una pendiente y para impedir la repetición de una aprobación
histórica al adaptar el expediente.

La actuación sobre una firma de flujo debe corresponder al ciclo más reciente
del flujo y del expediente/documento. Se bloquea la actuación si en ese ciclo
hay firmas pendientes o rechazadas sin etapa. Las reglas existentes de orden,
rol y empleado responsable continúan aplicándose.

Esto preserva las firmas como evidencia; no significa que toda firma histórica
autorice por sí sola a saltar una etapa diferente de un flujo nuevo.

### 4.4 Bandejas y autorización

- Las firmas de etapas ya autorizadas no se vuelven a excluir por la comparación
  heredada entre estado del proyecto y estado del cargo.
- En revision y En revisión permiten evaluar el flujo configurado, manteniendo
  los controles de etapa actual, ciclo, rol y empleado.
- Borrador, Autoguardado, Subsanacion, Registrado, En curso, Finalizado y Cancelado
  no habilitan una firma pendiente de inscripción por esa ruta.
- Otros estados históricos mantienen la comprobación anterior contra el cargo.
- Una firma heredada auténtica exige que todos estos campos sean nulos:
  `flujo_aprobacion_id`, `flujo_aprobacion_etapa_id`, `revision_ciclo`,
  `orden_revision`, `etapa_codigo` y `etapa_nombre`.
- La acción heredada exige firma pendiente y no eliminada, empleado asignado,
  rol activo coincidente y pertenencia efectiva del usuario a ese rol.

Las pantallas especiales de revisión solicitada y revisión final consultan
las firmas actuales autorizadas para proyectos con flujo. En el detalle,
las acciones específicas de código/dictamen se determinan a partir del cargo
de la revisión actual, evitando que el estado general En revision habilite
esas acciones en cualquier etapa.

### 4.5 Edición de flujos

La restricción inicial que impedía quitar etapas con firmas o adopciones fue
reemplazada el **22 de septiembre de 2026**, por petición del usuario. Se puede
volver a utilizar **Quitar** y guardar sobre el mismo flujo; no es necesario
crear otro flujo.

La eliminación en la configuración es un retiro lógico:

- Solo se hace efectiva al guardar. Quitar una tarjeta sin guardar no cambia la base.
- Las etapas retiradas dejan de formar parte de los nuevos envíos.
- Las revisiones ya enviadas conservan sus etapas, responsables y firmas,
  incluidas las pendientes; no se omiten aprobaciones de esos expedientes.
- Las firmas aprobadas, los ciclos y la evidencia de adopción no se borran.
- Las fichas y el indicador de progreso consultan el recorrido guardado del
  expediente, para seguir mostrando las etapas retiradas que le corresponden.
- Agregar una etapa al catálogo tampoco invalida una inscripción ya completada.
- Se pueden reutilizar el orden y el código de una etapa retirada en la
  configuración actual; sus identificadores históricos siguen siendo distintos.

La referencia técnica es `flujos_aprobacion_etapas.configuracion_vigente`:
`true` identifica la configuración actual y `null` una etapa retirada.
La relación `FlujoAprobacion::etapas()` devuelve solo la configuración actual;
las relaciones de firmas y evidencia siguen encontrando las etapas anteriores.
Los índices únicos incluyen esa marca para conservar códigos y órdenes
históricos sin bloquear su uso en la configuración actual.

El límite de conservación es el **envío que genera las firmas**. Un borrador
sin recorrido enviado utiliza la configuración guardada al enviarse. La
subsanación de una revisión iniciada conserva sus referencias históricas.
Esto no implementa versiones inmutables de todos los atributos del flujo ni
certifica el comportamiento de otros módulos que comparten el catálogo.

## 5. Pantallas, filtros, estadísticas y documentos

- Los listados de proyectos de administración, docente y director, y el detalle
  adaptado, muestran el estado general normalizado.
- Los filtros presentan opciones generales sin duplicar etiquetas y resuelven
  sus equivalentes históricos.
- El listado administrativo muestra además la revisión actual de inscripción:
  nombre de etapa, rol requerido, indicación de subsanación y advertencia si
  falta la referencia de etapa. Este resumen no concede autorización.
- Se incorporó la presentación de Registrado en los indicadores y etiquetas.
- El historial de movimientos conserva los nombres originales de los estados.
- Las comprobaciones de disponibilidad del informe intermedio y del cierre
  aceptan Registrado, manteniendo permisos y aprobaciones requeridas.
- La verificación de constancias acepta Registrado donde antes aceptaba
  En curso; Finalizado conserva el tratamiento de finalización.
- Los paneles mantienen claves internas como `en_curso` o `ejecucion` por
  compatibilidad, aunque la etiqueta visible pasa a Registrado/Registrados.

**Límite de las estadísticas:** el grupo `EstadosProyecto::REGISTRADOS`
incluye Registrado y En curso. No incluye actualmente Inscrito y Aprobado,
aunque el normalizador de presentación sí reconoce esos nombres. No debe
afirmarse equivalencia total de los conteos para todos los alias históricos.

## 6. Inventario de archivos

Las rutas siguientes son relativas a la raíz del repositorio.

| Área | Archivos modificados o añadidos |
| --- | --- |
| Normalización | `app/Support/Proyecto/EstadoGeneralProyecto.php` (nuevo), `app/Models/Proyecto/Proyecto.php` |
| Firmas y autorización | `app/Models/Proyecto/FirmaProyecto.php`, `app/Concerns/ResolvesFirmasPendientes.php`, `app/Concerns/ResuelveFirmaPorEtapa.php`, `app/Concerns/ReenviaDesdeSubsanacionPorEtapa.php`, `app/Livewire/Docente/Proyectos/ProyectosPorFirmar.php` |
| Configuración y adaptación | `app/Livewire/Configuracion/Flujos/ConfiguracionFlujosProyectos.php`, `app/Services/Proyecto/ProyectoLegacyWorkflowAdoptionService.php`, `app/Services/Proyecto/ProyectoWorkflowService.php` |
| Creación y revisión administrativa | `app/Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php`, `ListProyectosVinculacion.php`, `ListProyectosSolicitado.php`, `ListProyectoRevisionFinal.php` (estos tres en la misma carpeta) |
| Consulta docente/director | `app/Livewire/Docente/Proyectos/HistorialProyecto.php`, `app/Livewire/Docente/Proyectos/ProyectosDocenteList.php`, `app/Livewire/DirectorFacultadCentro/Proyectos/ListProyectos.php` |
| Constancias e informe intermedio | `app/Http/Controllers/Docente/VerificarConstancia.php`, `app/Services/InformeIntermedio/InformeIntermedioProyectoWorkflowService.php` |
| Estadísticas | `app/Services/Dashboard/MisFormulariosService.php`, `app/Services/Dashboard/PanelEstadisticoService.php`, `app/Support/Dashboard/EstadosProyecto.php`, `app/Support/Dashboard/Tramites/FamiliaProyectos.php`, `config/nexo.php` |
| Catálogo | `database/migrations/2026_09_21_000001_add_registered_project_state.php` (nueva) |

Vistas modificadas:

- `resources/views/components/dashboard/chip-estado.blade.php`
- `resources/views/livewire/director-facultad-centro/proyectos/list-proyectos.blade.php`
- `resources/views/livewire/docente/proyectos/historial-proyecto.blade.php`
- `resources/views/livewire/docente/proyectos/proyectos-docente-list.blade.php`
- `resources/views/livewire/inicio/dashboards/dasboard-docente.blade.php`
- `resources/views/livewire/inicio/dashboards/dashboard-director.blade.php`
- `resources/views/livewire/proyectos/vinculacion/list-proyectos-vinculacion.blade.php`

Pruebas añadidas o ajustadas:

- `tests/Unit/EstadoGeneralProyectoTest.php` (nueva).
- `tests/Unit/ProyectoRevisionActualTest.php` (nueva).
- `tests/Feature/FirmaProyectoWorkflowStageTest.php`.
- `tests/Feature/HistorialProyectoWorkflowStageResubmissionIntegrationTest.php`.
- `tests/Feature/InformeFinalINF001Test.php`.
- `tests/Feature/InformeIntermedioWorkflowTest.php`.
- `tests/Feature/ProyectoLegacyWorkflowAdoptionTest.php`.
- `tests/Feature/ProyectoWorkflowStageResubmissionTest.php`.
- `tests/Feature/ProyectosPorFirmarWorkflowStageApprovalTest.php`.
- `tests/Feature/ProyectosPorFirmarWorkflowStageAuthorizationTest.php`.
- `tests/Feature/ProyectosPorFirmarWorkflowStagePublicIntegrationTest.php`.
- `tests/Feature/ProyectosPorFirmarWorkflowStageRejectionTest.php`.

## 7. Migración y alcance sobre los datos

La [migración del catálogo](../database/migrations/2026_09_21_000001_add_registered_project_state.php)
crea cada uno de los cinco estados si no existe una fila activa con ese nombre
y limpia la caché de estados. No cambia identificadores de proyectos,
asignaciones, firmas ni historiales.

Se observó su ejecución correcta en la base local del proyecto y en la base
de pruebas durante esta sesión. No se ejecutó un despliegue en otros entornos.
Para aplicar específicamente esta migración en el entorno correspondiente:

```sh
php artisan migrate --force --path=database/migrations/2026_09_21_000001_add_registered_project_state.php
```

Su método `down()` no elimina los estados porque pueden estar referenciados
por expedientes e historial. Por ello un rollback no revierte los datos del
catálogo. Una reversión funcional requiere evaluar los proyectos que ya
utilicen Registrado antes de volver a una versión anterior del código.

Para completar el esquema de **la base de pruebas**, también se aplicaron
dos migraciones preexistentes pendientes de INF-001:

- `2026_09_02_000001_add_multiple_territorio_to_informe_final.php`.
- `2026_09_02_000002_add_plazo_to_informe_final_resultados.php`.

Estas dos últimas no se aplicaron a la base local principal como parte de
este trabajo. No se cambiaron archivos `.env` ni se incluyen credenciales
en esta documentación.

## 8. Validación registrada

El último grupo focalizado cuyo resultado se observó terminó con
**166 pruebas aprobadas y 882 aserciones**, en 13,68 segundos. Cubrió adopción,
autorización, aprobación, rechazo, ciclos, subsanación, separación visual,
normalización, disponibilidad de informe intermedio y casos seleccionados
de cierre. No corresponde a toda la suite del repositorio.

Entre los escenarios verificados se encuentran:

1. Creación con flujo establecido y aprobación hasta Registrado sin adopción.
2. Conservación de aprobaciones anteriores al adaptar y continuar el trámite.
3. Bloqueo de repetición de aprobaciones históricas y de firmas huérfanas.
4. Bloqueo de etapas futuras, ciclos anteriores y estados que no permiten firmar.
5. Reenvío desde subsanación manteniendo evidencia del ciclo anterior.
6. En la validación inicial se comprobó el bloqueo de eliminación; la actualización
   del 22 de septiembre lo sustituye por retiro lógico con conservación del recorrido.
7. Compatibilidad de Registrado y En curso para habilitar el cierre.

Se realizaron comprobaciones de sintaxis PHP y `git diff --check` durante la
implementación. Una tanda adicional de pruebas de paneles, adopción y estados
se inició después de ajustes menores de presentación/filtros; no se conservó
un resultado confirmado de esa ejecución. No se atribuye a esa tanda el
resultado de 166 pruebas ni se certifica una revisión visual completa por rol.

### Fallos preexistentes encontrados en ejecuciones más amplias

Una ejecución más amplia terminó con 263 aprobadas y tres fallidas. Los tres
fallos de `InformeFinalINF001Test` se reprodujeron también utilizando las clases
de aplicación de `HEAD`, sin los cambios de esta implementación:

- `test_cupos_por_sexo_y_resumen_planificado_registrado_pendiente`: campos
  requeridos faltantes en el estudiante manual.
- `test_instrumento_de_contraparte_se_precarga_en_anexos_sin_duplicar_archivo`:
  diferencia en el contenido esperado de la carta de intenciones.
- `test_ods_planificado_es_solo_lectura_y_ods_de_ejecucion_permanece_editable`:
  diferencia en el texto esperado del registro del proyecto.

Otra ejecución detectó fallos de renderizado de paneles por marcadores de
conflicto de Git ya presentes en `HEAD` en
`app/Livewire/Inicio/Dashboards/DasboardDocente.php`. Ese archivo no se corrigió
como parte de este alcance. El fallo adicional de una configuración de prueba
sin responsable fijo sí se corrigió y el escenario pasó en la tanda focalizada.

Estos resultados son el registro de lo observado durante la implementación,
no una afirmación de que toda la suite esté actualmente en verde.

## 9. Límites y trabajo pendiente

| Pendiente | Alcance e implicación |
| --- | --- |
| Configuración del flujo del caso inicial | Revisar correspondencia entre nombre de etapa, cargo, rol y responsable. La nueva presentación no cambia esas asignaciones. |
| Atomicidad del envío inicial | **Resuelto el 23 de septiembre** (sección 11): el envío es una sola transacción. |
| Límite de candidatos en bandeja | **Resuelto el 23 de septiembre** (sección 11): se quitó el límite y la etapa actual se filtra en SQL. |
| Versionado de flujos | Se permite retirar etapas conservando sus referencias y el recorrido enviado; no se implementaron versiones inmutables de todos los atributos. |
| Concurrencia | **Resuelto el 23 de septiembre** (sección 11) para el flujo por etapas: se bloquea el expediente antes de decidir. Las rutas heredadas sin flujo no cambiaron. |
| Equivalencia histórica de cargos | El bloqueo de repetición utiliza la correspondencia existente; no resuelve toda ambigüedad semántica de una adaptación. |
| Conteos y alias | Evaluar si Inscrito/Aprobado deben incorporarse también al grupo estadístico de registrados. |
| Otros módulos | No se certificó una migración integral de ENF, PPS, pasantías ni de sus filtros propios. |
| Validación completa | Resolver los fallos preexistentes, confirmar la última tanda y realizar revisión visual por rol antes de certificar el conjunto. |

Las pruebas aportan evidencia de compatibilidad para los recorridos cubiertos.
No equivalen a una garantía absoluta para cualquier configuración o ejecución
concurrente. Los cambios refuerzan la separación de conceptos y las validaciones
sin reescribir las firmas históricas, y dejan explícitos los puntos que todavía
necesitan revisión.

## 10. Actualización del 22 de septiembre: quitar etapas del mismo flujo

Archivos adicionales o nuevamente ajustados:

- `app/Models/Proyecto/FlujoAprobacion.php`: filtra la configuración vigente.
- `app/Models/Proyecto/FlujoAprobacionEtapa.php`: interpreta la marca de vigencia.
- `app/Livewire/Configuracion/Flujos/ConfiguracionFlujosProyectos.php`: retira
  etapas al guardar, conserva sus campos y libera la configuración para nuevos envíos.
- `app/Models/Proyecto/Proyecto.php`: `etapasDelExpediente()` reconstruye las
  etapas desde firmas y evidencia; fichas y progreso utilizan ese recorrido.
- `app/Services/Proyecto/ProyectoWorkflowService.php`: comprueba la inscripción
  completada contra el recorrido del expediente.
- `resources/views/livewire/configuracion/flujos/configuracion-flujos-proyectos.blade.php`:
  explica el alcance de Quitar y Guardar.
- `tests/Feature/FirmaProyectoWorkflowStageTest.php` y
  `tests/Feature/ProyectoLegacyWorkflowAdoptionTest.php`: conservación histórica,
  reutilización de código/orden, nuevos envíos y continuidad de aprobación.
- `tests/Feature/HistorialProyectoWorkflowStageResubmissionIntegrationTest.php`:
  reenvío desde subsanación conservando etapas retiradas y aprobaciones anteriores.

Migración adicional:

```sh
php artisan migrate --force --path=database/migrations/2026_09_22_000001_allow_retiring_workflow_stages.php
```

Aplicada en la base local y en `vinculacion_testing`. Añade la marca e índices,
sin retirar etapas existentes ni modificar firmas. Los índices sustitutos se
crean antes de quitar los anteriores para mantener el soporte de la clave
foránea. El rollback se bloquea si ya existen etapas retiradas, para no perder
el historial ni reactivar pasos por accidente.

Validación final de esta actualización: **164 pruebas aprobadas, 880 aserciones**
(10,40 segundos), en la base `vinculacion_testing`. Incluye las familias de
pruebas de firmas por etapa, adopción, autorización, aprobación/rechazo, nuevo
ciclo, subsanación pública, informe intermedio y disposición del panel de
proyectos. Se verificaron además sintaxis PHP y `git diff --check`.

Los casos específicos comprueban que una etapa retirada sigue apareciendo en
la revisión histórica, que el expediente puede completar sus aprobaciones y
reenviarse desde subsanación, que una adopción completada conserva su evidencia
y que un proyecto nuevo genera firmas solo para las etapas vigentes. La cifra
corresponde a este grupo focalizado, no a la suite completa ni al grupo anterior
de 166 pruebas, que tenía una selección diferente.

### Corrección posterior: las etapas reaparecían al guardar

Se reprodujo un defecto del retiro lógico al guardar una selección parcial:
`Eloquent\Collection::except()` reindexaba la colección de etapas conservadas.
La búsqueda posterior por ID no las encontraba y creaba filas nuevas, mientras
las anteriores permanecían vigentes con código/orden temporal. El resultado
era una configuración con más etapas de las que el usuario había dejado.

Se corrigió `syncFlowStages()` reconstruyendo las claves por ID después de
excluir las etapas retiradas. Al guardar se actualizan las filas conservadas
y se retiran únicamente las omitidas, sin recrear las existentes. La prueba
`test_quitar_guardar_y_recargar_conserva_solamente_las_etapas_elegidas` reproduce
quitar dos de cuatro etapas, guardar, recargar y volver a guardar sin cambios;
comprueba IDs, orden y cantidad total de filas. No se realizó una limpieza
automática de posibles duplicados generados antes de esta corrección.

## 11. Actualización del 23 de septiembre: panel, bandejas y etapa actual

### Problema observado

Un informe final de un proyecto FORM-DVUS-015 llegaba a la bandeja de tareas
y al contador de la barra, pero el panel mostraba «No tienes nada pendiente
de revisar». Se reprodujo con datos locales (informe final del proyecto 64,
etapa «Vinculacion xd»): bandeja 1, contador 1, panel 0.

La revisión encontró que la separación entre estado general y etapa
(secciones 2 y 4) no se había trasladado a las métricas del panel, que
seguían deduciendo la etapa a partir del estado:

| Defecto | Causa | Corrección |
| --- | --- | --- |
| Informes pendientes ausentes del panel | `PendientesRevisionService` solo consideraba firmas de `Proyecto` | Incluye las de `DocumentoProyecto` (informe intermedio y final) |
| Etapa vacía en «Esperan tu revisión» | Leía `$proyecto->estadoActual`, que por el accessor homónimo devuelve un `TipoEstado` | Usa `etapa_nombre` de la firma (o el cargo, en firmas antiguas) |
| «En cola» por etapa vacío para FORM-DVUS-015 y erróneo para FORM-DVUS-001 | Comparaba el estado del proyecto con el del cargo; con «En revision» solo coincidía con el cargo 5 | Cuenta la etapa actual (`EtapaActualFirma`) |
| «Trámites detenidos» con etapa equivocada | `MIN()` alfabético entre todas las firmas pendientes | Solo la etapa actual; incluye informes |
| Días de espera y tiempos por etapa inflados | Se medían desde la creación de la firma, que ocurre al enviar | Se miden desde la aprobación de la etapa anterior del ciclo |
| «Sin estado» en las listas del panel de director | Mismo accessor homónimo | Usa `estado_general` |
| Proyectos finalizados contados en «Informe final» | La existencia del informe prevalecía sobre Finalizado | Finalizado prevalece |

`App\Support\Proyecto\EtapaActualFirma` expresa en SQL el criterio de
`Proyecto::firmaEsActualEnFlujoPorEtapa()`: ciclo más reciente, sin etapas
anteriores pendientes o rechazadas, sin firmas huérfanas en el ciclo y con el
proyecto fuera de Borrador, Subsanacion, Registrado, Finalizado y Cancelado.
Las firmas antiguas sin flujo conservan la comparación entre estado y cargo.
También calcula la fecha de llegada a la etapa.

### Otros formularios

- **Pasantías (FORM-DVUS-013):** sus revisiones no aparecían en la bandeja,
  el contador ni el panel. `Pasantia::pendientesParaUsuario()` combina
  `usuarioPuedeRevisar()` con la exigencia de `PasantiaWorkflowService::aprobar()`
  de que la firma sea del empleado del usuario. La bandeja las lista con un
  enlace a su detalle, donde se aprueban o devuelven.
- **ENF (016/018):** la bandeja, el contador y el panel usaban tres consultas
  distintas. `EnfRevision::pendientesParaUsuario()` es ahora la única y
  reproduce `EnfWorkflowService::puedeRevisar()`. El contador ya no excluye los
  informes (que se revisan con la acción aprobada) y el panel ya no excluye
  otros códigos de formulario.
- **ENF, autorización:** `puedeRevisar()` permitía decidir una etapa posterior
  de un ciclo devuelto a subsanación, porque sus revisiones seguían pendientes.
  Ahora lo impide hasta el reenvío.
- **PPS (FORM-DVUS-014):** sin cambios de criterio; en el panel, la espera se
  mide desde la última aprobación del ciclo vigente.

### Archivos

| Área | Archivos |
| --- | --- |
| Criterio común | `app/Support/Proyecto/EtapaActualFirma.php` (nuevo) |
| Envío, decisiones y bandeja | `app/Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php`, `app/Concerns/ResuelveFirmaPorEtapa.php`, `app/Concerns/ResolvesFirmasPendientes.php` |
| Panel | `app/Services/Dashboard/PendientesRevisionService.php`, `app/Services/Dashboard/PanelEstadisticoService.php`, `app/Support/Dashboard/Tramites/FamiliaProyectos.php` |
| Consultas de pendientes | `app/Models/Pasantia.php`, `app/Models/ENF/EnfRevision.php`, `app/Services/ENF/EnfWorkflowService.php` |
| Bandeja y contador | `app/Livewire/Docente/Proyectos/ProyectosPorFirmar.php`, `app/Clases/DataNavBar.php` |
| Vistas | `dashboard.blade.php`, `dashboard-director.blade.php` (en `resources/views/livewire/inicio/dashboards/`), `resources/views/livewire/docente/proyectos/proyectos-por-firmar.blade.php` |
| Pruebas | `tests/Feature/Dashboard/PendientesRevisionTest.php`, `tests/Feature/Dashboard/EtapaActualMetricasTest.php` y `tests/Feature/ProyectoWorkflowConsistenciaTest.php` (nuevas), `tests/Feature/Dashboard/PanelesRenderizanTest.php`, `tests/Feature/InformeFinalINF001Test.php` |

No hay migraciones nuevas ni cambios de datos.

### Validación

- Las 14 pruebas nuevas pasan. Las 9 que no dependen de métodos nuevos
  (panel e indicadores) se ejecutaron también contra el código anterior y
  fallan por los defectos descritos.
- Las dos pruebas de recorrido completo de `InformeFinalINF001Test` estaban
  desactualizadas: desde `d74ad09`, «Marcar completo» del paso 8 ya envía el
  informe al flujo de cierre, y la prueba lo reenviaba (403). Se ajustaron.
- Suite completa: 824 aprobadas, 17 fallidas y 38 omitidas. Las 17 fallan
  también en `HEAD` sin estos cambios (allí son 19, contando las dos anteriores):
  `EnfDocumentoArchivoTest` (1), `FichaFirmaDelFirmanteRealTest` (2),
  `FormDvus014PdfLayoutTest` (2), `FormDvus014PdfRenderTest` (1),
  `InformeFinalINF001Test` (3), `InformeFinalInf001FormatoOficialTest` (2),
  `NewUserOnboardingTest` (2), `PpsServicioSocialWorkflowTest` (1) y
  `ProyectoVinculacionFormularioTest` (3).

### Pendientes de la sección 9 resueltos

**Atomicidad del envío inicial.** `CreateProyectoVinculacion::enviarPorFlujoDeEtapas()`
confirmaba las firmas en su propia transacción y registraba después el estado
y la validación final. Si fallaba algo en ese tramo, el proyecto quedaba en
Borrador con firmas pendientes: fuera de toda bandeja, y con el reenvío
bloqueado por `validarSinFirmasPreviasParaEnvioPorEtapa()` («Contacte a
administración»). Ahora bloqueo del proyecto, vinculación con el flujo, firma
del coordinador, firmas por etapa, estado «En revision» y validación final
forman una sola transacción; los correos a los revisores salen después de
confirmarla. El bloqueo también impide que dos envíos simultáneos (doble clic)
creen dos recorridos. El reenvío desde subsanación ya era atómico.

**Concurrencia.** `aprobarFirmaPorEtapa()` y `rechazarFirmaPorEtapa()`
bloqueaban solo la firma. Cuando una etapa se envía a todos los usuarios de un
rol hay varias firmas candidatas, y dos revisores podían decidir a la vez cada
uno la suya. Ahora se bloquean el proyecto y, si lo hay, el documento antes que
la firma: el mismo orden que `crearNuevoCicloDesdeFirmaRechazada()`. El
proyecto se resuelve antes de abrir la transacción para que la instantánea de
InnoDB se tome después de esperar el bloqueo. Cubre la bandeja, el historial y
la revisión de informes, que usan ese mismo trait. Las aprobaciones heredadas
(firmas sin flujo, de expedientes no adaptados) no cambiaron.

**Límite de 250 candidatas.** `firmasPorEtapaDisponiblesIds()` tomaba las 250
firmas pendientes más recientes del revisor y luego filtraba la etapa actual
en PHP. Como todas las firmas del recorrido se crean al enviar, quien revisa
las últimas etapas acumula una por expediente en curso: pasadas 250 se perdían
las más antiguas, que son las que ya le habían llegado. Ahora `EtapaActualFirma`
(movida a `app/Support/Proyecto/`) filtra la etapa actual en SQL y
`canActOnWorkflowStageFirma()` sigue decidiendo sobre lo que queda; no hay
límite. En la prueba con 260 firmas del mismo revisor, la bandeja pasó de
1,30 s a 0,07 s. El panel, además, reutiliza la lista dentro de la misma
petición en lugar de calcularla cuatro veces.

Pruebas: `tests/Feature/ProyectoWorkflowConsistenciaTest.php` (3 pruebas; las
tres fallan con el código anterior). Suite completa después de estos cambios:
827 aprobadas, las mismas 17 fallidas preexistentes y 38 omitidas.

**Observación sobre los datos importados.** Con la base local (dump de
producción), el contador del menú y el panel coinciden en las 387
combinaciones de usuario y rol. Los 9 proyectos heredados en revisión (29, 30,
47, 48, 50, 51, 52, 53 y 62) no aparecen en ninguna bandeja: el usuario de su
firmante actual no tiene el rol del cargo. Se resuelven al adaptarlos al flujo;
no se modificaron.

### Correcciones a lo registrado antes

- La sección 8 da por aplicadas en `vinculacion_testing` las migraciones
  `2026_09_21_000001` y `2026_09_22_000001`. El 23 de septiembre esa base tenía
  ocho migraciones pendientes, entre ellas esas dos, y las pruebas de flujos
  fallaban por la columna `configuracion_vigente`. Se aplicaron solo en esa base.
- Los marcadores de conflicto de `DasboardDocente.php` citados en la sección 8
  ya no existen.

## 12. Panel para todos los formularios

NEXO tendrá más de treinta formularios, cada uno con su flujo y sus etapas. El
panel dibujaba un «carril» con las fases propias de cada familia de trámites
(`FamiliaTramite`), lo que no escala: con treinta formularios serían treinta
recorridos distintos. Se sustituyó por una vista que no conoce ningún
formulario y trabaja solo con lo que todos comparten:

| Concepto | Qué es | Dónde |
| --- | --- | --- |
| Estado general | Borrador, En revisión, Subsanación, Aprobado, Finalizado (y Otros) | `App\Support\Dashboard\EstadoGeneral` |
| Etapa actual | La etapa que el trámite espera ahora | `EtapaActualFirma` y, en ENF, `EnfRevision::esperandoDecision()` |
| Espera | Días desde que la etapa le llegó | Aprobación de la etapa anterior del mismo ciclo |

`EstadoGeneral` traduce cualquier nombre de `tipo_estado`. Un nombre
desconocido cuenta como En revisión si es el estado de algún cargo de firma
(los flujos por cargo crean uno por etapa), y como Otros en caso contrario.
PendienteInformacion (proyectos anteriores al sistema) y Cancelado van a Otros.

### Qué muestra

En los paneles de administración y de director con ámbito institucional:

- **Estado de los trámites:** totales por estado general de todos los
  formularios y una tabla formularios × estado, agrupada por tipo de acción. Solo
  aparecen los formularios con trámites. Si uno no puede acotarse al centro
  (PPS y pasantías guardan la facultad como texto), se marca «cifra
  institucional».
- **Recorrido de un formulario:** se elige uno a la vez (por defecto, el que
  más trámites tiene esperando). Muestra las etapas de su flujo configurado,
  separadas por proceso (inscripción, informe intermedio, cierre), con cuántos
  trámites esperan en cada una. Las esperas en etapas retiradas van aparte, y
  los expedientes sin adaptar al flujo se agrupan por cargo.
- **Dónde se atascan los trámites:** las etapas con más trámites esperando, de
  todos los formularios, con su código y el tiempo de espera máximo.

«Salud del flujo» y «Trámites detenidos» siguen siendo de proyectos.

### Cómo se da de alta un formulario

En `config/nexo.php`, `dashboard.formularios`:

- Si usa el motor común (`App\Concerns\TieneFlujoPorEtapas`: estado en
  `estado_proyecto` y firmas en `firma_proyecto`), basta una entrada con
  `FormularioMotorComun`: código, nombre, tipo de acción, modelo y, si existen,
  columnas de autor, centro y departamento. No hace falta escribir una clase.
- Si tiene un motor propio, necesita una clase que implemente
  `App\Support\Dashboard\Formularios\FormularioPanel` (como `FormularioEnf`).

Las etapas del recorrido se leen del flujo activo cuyo `codigo_formulario`
coincide con el del formulario. Los proyectos sin tipo de acción aparecen como
«Proyectos sin tipo de acción» (`PROYECTO-SIN-TIPO`).

Se eliminaron `FamiliaTramite`, `FamiliaPorEstados`, `FamiliaProyectos`,
`FamiliaPps`, `FamiliaEnf`, `RegistroFamiliasTramite`, el componente
`carriles-tramite` y `FamiliasTramiteTest`. Los reemplazan `RegistroFormularios`,
`PanelFormulariosService` y los componentes `estado-tramites`,
`recorrido-formulario` y `atascos`.

### Otros defectos corregidos en el panel

| Defecto | Corrección |
| --- | --- |
| «Proyectos vigentes» contaba todos los proyectos, incluidos borradores y finalizados | Cuenta los registrados y en ejecución |
| El aviso «N esperan tu revisión» del panel de administración llevaba a la revisión solicitada, no a la bandeja de donde sale la cifra | Lleva a la bandeja personal |
| La actividad reciente mostraba los informes como «Documento» y sin enlace (`proyecto_documento` no tiene nombre) | «Informe Final · proyecto», con enlace al historial |
| Con el estado fijo en «En revision», el historial no decía en qué etapa quedaba el proyecto | Los comentarios de envío y avance nombran la etapa |
| Los pendientes de PPS y ENF no tenían enlace | PPS a su detalle; ENF a la bandeja |
| «Mis formularios» no incluía pasantías y no contaba un PPS aprobado en ninguna tarjeta | Incluye pasantías y usa `EstadoGeneral` |
| «Requiere tu atención» solo buscaba devoluciones entre los 15 trámites visibles | Busca en todos |
| «Ver más trámites» aparecía con exactamente 15 aunque no hubiera más | Solo si hay más |
| «Salud del flujo» pintaba en verde «0 días» con dos firmas | Con menos de 3 revisiones resueltas la barra va en gris y se advierte |
| Proyectos antiguos «En revision» sin ninguna firma pendiente no figuraban en ninguna espera | Aparecen como «En revision, sin firma pendiente» entre los no adaptados |

Pruebas nuevas: `tests/Feature/Dashboard/PanelFormulariosTest.php` (8) y
`tests/Feature/Dashboard/PanelDetallesTest.php` (4). Además se revisó el panel de
administración renderizado con los datos importados.
