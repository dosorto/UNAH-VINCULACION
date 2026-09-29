# Visor del FORM-DVUS-013

La ficha de Pasantías se genera desde `storage/app/templates/form-dvus-013.docx`,
conservando el formato institucional del Word. `FormDvus013DataMapper` llena
las celdas y agrega filas para las asignaturas adicionales. Las firmas PNG/JPEG
registradas se incrustan como imágenes dentro del DOCX.

`FormDvus013DocumentService` convierte la copia temporal mediante LibreOffice,
sin exigir una cantidad fija de páginas. La caché depende de la plantilla, los
datos y el contenido de las imágenes de firma. La conversión usa un perfil
independiente, bloqueo por registro y limpieza de archivos temporales.

## Servidor

Desplegar también la plantilla Word. Se reutiliza `LIBREOFFICE_BINARY` de
`config/documents.php` (por defecto `/usr/bin/libreoffice`). Instalar las fuentes
institucionales en el servidor para mantener la paginación y apariencia.
Después de actualizar configuración y vistas:

```bash
php artisan config:cache
php artisan view:cache
```

## Visualización

- `pasantias.show`: pestañas Ficha y adjuntos, flujo e historial.
- `pasantias.pdf?inline=1`: PDF actual para el visor.
- `pasantias.pdf`: descarga del mismo PDF actual.
- `pasantias.anexo`: archivo registrado en el modelo; Word se convierte a PDF,
  imágenes y PDFs se sirven directamente. `download=1` descarga el original.

Los endpoints comprueban acceso al registro y los adjuntos no aceptan rutas
arbitrarias del cliente. Los errores de conversión se registran en el log y
muestran una pantalla de reintento en el visor.

```bash
php artisan test tests/Feature/FormDvus013DocumentTest.php tests/Feature/FormDvus018PdfGenerationTest.php
```
