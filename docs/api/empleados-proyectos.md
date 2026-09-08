# API de proyectos por empleado

Esta API permite a un consumidor externo consultar los proyectos publicados para un empleado. La operación es de solo lectura y conserva exactamente cuatro campos por proyecto.

La URL base se configura en el consumidor; la aplicación no publica una URL fija en este contrato. En los ejemplos se usa `{{base_url}}`.

## Endpoint

```http
GET {{base_url}}/api/empleados/{identificador}/proyectos
```

`identificador` es obligatorio y debe ser una cadena de hasta 80 bytes, sin espacios al inicio o al final. La aplicación resuelve el valor con esta prioridad inequívoca:

1. coincidencia exacta con `empleado.numero_empleado`;
2. si no existe y el valor contiene solo dígitos, coincidencia con `empleado.id`;
3. si tampoco existe, coincidencia con `empleado.user_id`.

Los ceros iniciales se eliminan únicamente para las búsquedas numéricas de `id` y `user_id`. La búsqueda por `numero_empleado` conserva el valor recibido. Un valor numérico que no cabe en el entero de la plataforma no se convierte a entero y no encuentra por `id` o `user_id`.

No hay parámetros de consulta definidos, no hay cuerpo de solicitud y no hay paginación configurada por este endpoint.

## Autenticación

Enviar uno de estos encabezados:

```http
Authorization: Bearer {{token}}
Accept: application/json
```

o:

```http
X-NEXO-API-TOKEN: {{token}}
Accept: application/json
```

Si ambos están presentes, se evalúa primero el Bearer. `Accept: application/json` solicita respuestas JSON y debe incluirse en todas las solicitudes de consumidores.

### Tokens almacenados

Se crean desde Configuración > Tokens de acceso, siempre que el usuario tenga el permiso web `configuracion.integraciones-api`. El valor completo se genera una sola vez con el prefijo `nexo_`; la base de datos conserva su hash SHA-256, junto con nombre, prefijo, creador, fecha de expiración, último uso y fecha de revocación.

El token debe estar vigente, asociado al alcance `empleados.proyectos` y ese alcance debe existir, estar asociado y tener `activo = true`. Una fecha de expiración pasada o igual al momento de la solicitud invalida el token. La revocación también lo invalida. Un token almacenado conocido que esté revocado, expirado o sin alcance no puede ser reautorizado mediante el token legado.

### Token legado

`NEXO_API_TOKEN` es una configuración de compatibilidad. Solo se usa cuando el valor recibido no corresponde a ningún token almacenado. Si coincide, permite esta ruta sin comprobar el alcance almacenado, porque no existe un registro de alcance para este secreto legado. No se documenta un formato, expiración, revocación o seguimiento de último uso para este token.

Nunca incluir tokens reales en una colección exportada, variables compartidas, repositorio, logs o ejemplos.

## Respuesta exitosa

