# Visor del FORM-DVUS-014 (PPS / Servicio Social)

La ficha se genera desde `storage/app/templates/form-dvus-014.docx`, el FORM-014
oficial de Word, igual que el FORM-DVUS-018. `FormDvus014DataMapper` llena las
celdas (fechas por día/mes/año, casillas con «X», la ubicación presencial o de
teletrabajo según la modalidad) y la firma de quien llena el formulario, que es
el coordinador(a) de la carrera. El supervisor y el estudiante firman a mano.

`FormDvus014DocumentService` convierte la copia temporal con LibreOffice y guarda
el PDF por la huella de la plantilla, los datos y la firma en
`storage/app/generated/form-dvus-014/{id}`; solo conserva el vigente.

La plantilla salió del Word oficial (`storage/app/FORM-014.-REGISTRO-PPS-Y-SERVICIO-SOCIAL-1.docx`)
con estos ajustes: sin el control «Page Numbers (Margins)» del encabezado (LibreOffice lo
dibuja como un círculo vacío; el número de página ya va en el pie), sin la hoja en blanco
final, con un texto vacío en cada celda de valor para que lo escrito herede Arial, y con
las casillas, fechas y horas centradas.

## Cartas

Las cartas usan el mismo encabezado institucional y un pie con el lema y la línea del
campus (`config/documents.php`: `ubicacion_campus`, `nombre_campus`). Las firma quien llena
el formulario, con su firma registrada (sin ella queda el espacio para firmar a mano).

- **Solicitud de práctica** (`solicitud-practica-pps.docx`): se genera en el paso 2 del
  formulario; al enviar solo se crea si falta.
- **Autorización de PPS** (`autorizacion-pps.docx`): se genera automáticamente en cada envío
  a firmar (una versión nueva en cada reenvío tras una subsanación).

## Visualización

- `pps-servicio-social.show`: encabezado con el progreso del flujo, pestañas «Ficha» y
  «Adjunto N» (solicitud, carta de formalización, convenio marco, autorización) en el
  visor de PDF, e historial de movimientos.
- `pps-servicio-social.pdf?ver=1`: ficha para el visor; sin `ver`, se descarga.
- `pps-servicio-social.documento-generado?ver=1`: carta generada para el visor.
- `pps-servicio-social.anexo`: anexo subido; los de Word se muestran convertidos a PDF.
  `download=1` descarga el original.

Todos comprueban `PpsServicioSocial::puedeConsultarse()`: el creador, el revisor de la
etapa y los roles de historial y revisión final.

## Servidor

Desplegar las plantillas `form-dvus-014.docx`, `solicitud-practica-pps.docx` y
`autorizacion-pps.docx` y tener LibreOffice instalado (ver `docs/form-dvus-018-pdf.md`).

```bash
php artisan test tests/Feature/FormDvus014DocumentTest.php tests/Feature/PpsServicioSocialWorkflowTest.php
```
