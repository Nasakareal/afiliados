# Inteligencia electoral municipal de Morelia

## Alcance

Este módulo analiza exclusivamente elecciones de Ayuntamiento de Morelia. La unidad de observación es la sección electoral y los periodos comparables son 2018, 2021 y 2024.

No se mezclan resultados de Presidencia de la República, gubernatura, diputaciones ni consultas. Esa separación corrige el principal problema de comparabilidad del estudio anterior: una variación entre cargos distintos puede responder al tipo de elección, las candidaturas, las coaliciones y el nivel de participación, no a un cambio territorial estable.

## Fuentes

- Instituto Electoral de Michoacán, cómputo municipal de Morelia por casilla de 2018.
- Instituto Electoral de Michoacán, resultado de Ayuntamiento de Morelia por casilla de 2021.
- Instituto Electoral de Michoacán, resultados de Ayuntamientos de los cómputos municipales por casilla de 2024.
- Lista nominal de la elección federal concurrente de 2018, usada únicamente para completar el denominador de participación que no aparece en el archivo municipal de ese año.
- Cartografía seccional vigente incluida en `public/maps/out/SECCION.geojson`.
- CONEVAL, Grado de Rezago Social a nivel AGEB urbana 2020.
- INEGI, Marco Geoestadístico del Censo de Población y Vivienda 2020.

Las ligas oficiales permanecen dentro de los metadatos de `database/data/electoral/morelia_municipal_sections.json` y se muestran en el tablero.

## Índice de oferta y demanda electoral

El tablero implementa las siete variables y ponderaciones del documento metodológico entregado:

- IOE: padrón electoral como proxy de población en edad de votar (10%), promedio histórico de votos válidos (10%), lista nominal 2024 (15%) y participación histórica promedio (15%).
- IDE: votos de referencia necesarios para ganar en 2024 (30%), porcentaje de Morena en 2024 invertido (10%) y variación de participación 2021–2024 invertida (10%).
- IODE: división directa de IOE entre IDE. Un valor de 1 indica equilibrio. Por debajo de 1 existe déficit y aumenta la prioridad; por encima de 1 existe superávit y disminuye la prioridad.

Cada variable se transforma primero a un rango relativo de 0 a 1 entre las secciones comparables. Después se aplican las ponderaciones originales sin ampliar la escala: IOE suma hasta 0.50 e IDE suma hasta 0.50. IODE se calcula como `IOE / IDE`, sin convertirlo a una escala de 0 a 100. Las secciones sin padrón INE o sin resultados en 2021 y 2024 se conservan como `null`: nunca se convierten en cero.

El archivo `database/data/electoral/morelia_padron_2023.json` procede del archivo abierto del INE `DatosAbiertos-derfe-pdln_edms_sexo_20231228.xlsx`. El padrón es un proxy operativo y no debe presentarse como población censal de 18 años y más. El importador reproducible está en `scripts/import_morelia_electoral_roll.php`.

## Capa socioeconómica

La capa `database/data/electoral/morelia_socioeconomic_sections_2020.json` cruza las AGEB urbanas de CONEVAL e INEGI con la cartografía seccional. Para cada intersección se estima población y viviendas representadas mediante la proporción del área de la AGEB que cae dentro de la sección. Los indicadores de población se ponderan por población estimada y los de vivienda por viviendas estimadas.

Se publican grado de rezago social, analfabetismo, educación básica incompleta, falta de derechohabiencia de salud, hacinamiento, agua, drenaje, electricidad, piso de tierra, refrigerador, computadora e internet. Cada sección informa cobertura territorial, número de AGEB utilizadas y población censal representada.

Se incluyen únicamente secciones con al menos 1% de cobertura territorial y 10 habitantes estimados. El resultado cubre 373 secciones; 317 tienen cobertura territorial de 80% o más. La generación es reproducible con `scripts/build_morelia_socioeconomic_layer.py`.

La asignación por superficie supone una distribución uniforme dentro de cada AGEB. Es una aproximación territorial y no sustituye microdatos. El grado de rezago social tampoco mide ingreso o pobreza, por lo que el tablero no etiqueta secciones como “ricas” o “pobres”.

## Normalización

1. Los resultados por casilla se agregan por sección.
2. Las casillas especiales se excluyen del cálculo territorial porque reciben votos de personas en tránsito y no representan al electorado residente de la sección donde se instalaron.
3. Los votos emitidos por partido, coalición o candidatura común se asignan a la candidatura municipal correspondiente.
4. El porcentaje de Morena y su alianza se calcula sobre votos válidos: votos totales menos votos nulos y candidaturas no registradas.
5. La participación se calcula como votos totales entre lista nominal.
6. Una sección se clasifica como ganada cuando la suma de la candidatura de Morena y sus aliados supera a cada candidatura competidora normalizada.

## Comparabilidad territorial

La cartografía vigente de Morelia contiene 402 polígonos. Hay 342 claves observadas en los tres procesos; solo estas pueden sostener una comparación completa 2018–2024. Las claves nuevas o reconfiguradas conservan el historial disponible, pero no se imputan ni se fuerzan hacia una sección anterior.

El tablero muestra por sección:

- victorias observadas en los tres procesos;
- porcentaje de voto de Morena y su alianza en 2024;
- participación 2024;
- margen frente al principal competidor;
- cambio del porcentaje entre 2021 y 2024;
- lista nominal 2024;
- serie histórica de resultados y ganador normalizado.

## Lectura responsable

Los resultados describen comportamiento agregado y no permiten inferir la preferencia de una persona. Tampoco constituyen una recomendación de mensaje político. La capa socioeconómica sirve para planeación territorial general; no debe usarse para perfilar personas o adaptar persuasión política a partir de carencias sociales.

## Hallazgos de control

- Secciones vigentes: 402.
- Secciones comparables en 2018, 2021 y 2024: 342.
- Secciones ganadas por la candidatura de Morena y sus aliados en los tres procesos: 7.
- Claves con victoria continua: 1015, 1241, 1244, 1245, 1250, 1252 y 1282.

Estas cifras se validan automáticamente en `tests/Unit/ElectoralIntelligenceDataTest.php`.
