"""Construye la capa socioeconómica seccional de Morelia.

Uso:
  python scripts/build_morelia_socioeconomic_layer.py \
    --coneval GRS_AGEB_urbana_2020.xlsx \
    --ageb-shapefile 16a.shp \
    --sections public/maps/electoral/morelia-secciones.geojson \
    --output database/data/electoral/morelia_socioeconomic_sections_2020.json

Requiere openpyxl, pyshp, shapely y pyproj. La agregación estima la población o
viviendas representadas por la fracción de área de cada AGEB que intersecta la
sección. Es una aproximación territorial agregada, no información individual.
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path

import openpyxl
import shapefile
from pyproj import CRS, Transformer
from shapely.geometry import shape
from shapely.ops import transform
from shapely.strtree import STRtree


GRADE_VALUE = {"Muy bajo": 1, "Bajo": 2, "Medio": 3, "Alto": 4, "Muy alto": 5}
GRADE_LABEL = {1: "Muy bajo", 2: "Bajo", 3: "Medio", 4: "Alto", 5: "Muy alto"}

POPULATION_INDICATORS = {
    "analfabetismo": 10,
    "educacion_basica_incompleta": 13,
    "sin_derechohabiencia": 14,
}

HOUSING_INDICATORS = {
    "hacinamiento": 15,
    "sin_agua_entubada": 16,
    "sin_drenaje": 18,
    "sin_electricidad": 19,
    "piso_tierra": 20,
    "sin_refrigerador": 22,
    "sin_computadora": 25,
    "sin_internet": 26,
}


def arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--coneval", required=True, type=Path)
    parser.add_argument("--ageb-shapefile", required=True, type=Path)
    parser.add_argument("--sections", required=True, type=Path)
    parser.add_argument("--output", required=True, type=Path)
    return parser.parse_args()


def numeric(value):
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def read_coneval(path: Path) -> dict[str, dict]:
    sheet = openpyxl.load_workbook(path, read_only=True, data_only=True).active
    rows: dict[str, dict] = {}
    for values in sheet.iter_rows(min_row=7, values_only=True):
        if str(values[2]).strip() != "16053":
            continue
        cvegeo = str(values[7]).strip()
        population = numeric(values[8])
        dwellings = numeric(values[9])
        if population is None or dwellings is None:
            continue
        rows[cvegeo] = {
            "poblacion": population,
            "viviendas": dwellings,
            "grado": str(values[27]).strip(),
            "indicadores": {
                name: numeric(values[column])
                for name, column in {**POPULATION_INDICATORS, **HOUSING_INDICATORS}.items()
            },
        }
    return rows


def valid_geometry(geometry):
    if geometry.is_valid:
        return geometry
    return geometry.buffer(0)


def read_agebs(path: Path, indicators: dict[str, dict]):
    reader = shapefile.Reader(str(path.with_suffix("")), encoding="latin1")
    geometries = []
    records = []
    for item in reader.iterShapeRecords():
        cvegeo = str(item.record["CVEGEO"])
        if cvegeo not in indicators:
            continue
        geometry = valid_geometry(shape(item.shape.__geo_interface__))
        if geometry.is_empty or geometry.area <= 0:
            continue
        geometries.append(geometry)
        records.append({"cvegeo": cvegeo, **indicators[cvegeo], "area": geometry.area})
    return geometries, records, CRS.from_wkt(path.with_suffix(".prj").read_text(encoding="utf-8"))


def weighted_average(parts: list[tuple[float, float]]) -> float | None:
    denominator = sum(weight for _, weight in parts)
    if denominator <= 0:
        return None
    return sum(value * weight for value, weight in parts) / denominator


def round_or_none(value: float | None, digits: int = 1):
    return None if value is None else round(value, digits)


def main() -> None:
    args = arguments()
    indicators = read_coneval(args.coneval)
    ageb_geometries, ageb_records, ageb_crs = read_agebs(args.ageb_shapefile, indicators)
    tree = STRtree(ageb_geometries)
    transformer = Transformer.from_crs(CRS.from_epsg(4326), ageb_crs, always_xy=True)
    sections_geojson = json.loads(args.sections.read_text(encoding="utf-8"))
    output = {}

    for feature in sections_geojson["features"]:
        section_id = str(int(feature["properties"]["SECCION"]))
        section_geometry = valid_geometry(transform(transformer.transform, shape(feature["geometry"])))
        if section_geometry.is_empty or section_geometry.area <= 0:
            continue

        population = 0.0
        dwellings = 0.0
        covered_area = 0.0
        ageb_ids = []
        grade_parts = []
        population_parts = {name: [] for name in POPULATION_INDICATORS}
        housing_parts = {name: [] for name in HOUSING_INDICATORS}

        for index in tree.query(section_geometry, predicate="intersects"):
            ageb_geometry = ageb_geometries[int(index)]
            record = ageb_records[int(index)]
            intersection = section_geometry.intersection(ageb_geometry)
            if intersection.is_empty or intersection.area <= 1:
                continue
            fraction = min(1.0, intersection.area / record["area"])
            estimated_population = record["poblacion"] * fraction
            estimated_dwellings = record["viviendas"] * fraction
            population += estimated_population
            dwellings += estimated_dwellings
            covered_area += intersection.area
            ageb_ids.append(record["cvegeo"])
            grade_parts.append((GRADE_VALUE[record["grado"]], estimated_population))
            for name in POPULATION_INDICATORS:
                if record["indicadores"][name] is not None:
                    population_parts[name].append((record["indicadores"][name], estimated_population))
            for name in HOUSING_INDICATORS:
                if record["indicadores"][name] is not None:
                    housing_parts[name].append((record["indicadores"][name], estimated_dwellings))

        coverage = min(1.0, covered_area / section_geometry.area)
        if coverage < 0.01 or population < 10:
            continue

        grade_value = weighted_average(grade_parts)
        grade_rounded = max(1, min(5, int((grade_value or 1) + .5)))
        section_indicators = {
            name: round_or_none(weighted_average(parts))
            for name, parts in {**population_parts, **housing_parts}.items()
        }
        output[section_id] = {
            "grado_rezago": GRADE_LABEL[grade_rounded],
            "grado_rezago_valor": round_or_none(grade_value, 2),
            "cobertura_territorial": round(coverage, 4),
            "calidad_cobertura": "Alta" if coverage >= .8 else "Media" if coverage >= .5 else "Parcial",
            "poblacion_representada": int(round(population)),
            "viviendas_representadas": int(round(dwellings)),
            "agebs": len(set(ageb_ids)),
            "indicadores": section_indicators,
        }

    payload = {
        "metadata": {
            "titulo": "Contexto socioeconómico agregado por sección electoral de Morelia",
            "anio": 2020,
            "fuente_indicadores": "CONEVAL, Grado de Rezago Social a nivel AGEB urbana 2020",
            "fuente_geometria": "INEGI, Marco Geoestadístico. Censo de Población y Vivienda 2020",
            "metodo": "Intersección espacial AGEB urbana–sección. Los porcentajes se ponderan por población o viviendas estimadas mediante la fracción de área intersectada.",
            "limitacion": "La estimación supone distribución uniforme dentro de cada AGEB. El rezago social no mide ingreso, pobreza ni preferencias individuales.",
            "umbral_inclusion": "Al menos 1% de cobertura territorial y 10 habitantes censales estimados.",
        },
        "secciones": dict(sorted(output.items(), key=lambda item: int(item[0]))),
    }
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Generadas {len(output)} secciones con contexto socioeconómico.")


if __name__ == "__main__":
    main()
