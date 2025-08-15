<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Seeder;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\MetaContribuye;

class MetasContribuyeSeeder extends Seeder
{
    public function run()
    {
        // Obtener ODS 1: Fin de la pobreza
        $ods1 = Od::where('nombre', '1. Fin de la pobreza')->first();
        if ($ods1) {
            MetaContribuye::create([
                'ods_id' => $ods1->id,
                'numero_meta' => '1.1',
                'descripcion' => 'Para 2030, erradicar la pobreza extrema para todas las personas en el mundo, actualmente medida por un ingreso por persona inferior a 1,25 dólares al día.'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods1->id,
                'numero_meta' => '1.2',
                'descripcion' => 'Para 2030, reducir al menos a la mitad la proporción de hombres, mujeres y niños y niñas de todas las edades que viven en la pobreza en todas sus dimensiones con arreglo a las definiciones nacionales.'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods1->id,
                'numero_meta' => '1.3',
                'descripcion' => 'Poner en práctica a nivel nacional sistemas y medidas apropiadas de protección social para todos y, para 2030, lograr una amplia cobertura de los pobres y los más vulnerables.'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods1->id,
                'numero_meta' => '1.4',
                'descripcion' => 'Para 2030, garantizar que todos los hombres y mujeres, en particular los pobres y los más vulnerables, tengan los mismos derechos a los recursos económicos, así como acceso a los servicios básicos, la propiedad y el control de las tierras y otros bienes, la herencia, los recursos naturales, las nuevas tecnologías y los servicios económicos, incluida la microfinanciación.'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods1->id,
                'numero_meta' => '1.5',
                'descripcion' => 'Para 2030, fomentar la resiliencia de los pobres y las personas que se encuentran en situaciones vulnerables y reducir su exposición y vulnerabilidad a los fenómenos extremos relacionados con el clima y a otros desastres económicos, sociales y ambientales.'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods1->id,
                'numero_meta' => '1.a',
                'descripcion' => 'Garantizar una movilización importante de recursos procedentes de diversas fuentes, incluso mediante la mejora de la cooperación para el desarrollo, a fin de proporcionar medios suficientes y previsibles para los países en desarrollo, en particular los países menos adelantados, para poner en práctica programas y políticas encaminados a poner fin a la pobreza en todas sus dimensiones.'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods1->id,
                'numero_meta' => '1.b',
                'descripcion' => 'Crear marcos normativos sólidos en el ámbito nacional, regional e internacional, sobre la base de estrategias de desarrollo en favor de los pobres que tengan en cuenta las cuestiones de género, a fin de apoyar la inversión acelerada en medidas para erradicar la pobreza.'
            ]);
        }


     // Obtener ODS 2: Hambre cero
        $ods2 = Od::where('nombre', '2. Hambre cero')->first();
        if ($ods2) {
            MetaContribuye::create([
                'ods_id' => $ods2->id,
                'numero_meta' => '2.1',
                'descripcion' => 'Para 2030, poner fin al hambre y asegurar el acceso de todas las personas, en particular los pobres y las personas en situaciones vulnerables, incluidos los lactantes, a una alimentación sana, nutritiva y suficiente durante todo el año'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods2->id,
                'numero_meta' => '2.2',
                'descripcion' => 'Para 2030, poner fin a todas las formas de malnutrición, incluso logrando, a más tardar en 2025, las metas convenidas internacionalmente sobre el retraso del crecimiento y la emaciación de los niños menores de 5 años, y abordar las necesidades de nutrición de las adolescentes, las mujeres embarazadas y lactantes y las personas de edad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods2->id,
                'numero_meta' => '2.3',
                'descripcion' => 'Para 2030, duplicar la productividad agrícola y los ingresos de los productores de alimentos en pequeña escala, en particular las mujeres, los pueblos indígenas, los agricultores familiares, los pastores y los pescadores, entre otras cosas mediante un acceso seguro y equitativo a las tierras, a otros recursos de producción e insumos, conocimientos, servicios financieros, mercados y oportunidades para la generación de valor añadido y empleos no agrícolas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods2->id,
                'numero_meta' => '2.4',
                'descripcion' => 'Para 2020, mantener la diversidad genética de las semillas, las plantas cultivadas y los animales de granja y domesticados y sus especies silvestres conexas, entre otras cosas mediante una buena gestión y diversificación de los bancos de semillas y plantas a nivel nacional, regional e internacional, y promover el acceso a los beneficios que se deriven de la utilización de los recursos genéticos y los conocimientos tradicionales y su distribución justa y equitativa, como se ha convenido internacionalmente'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods2->id,
                'numero_meta' => '2.a',
                'descripcion' => 'Aumentar las inversiones, incluso mediante una mayor cooperación internacional, en la infraestructura rural, la investigación agrícola y los servicios de extensión, el desarrollo tecnológico y los bancos de genes de plantas y ganado a fin de mejorar la capacidad de producción agrícola en los países en desarrollo, en particular en los países menos adelantados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods2->id,
                'numero_meta' => '2.b',
                'descripcion' => 'Corregir y prevenir las restricciones y distorsiones comerciales en los mercados agropecuarios mundiales, entre otras cosas mediante la eliminación paralela de todas las formas de subvenciones a las exportaciones agrícolas y todas las medidas de exportación con efectos equivalentes'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods2->id,
                'numero_meta' => '2.c',
                'descripcion' => 'Adoptar medidas para asegurar el buen funcionamiento de los mercados de productos básicos alimentarios y sus derivados y facilitar el acceso oportuno a información sobre los mercados, en particular mediante la mejora de la transparencia y la previsibilidad de las políticas comerciales'
            ]);
        }   


       // Obtener ODS 3: Salud y bienestar
        $ods3 = Od::where('nombre', '3. Salud y bienestar')->first();
        if ($ods3) {
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.1',
                'descripcion' => 'Para 2030, reducir la tasa mundial de mortalidad materna a menos de 70 por cada 100.000 nacidos vivos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.2',
                'descripcion' => 'Para 2030, poner fin a las muertes evitables de recién nacidos y de niños menores de 5 años, logrando que todos los países intenten reducir la mortalidad neonatal al menos hasta 12 por cada 1.000 nacidos vivos, y la mortalidad de niños menores de 5 años al menos hasta 25 por cada 1.000 nacidos vivos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.3',
                'descripcion' => 'Para 2030, poner fin a las epidemias del SIDA, la tuberculosis, la malaria y las enfermedades tropicales desatendidas y combatir la hepatitis, las enfermedades transmitidas por el agua y otras enfermedades transmisibles'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.4',
                'descripcion' => 'Para 2030, reducir en un tercio la mortalidad prematura por enfermedades no transmisibles mediante la prevención y el tratamiento y promover la salud mental y el bienestar'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.5',         
                'descripcion' => 'Fortalecer la prevención y el tratamiento del abuso de sustancias adictivas, incluido el uso indebido de estupefacientes y el consumo nocivo de alcohol'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.6',
                'descripcion' => 'Para 2020, reducir a la mitad el número de muertes y lesiones causadas por accidentes de tráfico en el mundo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.7',
                'descripcion' => 'Para 2030, garantizar el acceso universal a los servicios de salud sexual y reproductiva, incluidos los de planificación de la familia, información y educación, y la integración de la salud reproductiva en las estrategias y los programas nacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.8',
                'descripcion' => 'Para 2030, lograr el acceso universal a la atención de salud y a servicios de salud sexual y reproductiva'
            ]); 
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.9',
                'descripcion' => 'Para 2030, reducir sustancialmente el número de muertes y enfermedades producidas por productos químicos peligrosos y la contaminación del aire, el agua y el suelo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.a',
                'descripcion' => 'Fortalecer la aplicación del Convenio Marco de la Organización Mundial de la Salud para el Control del Tabaco en todos los países, según proceda'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.b',
                'descripcion' => 'Apoyar las actividades de investigación y desarrollo de vacunas y medicamentos para las enfermedades transmisibles y no transmisibles que afectan primordialmente a los países en desarrollo y facilitar el acceso a medicamentos y vacunas esenciales asequibles de conformidad con la Declaración de Doha relativa al Acuerdo sobre los ADPIC y la Salud Pública, en la que se afirma el derecho de los países en desarrollo a utilizar al máximo las disposiciones del Acuerdo sobre los Aspectos de los Derechos de Propiedad Intelectual Relacionados con el Comercio en lo relativo a la flexibilidad para proteger la salud pública y, en particular, proporcionar acceso a los medicamentos para todos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.c',
                'descripcion' => 'Aumentar sustancialmente la financiación de la salud y la contratación, el desarrollo, la capacitación y la retención del personal sanitario en los países en desarrollo, especialmente en los países menos adelantados y los pequeños Estados insulares en desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods3->id,
                'numero_meta' => '3.d',
                'descripcion' => 'Reforzar la capacidad de todos los países, en particular los países en desarrollo, en materia de alerta temprana, reducción de riesgos y gestión de los riesgos para la salud nacional y mundial'
            ]);
        }

        // Obtener ODS 4: Educación de calidad
        $ods4 = Od::where('nombre', '4. Educación de calidad')->first();
        if ($ods4) {
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.1',
                'descripcion' => 'De aquí a 2030, asegurar que todas las niñas y todos los niños terminen la enseñanza primaria y secundaria, que ha de ser gratuita, equitativa y de calidad y producir resultados de aprendizaje pertinentes y efectivos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.2',
                'descripcion' => 'Asegurar que todas las niñas y todos los niños tengan acceso a servicios de atención y desarrollo en la primera infancia y educación preescolar de calidad, a fin de que estén preparados para la enseñanza primaria'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.3',
                'descripcion' => 'De aquí a 2030, asegurar el acceso igualitario de todos los hombres y las mujeres a una formación técnica, profesional y superior de calidad, incluida la enseñanza universitaria'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.4',
                'descripcion' => 'De aquí a 2030, aumentar considerablemente el número de jóvenes y adultos que tienen las competencias necesarias, en particular técnicas y profesionales, para acceder al empleo, el trabajo decente y el emprendimiento'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.5',
                'descripcion' => 'De aquí a 2030, eliminar las disparidades de género en la educación y asegurar el acceso igualitario a todos los niveles de la enseñanza y la formación profesional
                    para las personas vulnerables, incluidas las personas con discapacidad, los pueblos indígenas y los niños en situaciones de vulnerabilidad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.6',
                'descripcion' => 'De aquí a 2030, asegurar que todos los jóvenes y una proporción considerable de los adultos, tanto hombres como mujeres, estén alfabetizados y tengan nociones
                    elementales de aritmética'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.7',
                'descripcion' => 'De aquí a 2030, asegurar que todos los alumnos adquieran los conocimientos teóricos y prácticos necesarios para promover el desarrollo sostenible, entre otras cosas mediante la educación para el desarrollo sostenible y los estilos de vida sostenibles, los derechos humanos, la igualdad de género, la promoción de una cultura de paz y no violencia, la ciudadanía mundial y la valoración de la diversidad cultural y la contribución de la cultura al desarrollo sostenible'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.a',
                'descripcion' => 'Construir y adecuar instalaciones educativas que tengan en cuenta las necesidades de los niños y las personas con discapacidad y las diferencias de género, y que ofrezcan entornos de aprendizaje seguros, no violentos, inclusivos y eficaces para todos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.b',
                'descripcion' => 'De aquí a 2020, aumentar considerablemente a nivel mundial el número de becas disponibles para los países en desarrollo, en particular los países menos adelantados, los pequeños Estados insulares en desarrollo y los países africanos, a fin de que sus estudiantes puedan matricularse en programas de enseñanza superior, incluidos programas de formación profesional y programas técnicos, científicos, de ingeniería y de tecnología de la información y las comunicaciones, de países desarrollados y otros países en desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods4->id,
                'numero_meta' => '4.c',
                'descripcion' => 'De aquí a 2030, aumentar considerablemente la oferta de docentes calificados, incluso mediante la cooperación internacional para la formación de docentes en los países en desarrollo, especialmente los países menos adelantados y los pequeños Estados insulares en desarrollo'
            ]);
        }

        // Obtener ODS 5: Igualdad de género
        $ods5 = Od::where('nombre', '5. Igualdad de género')->first();
        if ($ods5) {
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.1',
                'descripcion' => 'Poner fin a todas las formas de discriminación contra todas las mujeres y las niñas en todo el mundo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.2',
                'descripcion' => 'Eliminar todas las formas de violencia contra todas las mujeres y las niñas en los ámbitos público y privado, incluidas la trata y la explotación sexual y otros tipos de explotación'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.3',
                'descripcion' => 'Eliminar todas las prácticas nocivas, como el matrimonio infantil, precoz y forzado y la mutilación genital femenina'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.4',
                'descripcion' => 'Reconocer y valorar los cuidados y el trabajo doméstico no remunerados mediante servicios públicos, infraestructuras y políticas de protección social, y promoviendo la responsabilidad compartida en el hogar y la familia, según proceda en cada país'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.5',
                'descripcion' => 'Asegurar la participación plena y efectiva de las mujeres y la igualdad de oportunidades de liderazgo a todos los niveles decisorios en la vida política, económica y pública'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.6',
                'descripcion' => 'Asegurar el acceso universal a la salud sexual y reproductiva y los derechos reproductivos según lo acordado de conformidad con el Programa de Acción de la Conferencia Internacional sobre la Población y el Desarrollo, la Plataforma de Acción de Beijing y los documentos finales de sus conferencias de examen'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.a',
                'descripcion' => 'Emprender reformas que otorguen a las mujeres igualdad de derechos a los recursos económicos, así como acceso a la propiedad y al control de la tierra y otros tipos de bienes, los servicios financieros, la herencia y los recursos naturales, de conformidad con las leyes nacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.b',
                'descripcion' => 'Mejorar el uso de la tecnología instrumental, en particular la tecnología de la información y las comunicaciones, para promover el empoderamiento de las mujeres'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods5->id,
                'numero_meta' => '5.c',
                'descripcion' => 'Aprobar y fortalecer políticas acertadas y leyes aplicables para promover la igualdad de género y el empoderamiento de todas las mujeres y las niñas a todos los niveles'
            ]);
        }

        // Obtener ODS 6: Agua limpia y saneamiento
        $ods6 = Od::where('nombre', '6. Agua limpia y saneamiento')->first();
        if ($ods6) {
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.1',
                'descripcion' => 'De aquí a 2030, lograr el acceso universal y equitativo al agua potable a un precio asequible para todos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.2',
                'descripcion' => 'De aquí a 2030, lograr el acceso a servicios de saneamiento e higiene adecuados y equitativos para todos y poner fin a la defecación al aire libre, prestando especial atención a las necesidades de las mujeres y las niñas y las personas en situaciones de vulnerabilidad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.3',
                'descripcion' => 'De aquí a 2030, mejorar la calidad del agua reduciendo la contaminación, eliminando el vertimiento y minimizando la emisión de productos químicos y materiales peligrosos, reduciendo a la mitad el porcentaje de aguas residuales sin tratar y aumentando considerablemente el reciclado y la reutilización sin riesgos a nivel mundial'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.4',
                'descripcion' => 'De aquí a 2030, aumentar considerablemente el uso eficiente de los recursos hídricos en todos los sectores y asegurar la sostenibilidad de la extracción y el abastecimiento de agua dulce para hacer frente a la escasez de agua y reducir considerablemente el número de personas que sufren falta de agua'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.5',
                'descripcion' => 'De aquí a 2030, implementar la gestión integrada de los recursos hídricos a todos los niveles, incluso mediante la cooperación transfronteriza, según proceda'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.6',
                'descripcion' => 'De aquí a 2020, proteger y restablecer los ecosistemas relacionados con el agua, incluidos los bosques, las montañas, los humedales, los ríos, los acuíferos y los lagos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.a',
                'descripcion' => 'De aquí a 2030, ampliar la cooperación internacional y el apoyo prestado a los países en desarrollo para la creación de capacidad en actividades y programas relativos al agua y el saneamiento, como los de captación de agua, desalinización, uso eficiente de los recursos hídricos, tratamiento de aguas residuales, reciclado y tecnologías de reutilización'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods6->id,
                'numero_meta' => '6.b',
                'descripcion' => 'Apoyar y fortalecer la participación de las comunidades locales en la mejora de la gestión del agua y el saneamiento'
            ]);
        }

         // Obtener ODS 7: Energía asequible y no contaminante
        $ods7 = Od::where('nombre', '7. Energía asequible y no contaminante')->first();
        if ($ods7) {
            MetaContribuye::create([
                'ods_id' => $ods7->id,
                'numero_meta' => '7.1',
                'descripcion' => 'De aquí a 2030, garantizar el acceso universal a servicios energéticos asequibles, fiables y modernos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods7->id,
                'numero_meta' => '7.2',
                'descripcion' => 'De aquí a 2030, aumentar considerablemente la proporción de energía renovable en el conjunto de fuentes energéticas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods7->id,
                'numero_meta' => '7.3',     
                'descripcion' => 'De aquí a 2030, duplicar la tasa mundial de mejora de la eficiencia energética'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods7->id,
                'numero_meta' => '7.a',
                'descripcion' => 'De aquí a 2030, aumentar la cooperación internacional para facilitar el acceso a la investigación y la tecnología relativas a la energía limpia, incluidas las fuentes renovables, la eficiencia energética y las tecnologías avanzadas y menos contaminantes de combustibles fósiles, y promover la inversión en infraestructura energética y tecnologías limpias'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods7->id,
                'numero_meta' => '7.b',
                'descripcion' => 'De aquí a 2030, ampliar la infraestructura y mejorar la tecnología para prestar servicios energéticos modernos y sostenibles para todos en los países en desarrollo, en particular los países menos adelantados, los pequeños Estados insulares en desarrollo y los países en desarrollo sin litoral, en consonancia con sus respectivos programas de apoyo'
            ]);
        }

       // Obtener ODS 8: Trabajo decente y crecimiento económico
        $ods8 = Od::where('nombre', '8. Trabajo decente y crecimiento económico')->first();
        if ($ods8) {
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.1',
                'descripcion' => 'Mantener el crecimiento económico per cápita de conformidad con las circunstancias nacionales y, en particular, un crecimiento del producto interno bruto de al menos el 7% anual en los países menos adelantados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.2',
                'descripcion' => 'Lograr niveles más elevados de productividad económica mediante la diversificación,   
                    la modernización tecnológica y la innovación, entre otras cosas centrándose en los sectores con gran valor añadido y un uso intensivo de la mano de obra'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.3',
                'descripcion' => 'Promover políticas orientadas al desarrollo que apoyen actividades productivas, la creación de empleos decentes, el emprendimiento y la innovación, y fomentar la formalización y el crecimiento de las micro, pequeñas y medianas empresas, incluso a través del acceso a servicios financieros'
            ]);     
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.4',
                'descripcion' => 'Mejorar progresivamente, de aquí a 2030, la producción y el consumo eficientes de los recursos mundiales y procurar desvincular el crecimiento económico de la degradación del medio ambiente, conforme al Marco Decenal de Programas sobre modalidades de Consumo y Producción Sostenibles, empezando por los países desarrollados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.5',
                'descripcion' => 'De aquí a 2030, lograr el empleo pleno y productivo y el trabajo decente para todas las mujeres y los hombres, incluidos los jóvenes y las personas con discapacidad, así como la igualdad de remuneración por trabajo de igual valor'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.6',
                'descripcion' => 'De aquí a 2020, reducir considerablemente la proporción de jóvenes que no están empleados y no cursan estudios ni reciben capacitación'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.7',
                'descripcion' => 'Proteger los derechos laborales y promover entornos de trabajo seguros y protegidos para todos los trabajadores, incluidos los migrantes, en particular las mujeres, y los jóvenes'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.8',
                'descripcion' => 'Proteger los derechos laborales y promover un entorno de trabajo seguro y sin riesgos para todos los trabajadores, incluidos los trabajadores migrantes, en particular las mujeres migrantes y las personas con empleos precarios'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.9',
                'descripcion' => 'De aquí a 2030, elaborar y poner en práctica políticas encaminadas a promover un turismo sostenible que cree puestos de trabajo y promueva la cultura y los productos locales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.10',
                'descripcion' => 'Fortalecer la capacidad de las instituciones financieras nacionales para fomentar y ampliar el acceso a los servicios bancarios, financieros y de seguros para todos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.a',
                'descripcion' => 'Aumentar el apoyo a la iniciativa de ayuda para el comercio en los países en desarrollo, en particular los países menos adelantados, incluso mediante el Marco Integrado Mejorado para la Asistencia Técnica a los Países Menos Adelantados en Materia de Comercio'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods8->id,
                'numero_meta' => '8.b',
                'descripcion' => 'Incluir la dimensión del trabajo decente en las políticas y programas de desarrollo sostenible, en particular en los planes de desarrollo nacional y local'
            ]);
        }

        // Obtener ODS 9: Industria, innovación e infraestructura   
        $ods9 = Od::where('nombre', '9. Industria, innovación e infraestructura')->first();
        if ($ods9) {
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.1',
                'descripcion' => 'Desarrollar infraestructuras fiables, sostenibles, resilientes y de calidad, incluidas infraestructuras regionales y transfronterizas, para apoyar el desarrollo económico y el bienestar humano, haciendo especial hincapié en el acceso asequible y equitativo para todos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.2',
                'descripcion' => 'Promover una industrialización inclusiva y sostenible y, de aquí a 2030, aumentar significativamente la contribución de la industria al empleo y al producto interno bruto, de acuerdo con las circunstancias nacionales, y duplicar esa contribución en los países menos adelantados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.3',
                'descripcion' => 'Aumentar el acceso de las pequeñas industrias y las empresas en crecimiento a los servicios financieros, incluidas la financiación asequible y el acceso a los mercados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.4',
                'descripcion' => 'Actualizar la infraestructura y transformar las industrias para que sean sostenibles, utilizando recursos eficientes y tecnologías limpias y ecológicas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.5',
                'descripcion' => 'Aumentar la investigación y la capacidad tecnológica en los sectores industriales de todos los países, en particular los países en desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.a',
                'descripcion' => 'Facilitar el desarrollo de infraestructuras sostenibles y resilientes en los países en desarrollo mediante un mayor apoyo financiero, tecnológico y técnico a los países africanos, los países menos adelantados, los países en desarrollo sin litoral y los pequeños Estados insulares en desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.b',
                'descripcion' => 'Apoyar el desarrollo de tecnologías, la investigación y la innovación nacionales en los países en desarrollo, incluso garantizando un entorno normativo propicio a la diversificación industrial y la adición de valor a los productos básicos, entre otras cosas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods9->id,
                'numero_meta' => '9.c',
                'descripcion' => 'Aumentar el acceso a las tecnologías de la información y las comunicaciones y esforzarse por proporcionar acceso universal y asequible a Internet en los países menos adelantados de aquí a 2020'
            ]);
        }

      $ods10 = Od::where('nombre', '10. Reducción de las desigualdades')->first();
        if ($ods10) {
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.1',
                'descripcion' => 'De aquí a 2030, lograr progresivamente y mantener el crecimiento de los ingresos del 40% más pobre de la población a una tasa superior a la media nacional'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.2',
                'descripcion' => 'De aquí a 2030, potenciar y promover la inclusión social, económica y política de todas las personas, independientemente de su edad, sexo, discapacidad, raza, etnia, origen, religión o situación económica u otra condición'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.3',
                'descripcion' => 'Garantizar la igualdad de oportunidades y reducir la desigualdad de resultados, incluso eliminando las leyes, políticas y prácticas discriminatorias y promoviendo legislaciones, políticas y medidas adecuadas a ese respecto'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.4',
                'descripcion' => 'Adoptar políticas, especialmente fiscales, salariales y de protección social, y lograr progresivamente una mayor igualdad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.5',
                'descripcion' => 'Mejorar la reglamentación y vigilancia de las instituciones y los mercados financieros mundiales y fortalecer la aplicación de esos reglamentos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.6',
                'descripcion' => 'Asegurar una mayor representación e intervención de los países en desarrollo en las decisiones adoptadas por las instituciones económicas y financieras internacionales para aumentar la eficacia, fiabilidad, rendición de cuentas y legitimidad de esas instituciones'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.7',
                'descripcion' => 'Facilitar la migración y la movilidad ordenadas, seguras, regulares y responsables de las personas, incluso mediante la aplicación de políticas migratorias planificadas y bien gestionadas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.a',
                'descripcion' => 'Aplicar el principio del trato especial y diferenciado para los países en desarrollo, en particular los países menos adelantados, de conformidad con los acuerdos de la Organización Mundial del Comercio'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.b',
                'descripcion' => 'Fomentar la asistencia oficial para el desarrollo y las corrientes financieras, incluida la inversión extranjera directa, para los Estados con mayores necesidades, en particular los países menos adelantados, los países africanos, los pequeños Estados insulares en desarrollo y los países en desarrollo sin litoral, en consonancia con sus planes y programas nacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods10->id,
                'numero_meta' => '10.c',
                'descripcion' => 'De aquí a 2030, reducir a menos del 3% los costos de transacción de las remesas de los migrantes y eliminar los corredores de remesas con un costo superior al 5%'
            ]);
        }

     // Obtener ODS 11: Ciudades y comunidades sostenibles
        $ods11 = Od::where('nombre', '11. Ciudades y comunidades sostenibles')->first();
        if ($ods11) {
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.1',
                'descripcion' => 'De aquí a 2030, asegurar el acceso de todas las personas a viviendas y servicios básicos adecuados, seguros y asequibles y mejorar los barrios marginales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.2',
                'descripcion' => 'De aquí a 2030, proporcionar acceso a sistemas de transporte seguros, asequibles, accesibles y sostenibles para todos y mejorar la seguridad vial, en particular mediante la ampliación del transporte público, prestando especial atención a las necesidades de las personas en situación de vulnerabilidad, las mujeres, los niños, las personas con discapacidad y las personas de edad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.3',
                'descripcion' => 'De aquí a 2030, aumentar la urbanización inclusiva y sostenible y la capacidad para la planificación y la gestión participativas, integradas y sostenibles de las ciudades y los asentamientos humanos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.4',
                'descripcion' => 'Redoblar los esfuerzos para proteger y salvaguardar el patrimonio cultural y natural del mundo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.5',
                'descripcion' => 'De aquí a 2030, reducir significativamente el número de muertes causadas por los desastres, incluidos los relacionados con el agua, y de personas afectadas por ellos, y reducir considerablemente las pérdidas económicas directas provocadas por los desastres
                    en comparación con el producto interno bruto mundial, haciendo especial hincapié en la protección de los pobres y las personas en situaciones de vulnerabilidad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.6',            
                'descripcion' => 'De aquí a 2030, reducir el impacto ambiental negativo per cápita de las ciudades, incluso prestando especial atención a la calidad del aire y la gestión de los desechos municipales y de otro tipo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.7',
                'descripcion' => 'De aquí a 2030, proporcionar acceso universal a zonas verdes y espacios públicos seguros, inclusivos y accesibles, en particular para las mujeres y los niños, las personas de edad y las personas con discapacidad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.a',
                'descripcion' => 'Apoyar los vínculos económicos, sociales y ambientales positivos entre las zonas urbanas, periurbanas y rurales fortaleciendo la planificación del desarrollo nacional y regional'    
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.b',
                'descripcion' => 'De aquí a 2020, aumentar considerablemente el número de ciudades y asentamientos humanos que adoptan e implementan políticas y planes integrados para promover la inclusión, el uso eficiente de los recursos, la mitigación del cambio climático y la adaptación a él y la resiliencia ante los desastres, y desarrollar y poner en práctica, en consonancia con el Marco de Sendai para la Reducción del Riesgo de Desastres 2015-2030, la gestión integral de los riesgos de desastre a todos los niveles'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods11->id,
                'numero_meta' => '11.c',
                'descripcion' => 'Proporcionar apoyo a los países menos adelantados, incluso mediante asistencia financiera y técnica, para que puedan construir edificios sostenibles y resilientes utilizando materiales locales'
            ]);
        }

      // Obtener ODS 12: Producción y consumo responsables
        $ods12 = Od::where('nombre', '12. Producción y consumo responsables')->first();
        if ($ods12) {
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.1',
                'descripcion' => 'Aplicar el Marco Decenal de Programas sobre Modalidades de Consumo y Producción Sostenibles, con la participación de todos los países y bajo el liderazgo de los países desarrollados, teniendo en cuenta el grado de desarrollo y las capacidades de los países en desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.2',
                'descripcion' => 'De aquí a 2030, lograr la gestión sostenible y el uso eficiente de los recursos naturales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.3',
                'descripcion' => 'De aquí a 2030, reducir a la mitad el desperdicio de alimentos per cápita mundial en la venta al por menor y a nivel de los consumidores y reducir las pérdidas de alimentos en las cadenas de producción y suministro, incluidas las pérdidas posteriores a la cosecha'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.4',
                'descripcion' => 'De aquí a 2020, lograr la gestión ecológicamente racional de los productos químicos y de todos los desechos a lo largo de su ciclo de vida, de conformidad con los marcos internacionales convenidos, y reducir significativamente su liberación a la atmósfera, el agua y el suelo a fin de minimizar sus efectos adversos en la salud humana y el medio ambiente'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.5',
                'descripcion' => 'De aquí a 2030, reducir considerablemente la generación de desechos mediante actividades de prevención, reducción, reciclado y reutilización'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.6',
                'descripcion' => 'Alentar a las empresas, en especial las grandes empresas y las multinacionales, a adoptar prácticas sostenibles y a integrar la sostenibilidad en sus estrategias comerciales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.7',
                'descripcion' => 'Promover prácticas de adquisición pública que sean sostenibles, de conformidad con las políticas y prioridades nacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.8',
                'descripcion' => 'De aquí a 2030, asegurar que las personas de todo el mundo tengan la información y los conocimientos pertinentes para el desarrollo sostenible y los estilos de vida en armonía con la naturaleza'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.a',
                'descripcion' => 'Ayudar a los países en desarrollo a fortalecer su capacidad científica y tecnológica para que puedan avanzar en el uso sostenible de los recursos naturales'
            ]); 
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.b',
                'descripcion' => 'Elaborar y aplicar instrumentos para vigilar los efectos en el desarrollo sostenible, a fin de lograr un turismo sostenible que cree puestos de trabajo y promueva la cultura y los productos locales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods12->id,
                'numero_meta' => '12.c',
                'descripcion' => 'Racionalizar los subsidios ineficientes a los combustibles fósiles que fomentan el consumo antieconómico eliminando las distorsiones del mercado, de acuerdo con las circunstancias nacionales, incluso mediante la reestructuración de los sistemas tributarios y la eliminación gradual de los subsidios perjudiciales, cuando existan, para reflejar su impacto ambiental, teniendo plenamente en cuenta las necesidades y condiciones específicas de los países en desarrollo y minimizando los posibles efectos adversos en su desarrollo, de manera que se proteja a los pobres y a las comunidades afectadas'
            ]);
        }

       // Obtener ODS 13: Acción por el clima
        $ods13 = Od::where('nombre', '13. Acción por el clima')->first();
        if ($ods13) {
            MetaContribuye::create([
                'ods_id' => $ods13->id, 
                'numero_meta' => '13.1',
                'descripcion' => 'Fortalecer la resiliencia y la capacidad de adaptación a los riesgos relacionados con el clima y los desastres naturales en todos los países'
            ]);
            MetaContribuye::create([    
                'ods_id' => $ods13->id,
                'numero_meta' => '13.2',
                'descripcion' => 'Incorporar medidas relativas al cambio climático en las políticas, estrategias y planes nacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods13->id,
                'numero_meta' => '13.3',
                'descripcion' => 'Mejorar la educación, la sensibilización y la capacidad humana e institucional respecto de la mitigación del cambio climático, la adaptación a él, la reducción de sus efectos y la alerta temprana'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods13->id,
                'numero_meta' => '13.a',
                'descripcion' => 'Cumplir el compromiso de los países desarrollados que son partes en la Convención Marco de las Naciones Unidas sobre el Cambio Climático de lograr para el año 2020 el objetivo de movilizar conjuntamente 100.000 millones de dólares anuales procedentes de todas las fuentes a fin de atender las necesidades de los países en desarrollo respecto de la adopción de medidas concretas de mitigación y la transparencia de su aplicación, y poner en pleno funcionamiento el Fondo Verde para el Clima capitalizándolo lo antes posible'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods13->id,
                'numero_meta' => '13.b',
                'descripcion' => 'Promover mecanismos para aumentar la capacidad para la planificación y gestión eficaces en relación con el cambio climático en los países menos adelantados y los pequeños Estados insulares en desarrollo, haciendo particular hincapié en las mujeres, los jóvenes y las comunidades locales y marginadas'
            ]);
        }

      // Obtener ODS 14: Vida submarina
        $ods14 = Od::where('nombre', '14. Vida submarina')->first();
        if ($ods14) {
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.1',
                'descripcion' => 'De aquí a 2025, prevenir y reducir significativamente la contaminación marina de todo tipo, en particular la producida por actividades realizadas en tierra, incluidos los detritos marinos y la polución por nutrientes'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.2',
                'descripcion' => 'De aquí a 2020, gestionar y proteger sosteniblemente los ecosistemas marinos y costeros para evitar efectos adversos importantes, incluso fortaleciendo su resiliencia, y adoptar medidas para restaurarlos a fin de restablecer la salud y la productividad de los océanos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.3',
                'descripcion' => 'Minimizar y abordar los efectos de la acidificación de los océanos, incluso mediante una mayor cooperación científica a todos los niveles'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.4',
                'descripcion' => 'De aquí a 2020, reglamentar eficazmente la explotación pesquera y poner fin a la pesca excesiva, la pesca ilegal, no declarada y no reglamentada y las prácticas pesqueras destructivas, y aplicar planes de gestión con fundamento científico a fin de restablecer las poblaciones de peces en el plazo más breve posible, al menos alcanzando niveles que puedan producir el máximo rendimiento sostenible de acuerdo con sus características biológicas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.5',
                'descripcion' => 'De aquí a 2020, conservar al menos el 10% de las zonas costeras y marinas, de conformidad con las leyes nacionales y el derecho internacional y sobre la base de la mejor información científica disponible'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.6',
                'descripcion' => 'De aquí a 2020, prohibir ciertas formas de subvenciones a la pesca que contribuyen a la sobrecapacidad y la pesca excesiva, eliminar las subvenciones que contribuyen a la pesca ilegal, no declarada y no reglamentada y abstenerse de introducir nuevas subvenciones de esa índole, reconociendo que la negociación sobre las subvenciones a la pesca en el marco de la Organización Mundial del Comercio debe incluir un trato especial y diferenciado, apropiado y efectivo para los países en desarrollo y los países menos adelantados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.7',
                'descripcion' => 'De aquí a 2030, aumentar los beneficios económicos que los pequeños Estados insulares en desarrollo y los países menos adelantados obtienen del uso sostenible de los recursos marinos, en particular mediante la gestión sostenible de la pesca, la acuicultura y el turismo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.a',
                'descripcion' => 'Aumentar los conocimientos científicos, desarrollar la capacidad de investigación y transferir tecnología marina, teniendo en cuenta los Criterios y Directrices para la Transferencia de Tecnología Marina de la Comisión Oceanográfica Intergubernamental, a fin de mejorar la salud de los océanos y potenciar la contribución de la biodiversidad marina al desarrollo de los países en desarrollo, en particular los pequeños Estados insulares en desarrollo y los países menos adelantados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.b',
                'descripcion' => 'Facilitar el acceso de los pescadores artesanales a los recursos marinos y los mercados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods14->id,
                'numero_meta' => '14.c',
                'descripcion' => 'Mejorar la conservación y el uso sostenible de los océanos y sus recursos aplicando el derecho internacional reflejado en la Convención de las Naciones Unidas sobre el Derecho del Mar, que constituye el marco jurídico para la conservación y la utilización sostenible de los océanos y sus recursos, como se recuerda en el párrafo 158 del documento “El futuro que queremos"'
            ]);
        }

        // Obtener ODS 15: Vida de ecosistemas terrestres
        $ods15 = Od::where('nombre', '15. Vida de ecosistemas terrestres')->first();
        if ($ods15) {
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.1',
                'descripcion' => 'Para 2020, velar por la conservación, el restablecimiento y el uso sostenible de los ecosistemas terrestres y los ecosistemas interiores de agua dulce y los servicios que proporcionan, en particular los bosques, los humedales, las montañas y las zonas áridas, en consonancia con las obligaciones contraídas en virtud de acuerdos internacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.2',
                'descripcion' => 'Para 2020, promover la gestión sostenible de todos los tipos de bosques, poner fin a la deforestación, recuperar los bosques degradados e incrementar la forestación y la reforestación a nivel mundial'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.3',
                'descripcion' => 'Para 2030, luchar contra la desertificación, rehabilitar las tierras y los suelos degradados, incluidas las tierras afectadas por la desertificación, la sequía y las inundaciones, y procurar lograr un mundo con una degradación neutra del suelo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,     
                'numero_meta' => '15.4',
                'descripcion' => 'Para 2030, velar por la conservación de los ecosistemas montañosos, incluida su diversidad biológica, a fin de mejorar su capacidad de proporcionar beneficios esenciales para el desarrollo sostenible'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.5',
                'descripcion' => 'Adoptar medidas urgentes y significativas para reducir la degradación de los hábitats naturales, detener la pérdida de la diversidad biológica y, para 2020, proteger las especies amenazadas y evitar su extinción'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.6',
                'descripcion' => 'Promover la participación justa y equitativa en los beneficios que se deriven de la utilización de los recursos genéticos y promover el acceso adecuado a esos recursos, como se ha convenido internacionalmente'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.7',    
                'descripcion' => 'Adoptar medidas urgentes para poner fin a la caza furtiva y el tráfico de especies protegidas de flora y fauna y abordar la demanda y la oferta ilegales de productos silvestres'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.8',
                'descripcion' => 'Para 2020, adoptar medidas para prevenir la introducción de especies exóticas invasoras y reducir de forma significativa sus efectos en los ecosistemas terrestres y acuáticos y controlar o erradicar las especies prioritarias'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.9',
                'descripcion' => 'Para 2020, integrar los valores de los ecosistemas y la diversidad biológica en la planificación nacional y local, los procesos de desarrollo, las estrategias de reducción de la pobreza y la contabilidad'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.a',
                'descripcion' => 'Movilizar y aumentar de manera significativa los recursos financieros procedentes de todas las fuentes para conservar y utilizar de forma sostenible la diversidad biológica y los ecosistemas'       
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.b',
                'descripcion' => 'Movilizar un volumen apreciable de recursos procedentes de todas las fuentes y a todos los niveles para financiar la gestión forestal sostenible y proporcionar incentivos adecuados a los países en desarrollo para que promuevan dicha gestión, en particular con miras a la conservación y la reforestación'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods15->id,
                'numero_meta' => '15.c',
                'descripcion' => 'Aumentar el apoyo mundial a la lucha contra la caza furtiva y el tráfico de especies protegidas, en particular aumentando la capacidad de las comunidades locales para promover oportunidades de subsistencia sostenibles'
            ]);
        }

        // Obtener ODS 16: Paz, justicia e instituciones sólidas
        $ods16 = Od::where('nombre', '16. Paz, justicia e instituciones sólidas')->first();
        if ($ods16) {
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.1',
                'descripcion' => 'Reducir significativamente todas las formas de violencia y las correspondientes tasas de mortalidad en todo el mundo'
            ]);
            MetaContribuye::create([    
                'ods_id' => $ods16->id,
                'numero_meta' => '16.2',
                'descripcion' => 'Poner fin al maltrato, la explotación, la trata y todas las formas de violencia y tortura contra los niños'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.3',        
                'descripcion' => 'Promover el estado de derecho en los planos nacional e internacional y garantizar la igualdad de acceso a la justicia para todos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.4',        
                'descripcion' => 'De aquí a 2030, reducir significativamente las corrientes financieras y de armas ilícitas, fortalecer la recuperación y devolución de los activos robados y luchar contra todas las formas de delincuencia organizada'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.5',
                'descripcion' => 'Reducir considerablemente la corrupción y el soborno en todas sus formas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.6',
                'descripcion' => 'Crear a todos los niveles instituciones eficaces y transparentes que rindan cuentas'
            ]);
            MetaContribuye::create([    
                'ods_id' => $ods16->id,
                'numero_meta' => '16.7',
                'descripcion' => 'Garantizar la adopción en todos los niveles de decisiones inclusivas, participativas y representativas que respondan a las necesidades'
            ]);         
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.8',
                'descripcion' => 'Ampliar y fortalecer la participación de los países en desarrollo en las instituciones de gobernanza mundial'
            ]);
            MetaContribuye::create([    
                'ods_id' => $ods16->id,
                'numero_meta' => '16.9',
                'descripcion' => 'De aquí a 2030, proporcionar acceso a una identidad jurídica para todos, en particular mediante el registro de nacimientos'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.10',
                'descripcion' => 'Garantizar el acceso público a la información y proteger las libertades fundamentales, de conformidad con las leyes nacionales y los acuerdos internacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.a',
                'descripcion' => 'Fortalecer las instituciones nacionales pertinentes, incluso mediante la cooperación internacional, para crear a todos los niveles, particularmente en los países en desarrollo, la capacidad de prevenir la violencia y combatir el terrorismo y la delincuencia'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods16->id,
                'numero_meta' => '16.b',
                'descripcion' => 'Promover y aplicar leyes y políticas no discriminatorias en favor del desarrollo sostenible'
            ]);
        }

        // Obtener ODS 17: Alianzas para lograr los objetivos   
        $ods17 = Od::where('nombre', '17. Alianzas para lograr los objetivos')->first();
        if ($ods17) {
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.1',
                'descripcion' => 'Fortalecer la movilización de recursos internos, incluso mediante la prestación de apoyo internacional a los países en desarrollo, con el fin de mejorar la capacidad nacional para recaudar ingresos fiscales y de otra índole'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.2',    
                'descripcion' => 'Velar por que los países desarrollados cumplan plenamente sus compromisos en relación con la asistencia oficial para el desarrollo, incluido el compromiso de numerosos países desarrollados de alcanzar el objetivo de destinar el 0,7% del ingreso nacional bruto a la asistencia oficial para el desarrollo de los países en desarrollo y entre el 0,15% y el 0,20% del ingreso nacional bruto a la asistencia oficial para el desarrollo de los países menos adelantados; se alienta a los proveedores de asistencia oficial para el desarrollo a que consideren la posibilidad de fijar una meta para destinar al menos el 0,20% del ingreso nacional bruto a la asistencia oficial para el desarrollo de los países menos adelantados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id, 
                'numero_meta' => '17.3',
                'descripcion' => 'Movilizar recursos financieros adicionales de múltiples fuentes para los países en desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id, 
                'numero_meta' => '17.4',
                'descripcion' => 'Ayudar a los países en desarrollo a lograr la sostenibilidad de la deuda a largo plazo con políticas coordinadas orientadas a fomentar la financiación, el alivio y la reestructuración de la deuda, según proceda, y hacer frente a la deuda externa de los países pobres muy endeudados a fin de reducir el endeudamiento excesivo'
            ]); 
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.5',
                'descripcion' => 'Adoptar y aplicar sistemas de promoción de las inversiones en favor de los países menos adelantados'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.6',
                'descripcion' => 'Mejorar la cooperación regional e internacional Norte-Sur, Sur-Sur y triangular en materia de ciencia, tecnología e innovación y el acceso a estas, y aumentar el intercambio de conocimientos en condiciones mutuamente convenidas, incluso mejorando la coordinación entre los mecanismos existentes, en particular a nivel de las Naciones Unidas, y mediante un mecanismo mundial de facilitación de la tecnología'
            ]);
            MetaContribuye::create([    
                'ods_id' => $ods17->id,
                'numero_meta' => '17.7',
                'descripcion' => 'Promover el desarrollo de tecnologías ecológicamente racionales y su transferencia, divulgación y difusión a los países en desarrollo en condiciones favorables, incluso en condiciones concesionarias y preferenciales, según lo convenido de mutuo acuerdo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.8',
                'descripcion' => 'Poner en pleno funcionamiento, a más tardar en 2017, el banco de tecnología y el mecanismo de apoyo a la creación de capacidad en materia de ciencia, tecnología e innovación para los países menos adelantados y aumentar la utilización de tecnologías instrumentales, en particular la tecnología de la información y las comunicaciones'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.9',
                'descripcion' => 'Aumentar el apoyo internacional para realizar actividades de creación de capacidad
                eficaces y específicas en los países en desarrollo a fin de respaldar los planes nacionales de implementación de todos los Objetivos de Desarrollo Sostenible, incluso mediante la cooperación Norte-Sur, Sur-Sur y triangular'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.10',
                'descripcion' => 'Promover un sistema de comercio multilateral universal, basado en normas, abierto, no discriminatorio y equitativo en el marco de la Organización Mundial del Comercio, incluso mediante la conclusión de las negociaciones en el marco del Programa de Doha para el Desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.11',   
                'descripcion' => 'Aumentar significativamente las exportaciones de los países en desarrollo, en particular con miras a duplicar la participación de los países menos adelantados en las exportaciones mundiales de aquí a 2020'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.12',
                'descripcion' => 'Lograr la consecución oportuna del acceso a los mercados libre de derechos y contingentes de manera duradera para todos los países menos adelantados, conforme a las decisiones de la Organización Mundial del Comercio'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.13',
                'descripcion' => 'Aumentar la estabilidad macroeconómica mundial, incluso mediante la coordinación y coherencia de las políticas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.14',
                'descripcion' => 'Mejorar la coherencia de las políticas para el desarrollo sostenible'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.15',
                'descripcion' => 'Respetar el margen normativo y el liderazgo de cada país para establecer y aplicar políticas de erradicación de la pobreza y desarrollo sostenible'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.16',
                'descripcion' => 'Mejorar la Alianza Mundial para el Desarrollo Sostenible, complementada por alianzas entre múltiples interesados que movilicen e intercambien conocimientos, especialización, tecnología y recursos financieros, a fin de apoyar el logro de los Objetivos de Desarrollo Sostenible en todos los países, particularmente los países en desarrollo'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.17',
                'descripcion' => 'Fomentar y promover la constitución de alianzas eficaces en las esferas pública, público-privada y de la sociedad civil, aprovechando la experiencia y las estrategias de obtención de recursos de las alianzas'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.18',
                'descripcion' => 'De aquí a 2020, mejorar el apoyo a la creación de capacidad prestado a los países en desarrollo, incluidos los países menos adelantados y los pequeños Estados insulares en desarrollo, para aumentar significativamente la disponibilidad de datos oportunos, fiables y de gran calidad
                desglosados por ingresos, sexo, edad, raza, origen étnico, estatus migratorio, discapacidad, ubicación geográfica y otras características pertinentes en los contextos nacionales'
            ]);
            MetaContribuye::create([
                'ods_id' => $ods17->id,
                'numero_meta' => '17.19',
                'descripcion' => 'De aquí a 2030, aprovechar las iniciativas existentes para elaborar indicadores que permitan medir los progresos en materia de desarrollo sostenible y complementen el producto interno bruto, y apoyar la creación de capacidad estadística en los países en desarrollo'
            ]);
        }
    }
}

