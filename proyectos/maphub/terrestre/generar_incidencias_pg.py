import random
import time
import math
from datetime import datetime
import psycopg

# ─────────────────────────────────────────────
# CONFIG DB
# ─────────────────────────────────────────────
DB_HOST = "localhost"
DB_PORT = 5432
DB_NAME = "Monitoreo_Terrestre"
DB_USER = "postgres"
DB_PASS = "Iris2024"

INTERVALO = 5

# ─────────────────────────────────────────────
# ZONAS BASADAS EN TU FRONTEND (BBOX)
# ─────────────────────────────────────────────
ZONAS = [
    {
        "nombre": "norte-oriente",
        "minLat": 19.1405944,
        "maxLat": 19.2008988,
        "minLon": -96.1430017,
        "maxLon": -96.0926666
    },
    {
        "nombre": "norte-poniente",
        "minLat": 19.1188116,
        "maxLat": 19.2653808,
        "minLon": -96.2682641,
        "maxLon": -96.1200911
    },
    {
        "nombre": "sur-oriente",
        "minLat": 19.045901,
        "maxLat": 19.1530029,
        "minLon": -96.1496577,
        "maxLon": -96.0607374
    },
    {
        "nombre": "sur-poniente",
        "minLat": 19.0137798,
        "maxLat": 19.1633069,
        "minLon": -96.2720176,
        "maxLon": -96.1096871
    }
]

# ─────────────────────────────────────────────
# GENERAR PUNTO EN CUADRANTE
# ─────────────────────────────────────────────
def punto_en_cuadrante(z):
    lat = random.uniform(z["minLat"], z["maxLat"])
    lon = random.uniform(z["minLon"], z["maxLon"])
    return lat, lon

# ─────────────────────────────────────────────
# VALIDAR TIERRA (evitar mar)
# ─────────────────────────────────────────────
def esta_en_tierra(lat, lon):

    if lon > -96.115:
        return False

    if lat > 19.19 and lon > -96.135:
        return False

    if lat < 19.17 and lon > -96.110:
        return False

    return True

# ─────────────────────────────────────────────
# LÓGICA DE TIPOS
# ─────────────────────────────────────────────
def tipo_por_hora():
    hora = datetime.now().hour

    base = [
        "choque",
        "atropellamiento",
        "robo",
        "asalto",
        "vehiculo_descompuesto"
    ]

    if hora >= 21 or hora <= 2:
        base += ["robo", "asalto"]

    if 7 <= hora <= 9:
        base += ["choque", "atropellamiento"]

    if 18 <= hora <= 21:
        base += ["choque", "volcadura"]

    return random.choice(base)

# ─────────────────────────────────────────────
# SEVERIDAD
# ─────────────────────────────────────────────
def severidad_por_tipo(tipo):
    if tipo == "choque":
        return random.choices([1,2,3,4], weights=[25,35,25,15])[0]
    if tipo == "atropellamiento":
        return random.choices([2,3,4,5], weights=[15,35,30,20])[0]
    if tipo == "robo":
        return random.choices([2,3,4], weights=[40,40,20])[0]
    if tipo == "asalto":
        return random.choices([3,4,5], weights=[45,35,20])[0]
    if tipo == "incendio":
        return random.choices([3,4,5], weights=[25,45,30])[0]
    if tipo == "inundacion":
        return random.choices([2,3,4,5], weights=[25,35,25,15])[0]
    if tipo == "volcadura":
        return random.choices([3,4,5], weights=[30,40,30])[0]
    if tipo == "vehiculo_descompuesto":
        return random.choices([1,2], weights=[70,30])[0]

    return random.randint(1,5)

# ─────────────────────────────────────────────
# DESCRIPCIONES
# ─────────────────────────────────────────────
def descripcion_por_tipo(tipo):
    desc = {
        "choque": "Choque entre vehículos en vialidad principal.",
        "atropellamiento": "Atropellamiento reportado, se solicita apoyo.",
        "robo": "Robo reportado por ciudadano.",
        "asalto": "Asalto con violencia reportado.",
        "incendio": "Incendio reportado, posible afectación a vialidad.",
        "inundacion": "Zona con encharcamiento/inundación reportada.",
        "volcadura": "Volcadura reportada, tránsito afectado.",
        "vehiculo_descompuesto": "Vehículo descompuesto obstruyendo carril."
    }
    return desc.get(tipo, "Incidencia reportada.")

# ─────────────────────────────────────────────
# INICIO
# ─────────────────────────────────────────────
print("=== SIMULADOR TERRESTRE VERACRUZ (CUADRANTES) ===")
print(f"Generando incidencias cada {INTERVALO} segundos...\n")

conn = psycopg.connect(
    host=DB_HOST,
    port=DB_PORT,
    dbname=DB_NAME,
    user=DB_USER,
    password=DB_PASS
)

try:
    while True:

        # Elegir zona aleatoria
        z = random.choice(ZONAS)

        # Generar punto válido
        while True:
            lat, lon = punto_en_cuadrante(z)
            if esta_en_tierra(lat, lon):
                break

        tipo = tipo_por_hora()

        # eventos raros
        if random.random() < 0.02:
            tipo = "inundacion"

        if random.random() < 0.015:
            tipo = "incendio"

        severidad = severidad_por_tipo(tipo)
        descripcion = descripcion_por_tipo(tipo)
        ts = datetime.now()

        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO incidencias
                (timestamp, tipo, severidad, descripcion, fuente,
                 latitud, longitud, zona, geom)
                VALUES
                (%s, %s, %s, %s, %s,
                 %s, %s, %s,
                 ST_SetSRID(ST_MakePoint(%s, %s), 4326)::geography);
            """, (
                ts,
                tipo,
                severidad,
                descripcion,
                "simulador",
                round(lat, 6),
                round(lon, 6),
                z["nombre"],
                lon,
                lat
            ))

        conn.commit()

        print(f"[{ts}] {tipo.upper()} (sev:{severidad}) -> ({round(lat,6)}, {round(lon,6)}) | {z['nombre']}")

        time.sleep(INTERVALO)

except KeyboardInterrupt:
    print("\nSimulación detenida")

finally:
    conn.close()
    print("Conexión cerrada")
