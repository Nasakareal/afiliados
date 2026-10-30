"""Build the priority-colony GeoJSON from INEGI's official DCAH shapefile.

Usage:
    python scripts/build_morelia_priority_colonies.py \
        --shapefile path/to/00as.shp \
        --sections public/maps/electoral/morelia-secciones.geojson \
        --reference database/data/electoral/morelia_priority_colonies_reference.json \
        --output public/maps/electoral/morelia-colonias-prioritarias.geojson

Dependencies: pyshp, pyproj and shapely.
"""

from __future__ import annotations

import argparse
import json
import re
import unicodedata
from collections import defaultdict
from pathlib import Path

import shapefile
from pyproj import CRS, Transformer
from shapely.geometry import shape as shapely_shape


MORELIA_ENTITY = "16"
MORELIA_MUNICIPALITY = "053"

# Only aliases that can be resolved unambiguously against the municipal names.
OFFICIAL_ALIASES = {
    "ACUEDUCTO": ("FOVISSSTE ACUEDUCTO",),
    "CENTRO": ("CENTRO HISTORICO",),
    "FRAY ANTONIO": ("FRAY ANTONIO DE LISBOA",),
    "LOMAS DEL TEC": ("LOMAS DEL TECNOLOGICO",),
    "TORRECILLAS": ("LAS TORRECILLAS",),
}


def normalize(value: str) -> str:
    value = "".join(
        character
        for character in unicodedata.normalize("NFD", value.upper())
        if unicodedata.category(character) != "Mn"
    )
    value = re.sub(r"[^A-Z0-9]+", " ", value).strip()
    value = re.sub(r"^(FRACC|COL|LOC) ", "", value)
    value = re.sub(r"^U HAB ", "", value)
    value = re.sub(r"^INF ", "INFONAVIT ", value)
    value = re.sub(r"^5 DE ", "CINCO DE ", value)
    return value


def transform_coordinates(value, transformer: Transformer):
    if value and isinstance(value[0], (int, float)):
        longitude, latitude = transformer.transform(value[0], value[1])
        return [round(longitude, 6), round(latitude, 6)]
    return [transform_coordinates(item, transformer) for item in value]


def arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--shapefile", type=Path, required=True)
    parser.add_argument("--sections", type=Path, required=True)
    parser.add_argument("--reference", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    return parser.parse_args()


def assign_section_priorities(features: list[dict], reference: dict, sections_path: Path) -> dict:
    priorities = {int(row["seccion"]): row for row in reference["filas"]}
    sections_geojson = json.loads(sections_path.read_text(encoding="utf-8"))
    priority_sections = []
    for feature in sections_geojson["features"]:
        section = int(feature["properties"]["SECCION"])
        if section in priorities:
            priority_sections.append((section, priorities[section], shapely_shape(feature["geometry"])))

    spatial_count = 0
    name_fallback_count = 0
    for feature in features:
        properties = feature["properties"]
        colony = shapely_shape(feature["geometry"])
        overlaps = []
        for section, row, section_geometry in priority_sections:
            overlap_area = colony.intersection(section_geometry).area
            if overlap_area > 0:
                overlaps.append((overlap_area, section, row))

        if overlaps:
            overlaps.sort(reverse=True, key=lambda item: item[0])
            overlap_area, section, row = overlaps[0]
            priority = int(row["prioridad"])
            properties["prioridad"] = priority
            properties["prioridad_etiqueta"] = reference["metadata"]["prioridades"][str(priority)]
            properties["prioridad_fuente"] = "cruce_espacial_seccion"
            properties["seccion_prioridad"] = section
            properties["porcentaje_colonia_en_seccion"] = round((overlap_area / colony.area) * 100, 1)
            properties["secciones_prioritarias_intersectadas"] = sorted(item[1] for item in overlaps)
            spatial_count += 1
        elif properties["prioridad"] is not None:
            properties["prioridad_fuente"] = "coincidencia_nombre_referencia"
            properties["seccion_prioridad"] = properties["secciones_referencia"][0]
            properties["porcentaje_colonia_en_seccion"] = None
            properties["secciones_prioritarias_intersectadas"] = []
            name_fallback_count += 1
        else:
            properties["prioridad_fuente"] = None
            properties["seccion_prioridad"] = None
            properties["porcentaje_colonia_en_seccion"] = None
            properties["secciones_prioritarias_intersectadas"] = []

    return {
        "cruce_espacial": spatial_count,
        "respaldo_por_nombre": name_fallback_count,
        "total": spatial_count + name_fallback_count,
    }


def main() -> None:
    args = arguments()
    with args.reference.open(encoding="utf-8") as stream:
        reference = json.load(stream)

    reader = shapefile.Reader(str(args.shapefile), encoding="latin1")
    field_names = [field[0] for field in reader.fields[1:]]
    prj_path = args.shapefile.with_suffix(".prj")
    transformer = Transformer.from_crs(
        CRS.from_wkt(prj_path.read_text(encoding="utf-8")),
        CRS.from_epsg(4326),
        always_xy=True,
    )

    official_records = []
    official_by_name = defaultdict(list)
    for shape_record in reader.iterShapeRecords():
        properties = dict(zip(field_names, shape_record.record))
        if properties["CVE_ENT"] != MORELIA_ENTITY or properties["CVE_MUN"] != MORELIA_MUNICIPALITY:
            continue
        index = len(official_records)
        official_records.append((shape_record, properties))
        official_by_name[normalize(properties["NOM_ASEN"])].append(index)

    matches = defaultdict(list)
    unmatched = []
    listed_names = 0
    for row in reference["filas"]:
        for listed_name in filter(None, (part.strip() for part in row["colonias"].split(","))):
            listed_names += 1
            normalized_name = normalize(listed_name)
            candidates = (normalized_name, *OFFICIAL_ALIASES.get(normalized_name, ()))
            record_indexes = {
                index
                for candidate in candidates
                for index in official_by_name.get(candidate, [])
            }
            if not record_indexes:
                unmatched.append({"seccion": row["seccion"], "nombre": listed_name})
                continue
            for index in record_indexes:
                matches[index].append(
                    {
                        "seccion": row["seccion"],
                        "prioridad": row["prioridad"],
                        "nombre_referencia": listed_name,
                    }
                )

    features = []
    for index, (shape_record, properties) in enumerate(official_records):
        match_rows = matches.get(index, [])
        priority = max((row["prioridad"] for row in match_rows), default=None)
        sections = sorted({row["seccion"] for row in match_rows})
        reference_names = sorted({row["nombre_referencia"] for row in match_rows})
        geometry = shape_record.shape.__geo_interface__
        geometry["coordinates"] = transform_coordinates(geometry["coordinates"], transformer)
        features.append(
            {
                "type": "Feature",
                "properties": {
                    "cvegeo": properties["CVEGEO"],
                    "nombre": properties["NOM_ASEN"],
                    "tipo": properties["TIPO"],
                    "codigo_postal": properties["CP"],
                    "fecha_actualizacion": properties["FECHA_ACT"],
                    "institucion": properties["INSTITUCIO"],
                    "prioridad": priority,
                    "prioridad_etiqueta": reference["metadata"]["prioridades"].get(str(priority)),
                    "secciones_referencia": sections,
                    "nombres_referencia": reference_names,
                },
                "geometry": geometry,
            }
        )

    priority_stats = assign_section_priorities(features, reference, args.sections)
    features.sort(key=lambda feature: (feature["properties"]["prioridad"] or 0, feature["properties"]["nombre"]))
    result = {
        "type": "FeatureCollection",
        "metadata": {
            "fuente_geometrica": "INEGI, Delimitación de Colonias y otros Asentamientos Humanos (DCAH), edición 2024",
            "institucion_origen": "Instituto Municipal de Planeación de Morelia",
            "fecha_actualizacion_municipal": "11/2021",
            "cobertura": "Localidad urbana 0001 del municipio de Morelia, Michoacán",
            "sistema_origen": "MEXICO_ITRF_2008_LCC",
            "sistema_salida": "EPSG:4326",
            "nombres_listados": listed_names,
            "nombres_coincidentes": listed_names - len(unmatched),
            "nombres_sin_coincidencia": unmatched,
            "poligonos_oficiales": len(features),
            "poligonos_prioritarios": priority_stats["total"],
            "poligonos_prioritarios_por_cruce_espacial": priority_stats["cruce_espacial"],
            "poligonos_prioritarios_por_nombre": priority_stats["respaldo_por_nombre"],
            "regla_prioridad": "La colonia hereda la prioridad de la sección prioritaria con mayor superficie de intersección; si no existe intersección, se conserva una coincidencia nominal inequívoca.",
        },
        "features": features,
    }
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(
        json.dumps(result, ensure_ascii=False, separators=(",", ":")),
        encoding="utf-8",
    )
    print(
        f"{len(features)} official polygons ({priority_stats['total']} priority: "
        f"{priority_stats['cruce_espacial']} spatial + {priority_stats['respaldo_por_nombre']} name fallback); "
        f"{listed_names - len(unmatched)}/{listed_names} listed names matched; "
        f"{len(unmatched)} unmatched"
    )


if __name__ == "__main__":
    main()
