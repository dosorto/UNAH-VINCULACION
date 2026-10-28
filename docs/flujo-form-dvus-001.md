# Configuración inicial de FORM-DVUS-001

`FormDvus001FlujoSeeder` crea el recorrido Jefe Departamento → Enlace Vinculación → Director Centro → Revisor Vinculación → Director Vinculación, sin responsables precargados. Las tres primeras etapas aplican a inscripción; las dos últimas también a informes intermedios y cierre. Los códigos de etapa son estables e independientes del orden.

El seeder está incluido en `DatabaseSeeder`, después de los catálogos y roles. Para ejecutarlo por separado en una base con esas dependencias y las migraciones aplicadas:

```sh
php artisan db:seed --class='Database\Seeders\Proyecto\FormDvus001FlujoSeeder'
```

Si ya existe un flujo del formulario, conserva íntegramente su configuración, incluso sus responsables y códigos anteriores. No actualiza proyectos ni firmas existentes. Una migración normal no ejecuta seeders automáticamente.

Después de crear el flujo, una persona con permiso `configuracion.flujos` debe abrir **Configuración → Flujos de proyectos** y asignar los responsables. Se muestra un aviso persistente con las etapas pendientes hasta completar la configuración. Las etapas donde el emisor elige destinatario no requieren un responsable fijo precargado.

Los nuevos envíos y las adopciones legacy que necesitan etapas sin responsable fijo se bloquean con un mensaje explicativo. Las asignaciones históricas se conservan. La adaptación concilia el estado y las revisiones legacy con el flujo seleccionado:

- Conserva el estado de origen y su historial en la evidencia de adopción.
- Identifica etapas por cargo y, cuando la revisión lo registra, por rol; no usa la posición para acreditar aprobaciones.
- Solo excluye del recorrido las etapas cuya última revisión aplicable está aprobada. Una revisión posterior pendiente o rechazada invalida una aprobación anterior.
- Las etapas sin evidencia de aprobación se incluyen según el orden configurado, incluso si preceden al estado legacy. La vista previa distingue el estado de origen de la primera etapa que recibirá el proyecto.
- Bloquea correspondencias ambiguas, revisiones activas sin etapa equivalente y contradicciones entre el estado y una aprobación. No modifica firmas mientras exista un bloqueo.
- Al adoptar una subsanación conserva el rechazo en su etapa. El primer reenvío comienza en la etapa de retorno detectada y conserva después las demás etapas pendientes según su orden configurado. Esta excepción no cambia las reglas de los ciclos ordinarios.
- Un expediente históricamente registrado conserva su inscripción global sin inventar aprobaciones individuales. Un expediente cancelado no acredita inscripción completada.

La evidencia nueva usa versión 2. Las adopciones existentes se conservan; no se reparan automáticamente ni se vuelven a adoptar. Las aprobaciones legacy permanecen intactas, y solo se anulan las solicitudes pendientes sustituidas por el nuevo ciclo.


## Selección manual de etapa durante la adaptación

Una cuenta con rol `admin` o `Director/Enlace` y permiso `proyectos.historial` puede activar **Seleccionar etapa manualmente**. Debe indicar una etapa activa de inscripción sin aprobación comprobada sin tener que escribir una justificación. Los permisos y la etapa seleccionada se validan también en el servicio, dentro de la transacción.

La selección define la primera tarea; las demás etapas pendientes se conservan después, siguiendo su orden configurado. La vista previa muestra ese recorrido. No equivale a aprobar ni omitir etapas anteriores. En subsanación no se envía una notificación al adaptar: el proyecto sigue en subsanación y la etapa elegida recibe el reenvío del coordinador.

La evidencia de adopción registra la propuesta automática, la elección manual, el motivo y el usuario que la confirmó. Las correspondencias ambiguas entre firmas y etapas, las etapas eliminadas con revisiones pendientes y la falta de responsables siguen siendo bloqueos; seleccionar una etapa no los descarta. La firma del coordinador se conserva intacta y se excluye de la conciliación de revisiones administrativas.


## Retorno automático desde subsanación

Se prioriza una revisión rechazada explícita. Cuando el legacy reinició las firmas a pendiente, se consulta el último estado previo a la subsanación (omitiendo registros consecutivos de subsanación). Se interpreta con la configuración legacy: `En revision` corresponde a `Revisor Vinculacion` y `En revision final` a `Director Vinculacion`. La equivalencia busca primero el rol del flujo actual; solo las etapas sin rol explícito admiten coincidencia por cargo. Si el estado no tiene equivalencia única, se conserva el bloqueo y la opción de selección manual autorizada.

El retorno detectado recibe primero al reenviar. Las aprobaciones vigentes se excluyen del nuevo recorrido; las revisiones pendientes anteriores al retorno se conservan después de este. Una etapa de retorno que ya aparece aprobada produce un bloqueo por contradicción. No se generan aprobaciones a partir del estado previo ni se cambia el estado de subsanación al adaptar.


## Incorporación de firmas históricas al PDF

Solo una cuenta con rol `admin` y permiso `proyectos.historial` puede incorporar firmas anteriores durante la adaptación manual. `Director/Enlace` puede adaptar, pero no cargar firmas históricas. El servidor verifica esta restricción incluso si se manipula la solicitud.

Después de seleccionar la etapa de retorno, el administrador marca las etapas de tipo Aprobación anteriores a ella, selecciona al empleado que aprobó y, opcionalmente, indica la fecha histórica. Cada empleado debe tener una firma guardada cuya imagen esté disponible; no basta con un registro que apunte a un archivo inexistente. El selector permite buscar por nombre o correo y solo muestra empleados con firma guardada y el rol actual configurado para la etapa. El servidor vuelve a comprobar ese rol al confirmar; una etapa sin rol configurado no permite cargar firmas históricas. No se permite sobrescribir aprobaciones conservadas, regularizar un rechazo vigente ni registrar la etapa de retorno como aprobada.

Las firmas incorporadas se guardan como aprobaciones del ciclo inicial y se excluyen de las tareas pendientes. En subsanación, el proyecto conserva ese estado y al reenviar no repite las etapas regularizadas. La evidencia versión 3 conserva la persona elegida, el identificador de la firma guardada, la fecha histórica (o nula), el administrador, la fecha de incorporación y la justificación si fue registrada.

El PDF utiliza la firma guardada seleccionada y la leyenda **Firma histórica incorporada por administración**. No usa la fecha de incorporación como fecha de aprobación histórica ni la presenta como una firma electrónica nueva de esa persona. Se conserva la referencia a la imagen utilizada incluso si el perfil recibe una nueva firma; no se agrega un sello automáticamente. La operación es transaccional y no modifica proyectos ya adoptados.

La adaptación no solicita el motivo histórico de subsanación: conserva el comentario existente del expediente, si lo hay, y permite continuar cuando no está disponible.
