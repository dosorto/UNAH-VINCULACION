# Generación PDF de FORM-DVUS-018

FORM-DVUS-018 se genera exclusivamente desde la plantilla maestra
`storage/app/templates/form-dvus-018.docx`. El sistema modifica únicamente el
contenido de las celdas destinadas a datos y conserva los recursos, estilos,
anchos, altos, encabezados, pies, marca de agua y saltos del DOCX.

`FormDvus018DocumentService` crea un DOCX en un directorio temporal único,
convierte con LibreOffice headless, valida que el resultado sea un PDF Carta de
11 páginas y elimina el DOCX y el perfil temporal. El PDF final se cachea por la
huella SHA-256 de la plantilla y de los datos. Por eso las rutas `Ver` y
`Descargar` usan exactamente los mismos bytes; sólo cambia
`Content-Disposition`.

## Dependencias

La imagen de aplicación las instala desde `Dockerfile`:

```bash
docker compose build app
docker compose up -d
```

En una instalación Debian/Ubuntu sin Docker:

```bash
sudo apt-get update
sudo apt-get install libreoffice-writer poppler-utils fonts-liberation \
  fonts-dejavu-core fonts-crosextra-carlito fonts-crosextra-caladea
```

Configure las rutas si difieren de las predeterminadas:

```dotenv
LIBREOFFICE_BINARY=/usr/bin/libreoffice
PDFINFO_BINARY=/usr/bin/pdfinfo
```

## Verificación

```bash
php artisan test tests/Feature/FormDvus018PdfGenerationTest.php \
  tests/Feature/Form018WordParityTest.php
php scripts/verify-form-dvus-018.php /tmp/FORM-DVUS-018-prueba.docx
php scripts/smoke-form-dvus-018-service.php
```

La última orden imprime la ruta del PDF y el SHA-256 que también se expone en
el encabezado `X-Content-SHA256` de ambas respuestas HTTP.
