"""Misiones, inventario y tienda. Todas las operaciones se validan en servidor.

El llamador abre una transacción BEGIN IMMEDIATE antes de modificar saldos.
Los eventos, pagos y compras tienen claves únicas para impedir duplicados.
"""

from datetime import date, datetime, timedelta, timezone

TZ = timezone(timedelta(hours=-6))
DAILY = [
    {
        "id": "post",
        "title": "Comparte una idea",
        "detail": "Publica una idea de al menos 40 caracteres en una comunidad.",
        "goal": 1,
        "xp": 30,
        "gold": 30,
        "action": "post",
    },
    {
        "id": "comment",
        "title": "Ayuda a un compañero",
        "detail": "Escribe una respuesta de al menos 25 caracteres en una publicación ajena.",
        "goal": 1,
        "xp": 30,
        "gold": 30,
        "action": "comment",
    },
    {
        "id": "participate",
        "title": "Conecta con tu comunidad",
        "detail": "Realiza 2 aportes distintos: publica o responde en conversaciones diferentes.",
        "goal": 2,
        "xp": 20,
        "gold": 20,
        "action": "comment",
    },
]

# El catálogo es editorial; el cliente nunca decide precios ni requisitos.
import json
from pathlib import Path

CATALOG = json.loads(
    Path(__file__).with_name("catalog.json").read_text(encoding="utf8")
)
SLOTS = ("avatar", "hair", "hat", "outfit", "accessory", "object")


def local_day():
    return datetime.now(TZ).date()


def week_start(day):
    return (day - timedelta(days=day.weekday())).isoformat()


def initialize(c):
    """Migración aditiva: no reemplaza cuentas, contraseñas ni saldos."""
    c.executescript(
        """
        CREATE TABLE IF NOT EXISTS activity_events (
            user_id INTEGER REFERENCES users(id),
            day TEXT NOT NULL,
            kind TEXT NOT NULL,
            target TEXT NOT NULL,
            PRIMARY KEY(user_id, day, kind, target)
        );
        CREATE TABLE IF NOT EXISTS economy_rewards (
            user_id INTEGER REFERENCES users(id),
            period TEXT NOT NULL,
            reward TEXT NOT NULL,
            gold INTEGER NOT NULL,
            xp INTEGER NOT NULL,
            created TEXT DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(user_id, period, reward)
        );
        CREATE TABLE IF NOT EXISTS inventory (
            user_id INTEGER REFERENCES users(id),
            item_id TEXT NOT NULL,
            paid INTEGER NOT NULL,
            acquired TEXT DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(user_id, item_id)
        );
        CREATE TABLE IF NOT EXISTS equipment (
            user_id INTEGER REFERENCES users(id),
            slot TEXT NOT NULL,
            item_id TEXT NOT NULL,
            PRIMARY KEY(user_id, slot),
            FOREIGN KEY(user_id, item_id) REFERENCES inventory(user_id, item_id)
        );
        CREATE TABLE IF NOT EXISTS tutorial_preferences (
            user_id INTEGER PRIMARY KEY REFERENCES users(id),
            show_on_login INTEGER NOT NULL DEFAULT 1,
            completed INTEGER NOT NULL DEFAULT 0
        );
    """
    )
    # Los pagos antiguos cuentan como realizados, sin volver a entregar monedas.
    c.execute(
        """
        INSERT OR IGNORE INTO economy_rewards(user_id, period, reward, gold, xp)
        SELECT user_id, day, mission,
               CASE mission WHEN 'post' THEN 15 WHEN 'comment' THEN 20 ELSE 10 END,
               CASE mission WHEN 'post' THEN 20 WHEN 'comment' THEN 30 ELSE 10 END
        FROM rewards WHERE mission IN ('post', 'comment')
    """
    )
    c.execute(
        """
        INSERT OR IGNORE INTO economy_rewards(user_id, period, reward, gold, xp)
        SELECT DISTINCT user_id, 'once', 'explore', 10, 10
        FROM rewards WHERE mission='join'
    """
    )
    c.commit()


