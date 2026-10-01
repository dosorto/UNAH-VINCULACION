# Auditoría FORM-DVUS-001: pérdida de datos al guardar el borrador

Fecha: 2026-10-01. Lo que quedó pendiente está en `docs/pendientes.md`
(sección «Auditoría FORM-DVUS-001»).

## Síntoma

Proyectos con datos operativos completos (equipo, estudiantes, ODS, cronograma,
anexos) llegaban a la base de datos sin contrapartes, sin los textos de la
sección V, sin marco lógico, sin sitio de ejecución y con el presupuesto en cero.
El caso que lo destapó fue el proyecto #81 («Plan estratégico de desarrollo
urbano de El Paraíso»). El docente había llenado los 9 pasos y vio «Borrador
guardado».

## Causa raíz

El PDF no perdía datos: imprimía fielmente una base de datos incompleta. La
pérdida ocurría al escribir:

1. `autoGuardarBorrador()` guardaba **todo** el formulario en una sola
   transacción y se tragaba cualquier excepción (`report()` y un texto pequeño
   «Error al guardar»). Bastaba un valor que la base de datos rechazara en
   cualquier sección para revertir también los textos narrativos.
2. La validación no coincidía con el esquema. Por ejemplo, los compromisos de la
   contraparte se validaban `required|string` sin `max`, pero la columna era
   `VARCHAR(255)`. `tipo_entidad` es ENUM en el pivote y texto libre en el
   catálogo.
3. Desde que la contraparte entraba en memoria, cada autoguardado fallaba. Solo
   sobrevivía lo que se confirma por su cuenta: actividades (`saveActividad`),
   metas ODS, anexos y lo guardado antes de agregar la contraparte.
4. `borrador()` ignoraba el error, firmaba, mostraba «Borrador guardado» y
   redirigía. Así descartaba los textos, que solo existían en memoria.
5. «Enviar para firmar» validaba la memoria del formulario, no lo guardado, y
   podía enviar a revisión un expediente vacío.

El log local registra ese tipo de fallos tragados: `Data too long` en
`nombre_indicador` y `descripcion_acuerdos`, `Data truncated` en `tipo_entidad`,
`Out of range` en el aporte institucional y `Unknown column` por una migración
sin aplicar.

## Cambios

### Guardado del formulario (`CreateProyectoVinculacion`)
- **Autoguardado por secciones.** Cada sección (nombre, datos y textos,
  clasificación, equipo, contrapartes, actividades, marco lógico, presupuesto,
  espacios) se guarda en su propio savepoint. Si una falla, solo se revierte
  esa; el resto queda guardado.
  - `autoGuardarBorrador()` devuelve `bool`.
  - `seccionesNoGuardadas` alimenta un aviso rojo persistente que dice qué no se
    guardó y por qué.
  - Al fallar una sección se llama a `discardChanges()` sobre el modelo. Sin eso,
    el siguiente `update()` reintentaba el valor rechazado.
- **«Guardar borrador» y «Enviar» se detienen si algo no se guardó.** No
  muestran éxito ni redirigen.
- **Validación sobre lo guardado.** Antes de enviar (en `abrirModalEnviar` y
  `confirmarEnvio`) se valida el registro de la BD con
  `Proyecto::camposObligatoriosFaltantes()`.
- **Guardar un borrador ya no firma.** El coordinador firma al enviar.
- **Sin sesión no hay excepción.** Si no hay empleado en la sesión, el
  autoguardado lo informa en vez de lanzar una excepción.
- **Código muerto eliminado:** `saveCurrentStep()` y `saveStep1..9`.

### Esquema y validación
- Migración `2026_10_01_000001`: `descripcion_acuerdos`, `nombre_indicador` y
  `nombre_medio_verificacion` pasan a `TEXT`.
- Un `tipo_entidad` fuera de `EntidadContraparte::TIPOS` o un `tipo_documento`
  vacío se guardan como NULL. Al crear una contraparte se valida
  `tipo_entidad` con `Rule::in`.
- El aporte institucional tiene tope de 99 999 999,99 (`DECIMAL(10,2)`) en el
  paso 8 y en los inputs. El nombre del proyecto tiene `maxlength="255"`.

### PDF del FORM-DVUS-001/015
- Los ítems obligatorios vacíos se imprimen como «CAMPO OBLIGATORIO NO
  REGISTRADO» en rojo. Antes decían, por ejemplo, «Sin objetivo general
  especificado», que se confundía con una respuesta.
- Los proyectos en Borrador o Autoguardado llevan la marca de agua
  «BORRADOR — NO VÁLIDO» en todas las páginas.

### Firma sin sello de quien registra o coordina (regla global)
Quien llena o coordina el expediente firma **sin sello**, aunque tenga uno en
su perfil por otro cargo (caso: Director DVUS que también coordina proyectos).
El sello es exclusivo de las etapas administrativas.

- `TipoCargoFirma::CARGOS_SIN_SELLO` y `CargoFirma::admiteSello()` definen la
  regla.
- `FirmaProyecto` la aplica al guardar (evento `saving`). Cubre Vinculación,
  ficha de actualización, informes, Pasantías y cualquier módulo nuevo que firme
  en `firma_proyecto`.
- `FirmaProyecto::selloParaDocumento()` es la única forma de leer el sello para
  un documento. Ignora el sello guardado en firmas antiguas del coordinador, así
  que los PDF que se generan al vuelo salen bien sin migrar datos.
- La usan `firmas-fijas-proyecto.blade.php` y `InformeFinalPdfGenerator`. Se
  eliminó `firmas-dinamicas.blade.php`, que no se usaba.

