# Estados generales y etapas de los flujos de proyectos

Fecha de documentación: 22 de septiembre de 2026.

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
| Atomicidad del envío inicial | La sincronización de firmas y la actualización posterior del estado aún no forman una única transacción global. |
| Límite de candidatos en bandeja | Permanece el límite de 250 candidatos previo a parte del filtrado; requiere revisión para volúmenes mayores. |
| Versionado de flujos | Se permite retirar etapas conservando sus referencias y el recorrido enviado; no se implementaron versiones inmutables de todos los atributos. |
| Concurrencia | No se demostró exclusión completa para aprobaciones simultáneas entre distintas firmas candidatas de una misma etapa. |
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