def grant(c, uid, period, reward, gold, xp):
    inserted = c.execute(
        "INSERT OR IGNORE INTO economy_rewards(user_id,period,reward,gold,xp) VALUES(?,?,?,?,?)",
        (uid, period, reward, gold, xp),
    ).rowcount
    if inserted:
        c.execute("UPDATE users SET gold=gold+?,xp=xp+? WHERE id=?", (gold, xp, uid))
        c.execute(
            "INSERT INTO audit(actor,action,detail) VALUES(?,?,?)",
            (uid, "recompensa", f"{reward}: +{gold} oro, +{xp} XP"),
        )
    return bool(inserted)


def activity(c, uid, kind, target, day=None):
    day = day or local_day()
    stamp = day.isoformat()
    inserted = c.execute(
        "INSERT OR IGNORE INTO activity_events VALUES(?,?,?,?)",
        (uid, stamp, kind, str(target)),
    ).rowcount
    if not inserted:
        return
    if kind == "join":
        # Primera exploración: una sola vez en toda la cuenta, no al volver a entrar.
        grant(c, uid, "once", "explore", 25, 20)
        return

    user = c.execute("SELECT * FROM users WHERE id=?", (uid,)).fetchone()
    if user["last_active"] != stamp:
        yesterday = (day - timedelta(days=1)).isoformat()
        streak = user["streak"] + 1 if user["last_active"] == yesterday else 1
        c.execute(
            "UPDATE users SET streak=?,best_streak=MAX(best_streak,?),last_active=? WHERE id=?",
            (streak, streak, stamp, uid),
        )
    counts = {
        row["kind"]: row["total"]
        for row in c.execute(
            "SELECT kind,COUNT(*) total FROM activity_events WHERE user_id=? AND day=? AND kind IN ('post','comment') GROUP BY kind",
            (uid, stamp),
        )
    }
    counts["participate"] = sum(counts.values())
    for mission in DAILY:
        if counts.get(mission["id"], 0) >= mission["goal"]:
            grant(c, uid, stamp, mission["id"], mission["gold"], mission["xp"])
    complete = c.execute(
        "SELECT COUNT(*) FROM economy_rewards WHERE user_id=? AND period=? AND reward IN ('post','comment','participate')",
        (uid, stamp),
    ).fetchone()[0]
    if complete == 3:
        grant(c, uid, stamp, "daily_bonus", 20, 10)
    week = week_start(day)
    active_days = c.execute(
        "SELECT COUNT(DISTINCT day) FROM activity_events WHERE user_id=? AND day BETWEEN ? AND ? AND kind IN ('post','comment')",
        (uid, week, stamp),
    ).fetchone()[0]
    if active_days >= 4:
        grant(c, uid, week, "weekly", 120, 60)


def missions_view(c, uid, day=None):
    day = day or local_day()
    stamp = day.isoformat()
    paid = {
        row[0]
        for row in c.execute(
            "SELECT reward FROM economy_rewards WHERE user_id=? AND period=?",
            (uid, stamp),
        )
    }
    counts = {
        row[0]: row[1]
        for row in c.execute(
            "SELECT kind,COUNT(*) FROM activity_events WHERE user_id=? AND day=? AND kind IN ('post','comment') GROUP BY kind",
            (uid, stamp),
        )
    }
    counts["participate"] = sum(counts.values())
    missions = [
        dict(
            m,
            progress=(
                m["goal"] if m["id"] in paid else min(m["goal"], counts.get(m["id"], 0))
            ),
            completed=m["id"] in paid,
        )
        for m in DAILY
    ]
    start = week_start(day)
    days = c.execute(
        "SELECT COUNT(DISTINCT day) FROM activity_events WHERE user_id=? AND day BETWEEN ? AND ? AND kind IN ('post','comment')",
        (uid, start, stamp),
    ).fetchone()[0]
    weekly_paid = bool(
        c.execute(
            "SELECT 1 FROM economy_rewards WHERE user_id=? AND period=? AND reward='weekly'",
            (uid, start),
        ).fetchone()
    )
    explored = bool(
        c.execute(
            "SELECT 1 FROM economy_rewards WHERE user_id=? AND period='once' AND reward='explore'",
            (uid,),
        ).fetchone()
    )
    earned = c.execute(
        (
            "SELECT COALESCE(SUM(gold),0) FROM economy_rewards WHERE user_id=? AND period=? AND "
            "reward IN ('post','comment','participate','daily_bonus')"
        ),
        (uid, stamp),
    ).fetchone()[0]
    return {
        "day": stamp,
        "daily": missions,
        "daily_bonus": {"gold": 20, "xp": 10, "completed": "daily_bonus" in paid},
        "weekly": {
            "goal": 4,
            "progress": min(4, days),
            "gold": 120,
            "xp": 60,
            "completed": weekly_paid,
        },
        "explore": {"gold": 25, "xp": 20, "completed": explored},
        "earned_today": earned,
        "daily_max": 100,
        "next_reset": (day + timedelta(days=1)).isoformat() + "T00:00:00-06:00",
        "week_reset": (date.fromisoformat(start) + timedelta(days=7)).isoformat()
        + "T00:00:00-06:00",
    }