### Permisos
- `unidad-academica.asignatura` (administrar asignaturas) se quitó a `docente`
  y a `Coordinador Proyecto`, en el seeder y con la migración
  `2026_10_01_000002`. En modo docente el menú ya no muestra
  Unidad Académica → Asignatura. Crear una asignatura desde el formulario no
  requiere ese permiso.

## Diferencias con el plan original

- La validación de lo guardado está en los puntos de entrada del envío y no
  dentro de `enviarPorFlujoDeEtapas()`. Dos tests invocan ese método con
  proyectos mínimos para probar la mecánica del flujo.
- No se agregó un guardado extra del presupuesto en `saveActividad()`: con el
  autoguardado por secciones, el presupuesto ya se guarda aparte justo después.
- Los resultados de mediano y largo plazo conservan su texto («Sin resultados…»)
  en el 001, donde son opcionales. El marcador de obligatorio solo aplica en el 015.

## Pruebas

- Tests nuevos:
  - `ProyectoVinculacionAutoguardadoTest` (aislamiento por secciones, borrador,
    envío, campos guardados, PDF);
  - `FirmaSinSelloDelCoordinadorTest` (regla, evento, lectura de firmas
    históricas, ficha, `agregarFirma`, INF-001);
  - `PermisoAdministrarAsignaturasTest`;
  - un caso en `ProyectosPorFirmarWorkflowStageApprovalTest` (etapa con cargo
    Coordinador).
- Tests ajustados:
  - `PasantiaFirmaCreadorTest`: la firma del creador ya no lleva sello.
  - `FormDvus001PdfLayoutTest`: el partial usa `selloParaDocumento`.
  - `ProyectoVoluntariadoFormularioTest`: sin sesión, el autoguardado informa
    el error.
- Suite completa: 913 pasan y 18 fallan. Los 18 fallaban igual antes de estos
  cambios (verificado con el código original); la lista está en
  `docs/pendientes.md`.
- Se generó con DomPDF el PDF de un borrador real. Muestra la marca de agua en
  las 4 páginas y los marcadores en los campos vacíos.

## SQL de solo lectura para fusionar los roles DVUS (producción)

Ejecutar antes de escribir la migración que fusiona `DIRECCION DIVUS` en
`Director Vinculacion` (ver `docs/pendientes.md`).

```sql
-- 1. Ambos roles (id, guard) y posibles variantes
SELECT id, name, guard_name, created_at
FROM roles
WHERE name IN ('Director Vinculacion', 'DIRECCION DIVUS')
   OR name LIKE '%DIVUS%' OR name LIKE '%DVUS%';

-- 2. Usuarios y permisos por rol
SELECT r.id, r.name,
       (SELECT COUNT(*) FROM model_has_roles m
         WHERE m.role_id = r.id AND m.model_type = 'App\\Models\\User') AS usuarios,
       (SELECT COUNT(*) FROM role_has_permissions rp WHERE rp.role_id = r.id) AS permisos,
       (SELECT COUNT(*) FROM users u WHERE u.active_role_id = r.id)          AS usuarios_con_rol_activo
FROM roles r
WHERE r.name IN ('Director Vinculacion', 'DIRECCION DIVUS');

-- 3. Usuarios de cada rol
SELECT r.name AS rol, u.id, u.name, u.email, (u.active_role_id = r.id) AS es_rol_activo
FROM model_has_roles m
JOIN roles r ON r.id = m.role_id
JOIN users u ON u.id = m.model_id
WHERE m.model_type = 'App\\Models\\User'
  AND r.name IN ('Director Vinculacion', 'DIRECCION DIVUS')
ORDER BY u.id, r.name;

-- 4. Usuarios con ambos roles
SELECT m1.model_id AS user_id
FROM model_has_roles m1
JOIN roles r1 ON r1.id = m1.role_id AND r1.name = 'DIRECCION DIVUS'
JOIN model_has_roles m2 ON m2.model_id = m1.model_id AND m2.model_type = m1.model_type
JOIN roles r2 ON r2.id = m2.role_id AND r2.name = 'Director Vinculacion';

-- 5. Permisos del duplicado que el canónico no tiene
SELECT p.name
FROM role_has_permissions rp
JOIN permissions p ON p.id = rp.permission_id
JOIN roles r ON r.id = rp.role_id
WHERE r.name = 'DIRECCION DIVUS'
  AND p.id NOT IN (SELECT rp2.permission_id FROM role_has_permissions rp2
                   JOIN roles r2 ON r2.id = rp2.role_id WHERE r2.name = 'Director Vinculacion');

-- 6. Referencias en flujos y firmas que hay que reapuntar
SELECT 'etapas (rol_revisor_id)' AS fuente, COUNT(*) AS n
  FROM flujos_aprobacion_etapas e JOIN roles r ON r.id = e.rol_revisor_id WHERE r.name = 'DIRECCION DIVUS'
UNION ALL SELECT 'firma_proyecto pendientes', COUNT(*) FROM firma_proyecto
  WHERE rol_requerido = 'DIRECCION DIVUS' AND estado_revision = 'Pendiente' AND deleted_at IS NULL
UNION ALL SELECT 'firma_proyecto históricas', COUNT(*) FROM firma_proyecto
  WHERE rol_requerido = 'DIRECCION DIVUS' AND estado_revision <> 'Pendiente'
UNION ALL SELECT 'enf_revisiones', COUNT(*) FROM enf_revisiones WHERE rol_requerido = 'DIRECCION DIVUS'
UNION ALL SELECT 'programa_revisiones', COUNT(*) FROM programa_revisiones WHERE rol_requerido = 'DIRECCION DIVUS';
```
