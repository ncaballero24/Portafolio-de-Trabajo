"""Reglas económicas en bases aisladas, sin modificar cuentas reales."""

import concurrent.futures
import sqlite3
import tempfile
import unittest
from datetime import date, timedelta
from pathlib import Path
from unittest.mock import patch

import economy
import server


class EconomyTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.database = Path(self.temp.name) / "economy.sqlite3"
        self.db_patch = patch.object(server, "DB", self.database)
        self.db_patch.start()
        server.initialize()
        self.uid = 2
        self.day = date(2026, 10, 26)  # Lunes.

    def tearDown(self):
        self.db_patch.stop()
        self.temp.cleanup()

    def run_db(self, operation):
        connection = server.connect()
        try:
            with connection:
                connection.execute("BEGIN IMMEDIATE")
                return operation(connection)
        finally:
            connection.close()

    def activity(self, kind, target, day=None):
        self.run_db(
            lambda c: economy.activity(c, self.uid, kind, target, day or self.day)
        )

    def user(self):
        return self.run_db(
            lambda c: dict(
                c.execute("SELECT * FROM users WHERE id=?", (self.uid,)).fetchone()
            )
        )

    def missions(self, day=None):
        return self.run_db(
            lambda c: economy.missions_view(c, self.uid, day or self.day)
        )

    def test_daily_progress_cap_and_reset(self):
        self.activity("post", "unique-idea")
        self.assertEqual(self.user()["gold"], 30)
        self.assertEqual(self.missions()["daily"][2]["progress"], 1)
        self.activity("post", "unique-idea")
        self.assertEqual(self.missions()["daily"][2]["progress"], 1)
        self.activity("comment", "thread-1")
        self.assertEqual(self.user()["gold"], 100)
        self.assertEqual(self.user()["xp"], 90)
        self.assertTrue(self.missions()["daily_bonus"]["completed"])
        for i in range(10):
            self.activity("comment", f"thread-{i}")
        self.assertEqual(self.user()["gold"], 100)
        tomorrow = self.day + timedelta(days=1)
        self.assertEqual(self.missions(tomorrow)["earned_today"], 0)
        self.assertTrue(
            all(m["progress"] == 0 for m in self.missions(tomorrow)["daily"])
        )
        self.activity("post", "unique-idea", tomorrow)
        self.assertEqual(self.user()["gold"], 130)
        self.assertEqual(self.user()["streak"], 2)

    def test_weekly_reward_nonconsecutive_days_and_once(self):
        for offset in [0, 1, 3, 5]:
            self.activity("comment", "thread", self.day + timedelta(days=offset))
        self.assertEqual(self.user()["gold"], 4 * 30 + 120)
        self.assertTrue(
            self.missions(self.day + timedelta(days=5))["weekly"]["completed"]
        )
        self.activity("comment", "other-thread", self.day + timedelta(days=5))
        self.assertEqual(self.user()["gold"], 260)
        next_week = self.day + timedelta(days=7)
        self.assertFalse(self.missions(next_week)["weekly"]["completed"])
        self.assertEqual(self.missions(next_week)["weekly"]["progress"], 0)

    def test_join_is_once_and_does_not_farm_streak(self):
        self.activity("join", "community-1")
        self.activity("join", "community-2", self.day + timedelta(days=1))
        self.assertEqual(self.user()["gold"], 25)
        self.assertEqual(self.user()["streak"], 0)
        self.assertEqual(self.missions()["weekly"]["progress"], 0)

    def test_streak_keeps_best_after_break(self):
        for offset in range(3):
            self.activity("post", "idea", self.day + timedelta(days=offset))
        self.activity("post", "idea", self.day + timedelta(days=5))
        self.assertEqual(self.user()["streak"], 1)
        self.assertEqual(self.user()["best_streak"], 3)

    def test_migration_keeps_credentials_balance_and_legacy_claims(self):
        original = self.user()["password"]
        self.run_db(
            lambda c: c.execute(
                "UPDATE users SET gold=45,xp=60 WHERE id=?", (self.uid,)
            )
        )
        self.run_db(
            lambda c: c.execute(
                "INSERT INTO rewards VALUES(?,?,?)",
                (self.uid, self.day.isoformat(), "post"),
            )
        )
        self.run_db(
            lambda c: c.execute(
                "INSERT INTO rewards VALUES(?,?,?)",
                (self.uid, self.day.isoformat(), "join"),
            )
        )
        connection = server.connect()
        try:
            economy.initialize(connection)
            economy.initialize(connection)
        finally:
            connection.close()
        self.assertEqual(self.user()["password"], original)
        self.assertEqual(self.user()["gold"], 45)
        self.assertEqual(self.user()["xp"], 60)
        self.activity("post", "new-idea")
        self.activity("join", "another")
        self.assertEqual(self.user()["gold"], 45)
        self.assertTrue(self.missions()["daily"][0]["completed"])

    def test_purchase_eligibility_inventory_and_equipment(self):
        item = dict(
            economy.CATALOG[0],
            available=True,
            asset="/assets/shop/test.svg",
            level=3,
            streak=3,
        )
        with patch.object(economy, "CATALOG", [item]):
            with self.assertRaisesRegex(ValueError, "requisitos"):
                self.run_db(lambda c: economy.buy(c, self.uid, item["id"]))
            self.run_db(
                lambda c: c.execute(
                    "UPDATE users SET xp=200,best_streak=3,streak=0 WHERE id=?",
                    (self.uid,),
                )
            )
            with self.assertRaisesRegex(ValueError, "suficiente"):
                self.run_db(lambda c: economy.buy(c, self.uid, item["id"]))
            with self.assertRaisesRegex(ValueError, "adquirir"):
                self.run_db(lambda c: economy.equip(c, self.uid, "avatar", item["id"]))
            self.run_db(
                lambda c: c.execute("UPDATE users SET gold=200 WHERE id=?", (self.uid,))
            )
            self.run_db(lambda c: economy.buy(c, self.uid, item["id"]))
            self.assertEqual(self.user()["gold"], 80)
            with self.assertRaisesRegex(ValueError, "inventario"):
                self.run_db(lambda c: economy.buy(c, self.uid, item["id"]))
            self.assertEqual(self.user()["gold"], 80)
            self.run_db(lambda c: economy.equip(c, self.uid, "avatar", item["id"]))
            self.assertEqual(
                self.run_db(lambda c: economy.appearance(c, self.uid))["avatar"]["id"],
                item["id"],
            )
            self.run_db(lambda c: economy.equip(c, self.uid, "avatar", ""))
            self.assertEqual(self.run_db(lambda c: economy.appearance(c, self.uid)), {})

    def test_concurrent_purchase_charges_once(self):
        item = dict(economy.CATALOG[0], available=True, asset="/assets/shop/test.svg")
        self.run_db(
            lambda c: c.execute("UPDATE users SET gold=200 WHERE id=?", (self.uid,))
        )

        def buy():
            try:
                self.run_db(lambda c: economy.buy(c, self.uid, item["id"]))
                return True
            except ValueError:
                return False

        with patch.object(
            economy, "CATALOG", [item]
        ), concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
            results = list(pool.map(lambda _: buy(), range(2)))
        self.assertEqual(sum(results), 1)
        self.assertEqual(self.user()["gold"], 80)

    def test_catalog_assets_and_multiple_equipment_slots(self):
        self.assertEqual(len(economy.CATALOG), 64)
        self.assertEqual(len({item["id"] for item in economy.CATALOG}), 64)
        for item in economy.CATALOG:
            asset = Path(server.__file__).parent / "static" / item["asset"].lstrip("/")
            self.assertTrue(asset.is_file(), item["id"])
        self.run_db(lambda c: c.execute("UPDATE users SET gold=1000 WHERE id=?", (self.uid,)))
        for item_id, slot in [("base_short", "avatar"), ("hair_01", "hair"), ("outfit_01", "outfit"), ("accessory_01", "accessory")]:
            self.run_db(lambda c: economy.buy(c, self.uid, item_id))
            self.run_db(lambda c: economy.equip(c, self.uid, slot, item_id))
        self.assertEqual(self.user()["gold"], 760)
        appearance = self.run_db(lambda c: economy.appearance(c, self.uid))
        self.assertEqual(set(appearance), {"avatar", "hair", "outfit", "accessory"})
        self.assertIsNotNone(appearance["hair"]["rect"])
        self.run_db(lambda c: economy.equip(c, self.uid, "outfit", ""))
        self.assertEqual(len(self.run_db(lambda c: economy.appearance(c, self.uid))), 3)

    def test_pending_art_cannot_be_sold(self):
        self.run_db(
            lambda c: c.execute("UPDATE users SET gold=9999 WHERE id=?", (self.uid,))
        )
        with self.assertRaisesRegex(ValueError, "disponible"):
            self.run_db(lambda c: economy.buy(c, self.uid, "avatar_01"))
        self.assertEqual(self.user()["gold"], 9999)


if __name__ == "__main__":
    unittest.main(verbosity=2)