def shop_view(c, uid):
    user = c.execute("SELECT * FROM users WHERE id=?", (uid,)).fetchone()
    if not user:
        return []
    owned = {
        row[0]
        for row in c.execute("SELECT item_id FROM inventory WHERE user_id=?", (uid,))
    }
    equipped = {
        row[0]
        for row in c.execute("SELECT item_id FROM equipment WHERE user_id=?", (uid,))
    }
    return [
        dict(
            item,
            owned=item["id"] in owned,
            equipped=item["id"] in equipped,
            unlocked=1 + user["xp"] // 100 >= item["level"]
            and user["best_streak"] >= item["streak"],
            affordable=user["gold"] >= item["price"],
        )
        for item in CATALOG
    ]


def appearance(c, uid):
    ids = {
        row[0]
        for row in c.execute("SELECT item_id FROM equipment WHERE user_id=?", (uid,))
    }
    return {
        item["slot"]: {
            key: item.get(key)
            for key in ("id", "name", "asset", "rect", "placement", "slot")
        }
        for item in CATALOG
        if item["id"] in ids
    }


def buy(c, uid, item_id):
    item = next((item for item in shop_view(c, uid) if item["id"] == item_id), None)
    if not item or not item["available"] or not item["asset"]:
        raise ValueError("Este diseño todavía no está disponible.")
    if item["owned"]:
        raise ValueError("Este artículo ya está en tu inventario.")
    if not item["unlocked"]:
        raise ValueError("Todavía no cumples los requisitos de nivel o racha.")
    changed = c.execute(
        "UPDATE users SET gold=gold-? WHERE id=? AND gold>=?",
        (item["price"], uid, item["price"]),
    ).rowcount
    if not changed:
        raise ValueError(
            "No tienes suficiente oro. Completa misiones para conseguirlo."
        )
    c.execute(
        "INSERT INTO inventory(user_id,item_id,paid) VALUES(?,?,?)",
        (uid, item_id, item["price"]),
    )
    c.execute(
        "INSERT INTO audit(actor,action,detail) VALUES(?,?,?)",
        (uid, "compra", f"{item_id}: -{item['price']} oro"),
    )


def equip(c, uid, slot, item_id):
    if slot not in SLOTS:
        raise ValueError("Categoría de apariencia inválida.")
    if not item_id:
        c.execute("DELETE FROM equipment WHERE user_id=? AND slot=?", (uid, slot))
        return
    item = next(
        (item for item in CATALOG if item["id"] == item_id and item["slot"] == slot),
        None,
    )
    if (
        not item
        or not c.execute(
            "SELECT 1 FROM inventory WHERE user_id=? AND item_id=?", (uid, item_id)
        ).fetchone()
    ):
        raise ValueError("Primero debes adquirir este artículo.")
    c.execute(
        "INSERT INTO equipment VALUES(?,?,?) ON CONFLICT(user_id,slot) DO UPDATE SET item_id=excluded.item_id",
        (uid, slot, item_id),
    )