Con proyectos:

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "data": [
    {
      "nombre_proyecto": "Programa comunitario de ejemplo",
      "codigo_proyecto": "EJEMPLO-001",
      "rol": "Coordinador",
      "estado": "en curso"
    },
    {
      "nombre_proyecto": "Proyecto educativo de ejemplo",
      "codigo_proyecto": "EJEMPLO-002",
      "rol": "Integrante",
      "estado": "aprobado"
    }
  ]
}
```

Sin proyectos publicados:

```json
{"data": []}
```

Los únicos campos de cada elemento son `nombre_proyecto`, `codigo_proyecto`, `rol` y `estado`. Los dos primeros pueden ser `null` según el esquema de datos.

## Estados y proyectos publicables

La API usa equivalencias exactas, comparando el nombre en minúsculas y con espacios exteriores recortados:

| Estado publicado | Salida |
|---|---|
| `Finalizado` | `finalizado` |
| `Aprobado` | `aprobado` |
| `En curso`, `Coordinador Proyecto`, `Enlace Vinculacion`, `Jefe Departamento`, `Director centro`, `En revision`, `En revisión`, `En revision final`, `En revisión final`, `Subsanacion`, `Subsanación`, `Inscrito`, `Actualizacion realizada`, `Actualización realizada`, `Informe Final Habilitado` | `en curso` |

Se excluyen los estados `Rechazado`, `Cancelado`, `Borrador`, `Autoguardado` y cualquier estado no incluido en la tabla de equivalencias. En particular, `En revision final` e `Informe Final Habilitado` no se clasifican como `finalizado`. Tampoco se aceptan coincidencias parciales como `Finalizado parcialmente` o `No aprobado`.

## Participaciones y duplicados

No se incluyen participaciones cuyo `empleado_proyecto.deleted_at` no sea nulo. Si existen varias participaciones activas para el mismo proyecto, la respuesta contiene una sola fila y conserva la participación activa con el mayor ID del pivote. Los proyectos se ordenan por ID ascendente; el orden es una propiedad técnica validada, no un criterio funcional para consumidores.

Los proyectos eliminados lógicamente y los proyectos sin un estado actual válido tampoco se publican.

### Verificación real del empleado 12280

En la verificación de la base de datos, `numero_empleado = 12280` tenía 16 participaciones activas: 10 sin estado actual, 2 en `En curso`, 1 en `Subsanacion`, 2 en `Finalizado` y 1 en `Borrador`. La API excluye las 10 sin estado y el borrador; `Subsanacion` se normaliza como `en curso`. Por ello, en esa verificación el resultado esperado era de **5 proyectos publicables**, con 3 salidas `en curso` y 2 salidas `finalizado`.

Este conteo describe la comprobación realizada y puede cambiar cuando cambien los datos; no se publican aquí nombres, códigos ni credenciales de esos registros.

## Errores

Todos los cuerpos definidos por la aplicación son JSON con una propiedad `message`.

### 401 Unauthorized

Token ausente:

```json
{"message":"Token de API requerido."}
```

Token inválido, expirado o revocado:

```json
{"message":"Token de API inválido."}
```

### 403 Forbidden

El token almacenado es válido, pero no tiene asociado el alcance activo `empleados.proyectos`:

```json
{"message":"El token no tiene el alcance requerido."}
```

### 404 Not Found

No existe un empleado que cumpla la prioridad de identificación:

```json
{"message":"Empleado no encontrado."}
```

### 422 Unprocessable Entity

El identificador está vacío, tiene más de 80 bytes o contiene espacios exteriores:

```json
{"message":"Identificador inválido."}
```

### 500 Internal Server Error

Una excepción al consultar los datos devuelve:

```json
{"message":"No fue posible consultar los proyectos."}
```

Un fallo de autenticación de infraestructura puede devolver, de forma independiente al controlador, `{"message":"No fue posible autenticar la solicitud."}`. No se debe interpretar ese caso como credencial inválida.

## Límites y seguridad

- El límite validado del identificador es 80 bytes.
- No hay filtros, paginación ni límite de cantidad de proyectos en el contrato corregido.
- El endpoint no modifica proyectos, empleados ni participaciones. El uso de un token almacenado actualiza `ultimo_uso_en` y registra una actividad de auditoría; los fallos de esas operaciones no bloquean la consulta.
- Los consumidores deben usar HTTPS, almacenar el token en un gestor de secretos, no enviarlo en URLs y rotarlo o revocarlo ante sospecha de exposición.
- Solicitar solo el alcance necesario y no exportar valores de variables Postman con secretos.
- No usar el token legado para nuevas integraciones; su compatibilidad no ofrece expiración, revocación por registro ni alcance individual.

## Ejemplos de solicitud

Bearer:

```bash
curl --request GET \
  --url "${BASE_URL}/api/empleados/${IDENTIFICADOR}/proyectos" \
  --header "Accept: application/json" \
  --header "Authorization: Bearer ${TOKEN}"
```

Encabezado legado o alternativo:

```bash
curl --request GET \
  --url "${BASE_URL}/api/empleados/${IDENTIFICADOR}/proyectos" \
  --header "Accept: application/json" \
  --header "X-NEXO-API-TOKEN: ${TOKEN}"
```

Los nombres `BASE_URL`, `IDENTIFICADOR` y `TOKEN` son variables de ejemplo; no contienen credenciales.

## Procedimiento seguro para una prueba temporal

1. Entra en Configuración > Tokens de acceso con un usuario cuyo rol activo tenga el permiso `configuracion.integraciones-api`.
2. Crea un token con un nombre identificable para la prueba, una fecha de expiración futura cercana y únicamente el alcance `empleados.proyectos`.
3. Copia el secreto usando el icono de copiar del aviso de generación. El secreto completo solo está disponible en ese momento.
4. En Postman usa un entorno local y asigna el valor únicamente a la variable secreta `token`. No exportes el valor ni lo guardes en archivos, repositorios, URLs o logs.
5. Envía `GET {{base_url}}/api/empleados/12280/proyectos` con `Accept: application/json` y `Authorization: Bearer {{token}}`. Comprueba `200 OK`, `data` y cinco elementos para una base que conserve la verificación indicada arriba.
6. Revoca el token desde Configuración > Tokens de acceso al terminar.
7. Elimina el valor de `token` en Postman y confirma con la misma solicitud que el token revocado devuelve `401` y `{"message":"Token de API inválido."}`.

No recuperes hashes ni uses el token legado para esta prueba. Si el usuario administrativo no tiene permiso o el alcance está inactivo, la interfaz debe impedir la creación o la solicitud debe devolver `403`, según el punto en que se detecte.

## Validación

Este contrato fue contrastado con la suite corregida `EmpleadoProyectosApiTest` y `ApiAccessTokensTest`: **37 pruebas y 117 aserciones**, ejecutadas contra una instancia MariaDB descartable. La documentación no incluye rutas, campos ni códigos que no estén cubiertos por ese comportamiento.
