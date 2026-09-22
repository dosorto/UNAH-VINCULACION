# Revisión y pruebas de flujos de proyectos

Fecha: 22 de septiembre de 2026.

Implementación revisada: `a66c2248` (`ajustes adopcion de los flujos`).
El detalle funcional y el inventario de cambios están en
[Estados y etapas de proyectos](estados-y-etapas-proyectos.md).

## Resumen para revisión del equipo

La implementación separa el estado general del proyecto de su etapa de
revisión. El estado sigue `Borrador → En revision → Registrado → Finalizado`,
con devolución a Subsanacion y reenvío a En revision. La etapa y su responsable
proceden del recorrido de firmas. Los nombres antiguos se reconocen mediante
compatibilidad, sin reescribir masivamente los expedientes históricos.

La adaptación legacy conserva aprobaciones anteriores y bloquea recorridos
que repetirían una aprobación reconocida. Se refuerzan las comprobaciones de
empleado, rol, etapa actual y ciclo vigente; una firma con metadatos de flujo
y sin referencia de etapa no se trata como una firma legacy auténtica.

**Quitar etapas y guardar** modifica la configuración para nuevos envíos del
mismo flujo. Las revisiones iniciadas conservan sus referencias y firmas.
Se corrigió además la reindexación de la colección durante el guardado, que
provocaba recrear las etapas conservadas. No se limpiaron automáticamente
duplicados anteriores ni se reasignaron proyectos reales.

Durante esta auditoría se encontró y corrigió un acceso a categoría nula en
`VerificarConstancia::validarConstanciaEmpleado()`: un empleado sin categoría
no cumple los requisitos de esa constancia, pero ya no provoca una excepción
que revierta la aprobación del cierre. Esta corrección y sus pruebas ya están
incluidas en `a66c2248`.

## Preparación y alcance

- Base utilizada: `vinculacion_testing`, con el esquema de pruebas existente.
- Cada escenario crea sus propios proyectos, usuarios, etapas y documentos.
  Se utiliza `DatabaseTransactions` para revertir los datos al terminar.
- «Desde cero» significa expedientes nuevos de prueba, sin continuar proyectos
  reales ni reutilizar resultados de una aprobación anterior. No se ejecutó
  `migrate:fresh` ni se vació la base de datos.
- Los recorridos continuos simulan correo y almacenamiento mediante los
  mecanismos de pruebas de Laravel. No envían correos reales.
- La creación inicial del expediente se prepara con modelos de prueba y el
  envío nuevo invoca el método de envío del componente. No se afirma haber
  completado manualmente todos los campos en un navegador.

## Dos recorridos continuos verificados

Pruebas nuevas en `tests/Feature/InformeFinalINF001Test.php`:

- `test_recorrido_desde_cero_proyecto_nuevo_hasta_finalizado`.
- `test_recorrido_desde_cero_legacy_adaptado_hasta_finalizado`.

| Paso | Proyecto nuevo | Legacy adaptado |
| --- | --- | --- |
| Preparación | Borrador sin firmas de inscripción aprobadas | Firma anterior aprobada y otra pendiente, sin metadatos de etapa |
| Inicio | Envío mediante el componente de creación | Adopción desde la etapa pendiente |
| Revisión | Aprobación de la primera etapa | Conservación de la aprobación histórica |
| Devolución | Rechazo de la última etapa a Subsanacion | Rechazo de la etapa pendiente a Subsanacion |
| Reenvío | Nuevo ciclo desde la etapa rechazada | Nuevo ciclo desde la etapa rechazada |
| Registro | Aprobación y estado Registrado | Aprobación y estado Registrado |
| Informe intermedio | Carga, envío y aprobación; conserva Registrado | Mismo recorrido |
| Informe final | Completar mediante Livewire y generar PDF al enviar | Mismo recorrido |
| Corrección del cierre | Rechazo y reenvío sobre el mismo documento | Mismo recorrido |
| Finalización | Aprobación, proyecto Finalizado y documento Aprobado | Mismo recorrido |
| Evidencia conservada | Primera aprobación y rechazo del ciclo anterior | Comparación íntegra de los atributos persistidos de la firma histórica |

Resultado de estos dos escenarios: **2 aprobados, 43 aserciones**.
También verifican que no se duplique el documento final y que sus datos de
presentación de PDF dejen de marcarlo como borrador al aprobarlo.

## Comprobaciones complementarias

