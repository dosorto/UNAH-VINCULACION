# Verificación de pendientes — 2026-10-02

Referencia: `931b1de7`. Se revisaron los puntos de `docs/pendientes.md`, su código,
las migraciones, pruebas seleccionadas y la base local configurada en la aplicación.
No se modificaron datos ni se ejecutaron migraciones o seeders. No se consultó
producción ni se realizó una revisión visual en navegador.

## Implementado y retirado de la lista de defectos

- **Consolidación de Dirección DVUS:** migración `2026_10_02_000001` aplicada
  localmente; existe un `Director Vinculacion` y no existe `DIRECCION DIVUS`.
  La prueba aislada de consolidación y preservación del historial pasa.
- **Catálogo INF-001:** `ConceptosPresupuestoInf001::UNAH` contiene 12 conceptos.
  El wizard y el PDF usan ese catálogo. Solo permanece la uniformidad de las
  etiquetas antiguas de `AporteInstitucional`.
- **Base del 3 %:** comprobación del modelo con cantidades 1 y costos unitarios
  a=100000, b=20000, alimentación=50000 y combustible=30000: infraestructura
  y servicios devuelven **3600 cada uno**.
- **Consultorías:** usando texto heredado «c) Contratación de servicios
  profesionales» por 40000 y horas docentes por 100000, el modelo devuelve
  subtotal **140000** y servicios **3000**. No clasifica la consultoría como
  servicios públicos. Las filas actuales usan `concepto_codigo`.
- **Actividades por resultado:** existe la FK en la migración
  `2026_09_22_000002`, aplicada localmente. El wizard valida y guarda
  `informe_final_resultado_id`; el PDF agrupa por resultado y contempla filas
  antiguas sin vínculo. No se ejecutó el recorrido completo en navegador.
- **Resultado sin plazo:** su prueba actual pasa.
- **Migraciones locales:** todas las que muestra `php artisan migrate:status`
  figuran ejecutadas. La antigua indicación de `tipo_firma` pendiente ya no aplica.
- **EnfFirma:** no se encontraron escrituras directas en los servicios revisados.
  La sugerencia «si empieza a usarse» no describe un defecto actual y se retiró
  de los pendientes activos.

Se conservó la eliminación solicitada por el usuario de la entrada sobre PDFs
históricos con sello del coordinador. Esta auditoría no regeneró documentos.

## Evidencia local de puntos que permanecen

- 16 nombres de `tipo_estado` tienen dos registros activos cada uno. El seeder
  no busca borrados lógicos, pero ese hecho no prueba el origen de los duplicados.
- `PASANTIAS_ETAPA_01` (id 1) usa `Coordinador Proyecto`.
- Etapa 12, `ETAPA_05`, llamada `Director Vinculacion`, usa `Revisor Vinculacion`;
  etapa 18, `DIRECTOR_VINCULACION`, usa `Director Vinculacion`.
- La consulta de proyectos sin objetivo general y con actividades devuelve **32**.
  Es una señal para revisión, no prueba de pérdida de datos.
- El proyecto 64 tiene 15 caracteres en `programa_pertenece` y no contiene la
  etiqueta denunciada. Falta contrastar el entorno del reporte original.
- NOTIFICACIONES POA tiene un empleado asociado.
- El servicio INF-001 devuelve `true` para Desarrollo Local y Voluntariado,
  y `false` para Seguimiento a Egresados, Alineamiento Curricular, Prácticas
  Educativas Integrales, PPS/Voluntariado/Gestión de Riesgo y tipo nulo.

## Pruebas ejecutadas

```bash
php artisan test tests/Feature/ProyectoVinculacionFormularioTest.php tests/Feature/FormDvus001PdfLayoutTest.php tests/Unit/PasantiaFirmaCreadorTest.php tests/Feature/MergeLegacyDireccionDivusRoleMigrationTest.php
```

Resultado: **69 aprobadas, 4 fallidas, 231 aserciones**. Los cuatro fallos
corresponden a expectativas antiguas de beneficiarios, catálogo de anexos y
redacción de la nota del PDF; sus nombres están en `docs/pendientes.md`.

La selección de siete clases dependientes de MySQL se ejecutó con
`--stop-on-error`: se detuvo por credenciales rechazadas de `nexo@localhost`,
antes de ejecutar aserciones (1 error y 244 pendientes). Incluía las clases de
INF-001, firma real, onboarding, Pasantías, PPS y archivos ENF.
No se utilizó la base de la aplicación como sustituto de la base de pruebas.

## Hallazgo adicional

`ProyectoSeeder::run()` actualiza el flujo `PROYECTO_DEFAULT`, elimina sus etapas
y las recrea. La recomendación anterior de ejecutar `db:seed` completo sobre un
dump era demasiado amplia: también ejecuta `PersonalSeeder`, que sobrescribe
datos y roles. Ambos puntos quedan pendientes de corrección.
