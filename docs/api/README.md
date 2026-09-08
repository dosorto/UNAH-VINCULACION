# API externa de NEXO

El contrato público disponible en este repositorio está limitado a la consulta de proyectos por empleado:

- [GET /api/empleados/{identificador}/proyectos](empleados-proyectos.md)
- [Colección Postman](postman/NEXO-API.postman_collection.json)
- [Entorno Postman sin secretos](postman/NEXO-API.postman_environment.example.json)

La suite corregida validó 37 pruebas y 117 aserciones. La verificación de datos realizada para el empleado `12280` encontró 16 participaciones activas y 5 proyectos publicables según las reglas del endpoint. Ese conteo es una observación de la base en el momento de la prueba y no una garantía permanente.

No se documentan aquí rutas web, Livewire, formularios, PDFs, integraciones salientes ni módulos internos.