Se ejecutan las familias de pruebas de:

- Bandeja y autorización: empleado correcto, rol activo, responsable fijo,
  candidatos por rol, etapa futura y ciclos anteriores.
- Aprobación y rechazo: avance, finalización, rollback ante errores y
  conservación de firmas anteriores.
- Adaptación legacy: borrador, revisión, subsanación, inscripción completada,
  evidencia histórica y bloqueos de adaptación inconsistente.
- Configuración: quitar parcialmente, guardar y recargar con los mismos IDs,
  guardar otra vez sin duplicar, retirar etapas y continuar revisiones enviadas.
- Subsanación: reanudar la etapa rechazada, incluso si fue retirada del catálogo.
- Informes intermedio/final: disponibilidad, edición, envío, documentos,
  permisos de descarga y presentación de PDF.
- Formularios de vinculación y voluntariado.
- Constancias de registro/finalización: emisión por servicios, archivos,
  verificación, acceso y numeración, según los casos existentes.
- Presentación de estado general y progreso de proyectos.

## Fallos pendientes identificados

Ejecución conjunta final: **381 aprobadas y 6 fallidas**, **1.855 aserciones**,
en **43,81 segundos**. Son 387 pruebas seleccionadas, no toda la suite del
repositorio. Los dos recorridos continuos forman parte de este resultado.

No se certifica toda la funcionalidad como libre de defectos. La ampliación a
formularios detecta estos seis casos fallidos:

| Prueba | Resultado observado |
| --- | --- |
| `InformeFinalINF001Test::test_cupos_por_sexo_y_resumen_planificado_registrado_pendiente` | El escenario no satisface los campos requeridos del estudiante manual. |
| `InformeFinalINF001Test::test_instrumento_de_contraparte_se_precarga_en_anexos_sin_duplicar_archivo` | No coincide el contenido esperado de la carta de intenciones. |
| `InformeFinalINF001Test::test_ods_planificado_es_solo_lectura_y_ods_de_ejecucion_permanece_editable` | No coincide el texto esperado de la sección ODS. |
| `ProyectoVinculacionFormularioTest::test_resultado_sin_plazo_falla` | El paso 7 permite avanzar con plazo vacío. |
| `ProyectoVinculacionFormularioTest::test_beneficiario_vacio_se_convierte_en_cero` | Un valor vacío permanece vacío en vez de convertirse en cero. |
| `ProyectoVinculacionFormularioTest::test_calc_totales_no_lanza_error_y_calcula_subtotales` | Los totales calculados no coinciden con los esperados por la prueba. |

Los tres casos del formulario de vinculación se repitieron utilizando la clase
`CreateProyectoVinculacion` de `a66c2248^` mediante una sustitución temporal del
autoload, sin modificar el árbol de trabajo: **3 fallidos, 3 aserciones**.
Se confirma así que esos fallos también existen antes de este commit.
Los tres casos de INF-001 ya habían sido reproducidos contra las clases de
aplicación anteriores durante la revisión documentada en estados y etapas.
Requieren revisar las reglas y las expectativas de sus pruebas; no se
modificaron para forzar un resultado positivo.

## Migraciones necesarias al incorporar el cambio

1. `2026_09_21_000001_add_registered_project_state.php`: incorpora el catálogo
   general sin reescribir estados históricos.
2. `2026_09_22_000001_allow_retiring_workflow_stages.php`: añade la marca de
   configuración vigente y los índices para retirar etapas sin borrar referencias.

El código y estas migraciones deben incorporarse juntos. La segunda migración
bloquea su reversión si existen etapas retiradas; la primera no elimina los
estados del catálogo en `down()`. Ambas fueron aplicadas localmente durante
la implementación; este informe no acredita un despliegue en producción.

## Límites de la validación

No se realizó una navegación manual por todos los roles ni una prueba de carga
o concurrencia. Las pruebas transaccionales de recorrido no ejecutan un commit
real de producción para certificar la entrega de correo o los callbacks
`afterCommit`; las constancias se cubren adicionalmente mediante sus pruebas
específicas de servicios. Tampoco se certifican todos los módulos ENF, PPS,
pasantías y DAFT, aunque compartan partes del catálogo.

Permanecen los límites descritos en el documento de estados y etapas, entre
ellos la atomicidad del envío inicial, el límite de candidatos de bandeja y
la ausencia de versionado integral de todos los atributos del flujo.
