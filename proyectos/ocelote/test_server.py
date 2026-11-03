"""Pruebas integradas en una base temporal. No modifica datos del foro."""

import http.cookiejar, json, os, secrets, subprocess, sys, tempfile, time, unittest, urllib.error, urllib.request
from pathlib import Path


class Client:
    def __init__(self, port):
        self.url = f"http://127.0.0.1:{port}/api/"
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
        )

    def call(self, path, data=None, origin=None):
        headers = {"Content-Type": "application/json"}
        if origin:
            headers["Origin"] = origin
        request = urllib.request.Request(
            self.url + path,
            headers=headers,
            data=None if data is None else json.dumps(data).encode(),
        )
        try:
            with self.opener.open(request) as r:
                return r.status, json.load(r)
        except urllib.error.HTTPError as e:
            return e.code, json.load(e)


class ForumTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.tmp = tempfile.TemporaryDirectory()
        cls.port = 18051
        env = dict(os.environ, OCELOTE_DB=str(Path(cls.tmp.name) / "test.sqlite3"))
        cls.process = subprocess.Popen(
            [sys.executable, "server.py", "--port", str(cls.port)],
            cwd=Path(__file__).parent,
            env=env,
            stdout=subprocess.DEVNULL,
        )
        cls.admin = Client(cls.port)
        cls.student = Client(cls.port)
        cls.guest = Client(cls.port)
        for _ in range(100):
            try:
                if cls.guest.call("state")[0] == 200:
                    break
            except OSError:
                time.sleep(0.05)
        else:
            cls.process.terminate()
            cls.process.wait(timeout=10)
            cls.tmp.cleanup()
            raise RuntimeError("Servidor de prueba no disponible")

    @classmethod
    def tearDownClass(cls):
        cls.process.terminate()
        cls.process.wait(timeout=10)
        cls.tmp.cleanup()

    def test_complete_flow(self):
        _, initial = self.guest.call("state")
        self.assertTrue(initial["setup"])
        self.assertIsNone(initial["user"])
        self.assertNotIn("age", initial["posts"][0])
        self.assertNotIn("password", initial["posts"][0])
        self.assertEqual(self.guest.call("admin")[0], 403)
        self.assertEqual(self.guest.call("post", {})[0], 401)
        self.assertEqual(
            self.admin.call("setup", {"password": secrets.token_urlsafe(20)})[0], 200
        )
        self.assertEqual(
            self.admin.call("setup", {"password": secrets.token_urlsafe(20)})[0], 400
        )
        pw = secrets.token_urlsafe(20)
        data = dict(
            username="test.student",
            password=pw,
            name="Estudiante de prueba",
            age=17,
            student_group="5A",
            specialty="Programación",
            role="admin",
            xp=9999,
        )
        self.assertEqual(self.student.call("register", data)[0], 200)
        _, s = self.student.call("state")
        uid = s["user"]["id"]
        self.assertEqual(s["user"]["role"], "student")
        self.assertEqual(s["user"]["xp"], 0)
        self.assertEqual(self.student.call("admin")[0], 403)
        self.assertEqual(
            self.student.call("admin/user", {"id": 2, "suspended": 1})[0], 403
        )
        self.assertEqual(
            self.student.call("join", {"id": 2}, origin="https://evil.invalid")[0], 403
        )
        self.assertEqual(self.student.call("join", {"id": 2})[0], 200)
        _, s = self.student.call("state")
        self.assertEqual(s["user"]["gold"], 25)
        self.student.call("join", {"id": 2})
        self.student.call("join", {"id": 2})
        _, s = self.student.call("state")
        self.assertEqual(s["user"]["gold"], 25)
        self.assertEqual(
            self.student.call(
                "post",
                dict(
                    community_id=3,
                    title="Mi publicación",
                    body="Contenido de prueba válido",
                ),
            )[0],
            400,
        )
        for _ in range(5):
            self.assertEqual(
                self.student.call(
                    "post",
                    dict(
                        community_id=2,
                        title="Una pregunta de prueba",
                        body="¿Cómo resolvemos este ejercicio de álgebra entre todos?",
                        kind="question",
                    ),
                )[0],
                200,
            )
        _, s = self.student.call("state")
        self.assertEqual(s["user"]["gold"], 55)
        self.assertEqual(s["user"]["xp"], 50)
        self.assertEqual(s["user"]["mission_progress"]["daily"][2]["progress"], 1)
        self.assertTrue(
            next(b for b in s["user"]["badges"] if b["id"] == "questions")["earned"]
        )
        pid = s["posts"][0]["id"]
        self.assertEqual(self.student.call("vote", dict(id=pid, value=1))[0], 400)
        for _ in range(2):
            self.assertEqual(
                self.student.call(
                    "comment", dict(id=1, body="Puedo compartir mis apuntes.")
                )[0],
                200,
            )
        _, s = self.student.call("state")
        self.assertEqual(s["user"]["gold"], 125)
        self.assertEqual(s["user"]["xp"], 110)
        self.assertEqual(s["user"]["level"], 2)
        self.assertEqual(len(s["user"]["missions"]), 3)
        self.assertEqual(
            self.student.call("shop/buy", {"item_id": "avatar_01", "price": 0})[0], 400
        )
        self.assertEqual(
            self.student.call("shop/equip", {"item_id": "avatar_01", "slot": "avatar"})[
                0
            ],
            400,
        )
        self.assertEqual(
            self.student.call("tutorial", {"show_on_login": False, "completed": True})[
                0
            ],
            200,
        )
        self.assertEqual(
            self.student.call("state")[1]["user"]["tutorial"]["show_on_login"], 0
        )
        self.assertEqual(s["user"]["streak"], 1)
        self.student.call("vote", dict(id=1, value=1))
        self.student.call("vote", dict(id=1, value=1))
        _, s = self.student.call("state")
        self.assertEqual(next(p for p in s["posts"] if p["id"] == 1)["score"], 0)
        self.student.call("save", dict(id=1))
        _, s = self.student.call("state")
        self.assertTrue(next(p for p in s["posts"] if p["id"] == 1)["saved"])
        self.assertEqual(
            self.student.call(
                "report", dict(id=1, reason="Reporte para verificar moderación")
            )[0],
            200,
        )
        _, a = self.admin.call("admin")
        self.assertEqual(len(a["reports"]), 1)
        self.assertEqual(self.admin.call("admin/post", dict(id=1, hidden=1))[0], 200)
        self.assertEqual(
            self.student.call("comment", dict(id=1, body="Comentario posterior"))[0],
            400,
        )
        self.assertEqual(self.guest.call("comments/1")[1], [])
        self.assertEqual(self.admin.call("admin/post", dict(id=1, hidden=0))[0], 200)
        self.assertEqual(
            self.admin.call("admin/user", dict(id=uid, suspended=1))[0], 200
        )
        self.assertIsNone(self.student.call("state")[1]["user"])
        self.assertEqual(
            self.student.call("login", dict(username="test.student", password=pw))[0],
            401,
        )
        self.assertEqual(
            self.admin.call("admin/user", dict(id=uid, suspended=0))[0], 200
        )
        self.assertEqual(
            self.student.call("login", dict(username="test.student", password=pw))[0],
            200,
        )
        self.assertEqual(self.student.call("logout", {})[0], 200)
        self.assertIsNone(self.student.call("state")[1]["user"])
        self.assertGreater(len(self.admin.call("admin")[1]["audit"]), 10)


if __name__ == "__main__":
    unittest.main(verbosity=2)
