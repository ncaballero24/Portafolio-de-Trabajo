"""Ocelote: servidor portable Python 3.12+, SQLite, sin dependencias externas."""

import argparse
import hashlib
import hmac
import json
import os
import secrets
import sqlite3
import time
from collections import defaultdict, deque
import economy
from datetime import datetime, timedelta, timezone
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parent
DB = Path(os.environ.get("OCELOTE_DB", str(ROOT / "data" / "ocelote.sqlite3")))
TZ = timezone(timedelta(hours=-6))  # Ciudad de México
LIMITS = defaultdict(deque)
MISSIONS = economy.DAILY
BADGES = [
    dict(
        id="active",
        name="Participación activa",
        goal=20,
        description="20 publicaciones o comentarios",
        art=0,
    ),
    dict(
        id="week",
        name="Semana en racha",
        goal=7,
        description="7 días consecutivos de participación",
        art=1,
    ),
    dict(
        id="answers",
        name="Racha de respuestas",
        goal=5,
        description="Responde en 5 publicaciones distintas",
        art=2,
    ),
    dict(
        id="questions",
        name="Racha de preguntas",
        goal=5,
        description="Publica 5 preguntas académicas",
        art=3,
    ),
    dict(
        id="ranked",
        name="Aporte más rankeado",
        goal=1,
        description="Alcanza el primer puesto con al menos 10 votos netos",
        art=4,
    ),
    dict(
        id="month",
        name="Un mes en racha",
        goal=30,
        description="30 días consecutivos de participación",
        art=5,
    ),
    dict(
        id="two_months",
        name="Dos meses en racha",
        goal=60,
        description="60 días consecutivos de participación",
        art=6,
    ),
]


def today():
    return datetime.now(TZ).date().isoformat()


class DatabaseConnection(sqlite3.Connection):
    """Cierra también el recurso al salir de la transacción."""

    def __exit__(self, *args):
        try:
            return super().__exit__(*args)
        finally:
            self.close()


def connect():
    c = sqlite3.connect(DB, timeout=20, factory=DatabaseConnection)
    c.row_factory = sqlite3.Row
    c.execute("PRAGMA foreign_keys=ON")
    return c


def password_hash(value):
    salt = secrets.token_hex(16)
    key = hashlib.scrypt(value.encode(), salt=salt.encode(), n=16384, r=8, p=1)
    return salt + ":" + key.hex()


def password_ok(value, stored):
    if not stored:
        return False
    salt, key = stored.split(":")
    return hmac.compare_digest(
        hashlib.scrypt(value.encode(), salt=salt.encode(), n=16384, r=8, p=1).hex(), key
    )


def initialize():
    DB.parent.mkdir(parents=True, exist_ok=True)
    with connect() as c:
        c.executescript((ROOT / "schema.sql").read_text(encoding="utf-8"))
        if not c.execute("SELECT 1 FROM users LIMIT 1").fetchone():
            c.execute(
                (
                    "INSERT INTO users(username,password,name,age,student_group,specialty,role) "
                    "VALUES('admin','','Administrador CETis',18,'Personal','Administración','admin')"
                )
            )
            for row in [
                (
                    "Vida estudiantil",
                    "🎓",
                    "Tu día a día en el CETis. Comparte experiencias, oportunidades y consejos.",
                    "#e4efdc",
                ),
                (
                    "Dudas académicas",
                    "📖",
                    "Pregunta, comparte apuntes y aprende en equipo.",
                    "#e5edfc",
                ),
                (
                    "Eventos",
                    "🗓️",
                    "Todo lo que está por suceder en nuestra comunidad.",
                    "#ffebd6",
                ),
                (
                    "Arte y cultura",
                    "🎨",
                    "Un espacio para crear, expresarte y compartir tu talento.",
                    "#f6e3ee",
                ),
                (
                    "Deportes",
                    "⚽",
                    "Encuentra tu equipo, organiza partidos y celebra tus logros.",
                    "#e3efe4",
                ),
                (
                    "Tecnología",
                    "💻",
                    "Proyectos, programación e ideas para construir el futuro.",
                    "#e3eaf3",
                ),
            ]:
                c.execute(
                    "INSERT INTO communities(name,icon,description,color) VALUES(?,?,?,?)",
                    row,
                )
            for username, name, group, specialty, avatar in [
                ("mariana.demo", "Mariana R.", "5A", "Programación", "👩🏽"),
                ("diego.demo", "Diego M.", "3B", "Mecatrónica", "👨🏻"),
                ("sofia.demo", "Sofía L.", "5C", "Administración", "👩🏻"),
            ]:
                c.execute(
                    "INSERT INTO users(username,password,name,age,student_group,specialty,avatar) VALUES(?,?,?,?,?,?,?)",
                    (
                        username,
                        password_hash(secrets.token_urlsafe(32)),
                        name,
                        17,
                        group,
                        specialty,
                        avatar,
                    ),
                )
            for row in [
                (
                    2,
                    2,
                    "¿Armamos un grupo de estudio para matemáticas?",
                    "Podemos repasar juntos para el próximo examen. Me gustaría formar un grupo pequeño y reunirnos en la biblioteca. ¿Quién se apunta?",
                    "question",
                ),
                (
                    3,
                    3,
                    "Hagamos una semana cultural inolvidable 🎉",
                    "Buscamos ideas: música, exposiciones, talleres y proyectos. ¿Qué te gustaría ver en nuestra próxima semana cultural?",
                    "post",
                ),
                (
                    4,
                    5,
                    "Se busca equipo para la cancha ⚽",
                    "Queremos organizar partidos amistosos después de clases. Deja un comentario si quieres participar. ¡Todos los niveles son bienvenidos!",
                    "post",
                ),
            ]:
                c.execute(
                    "INSERT INTO posts(user_id,community_id,title,body,kind) VALUES(?,?,?,?,?)",
                    row,
                )
            c.execute(
                "INSERT INTO comments(post_id,user_id,body) VALUES(1,3,'¡Me apunto! Puedo llevar ejercicios de álgebra.')"
            )
    with connect() as c:
        economy.initialize(c)


def audit(c, uid, action, detail):
    c.execute(
        "INSERT INTO audit(actor,action,detail) VALUES(?,?,?)", (uid, action, detail)
    )


def activity(c, uid, mission, target):
    economy.activity(c, uid, mission, target)
    unlock(c, uid)


def progress(c, uid):
    u = c.execute("SELECT * FROM users WHERE id=?", (uid,)).fetchone()
    posts = c.execute(
        "SELECT COUNT(*) FROM posts WHERE user_id=? AND hidden=0", (uid,)
    ).fetchone()[0]
    comments = c.execute(
        "SELECT COUNT(*) FROM comments WHERE user_id=? AND hidden=0", (uid,)
    ).fetchone()[0]
    answers = c.execute(
        (
            "SELECT COUNT(DISTINCT c.post_id) FROM comments c JOIN posts p ON p.id=c.post_id "
            "WHERE c.user_id=? AND c.hidden=0 AND p.hidden=0 AND p.user_id!=?"
        ),
        (uid, uid),
    ).fetchone()[0]
    questions = c.execute(
        "SELECT COUNT(*) FROM posts WHERE user_id=? AND kind='question' AND hidden=0",
        (uid,),
    ).fetchone()[0]
    top = c.execute(
        (
            "SELECT p.user_id,COALESCE(SUM(v.value),0) score FROM posts p LEFT JOIN votes v ON "
            "p.id=v.post_id WHERE p.hidden=0 GROUP BY p.id ORDER BY score DESC,p.id LIMIT 1"
        )
    ).fetchone()
    return dict(
        active=posts + comments,
        week=u["best_streak"],
        answers=answers,
        questions=questions,
        ranked=int(bool(top and top["user_id"] == uid and top["score"] >= 10)),
        month=u["best_streak"],
        two_months=u["best_streak"],
    )


def unlock(c, uid):
    p = progress(c, uid)
    for b in BADGES:
        if p[b["id"]] >= b["goal"]:
            c.execute(
                "INSERT OR IGNORE INTO badges(user_id,badge) VALUES(?,?)",
                (uid, b["id"]),
            )


def user_view(c, u):
    if not u:
        return None
    d = {
        k: u[k]
        for k in [
            "id",
            "username",
            "name",
            "age",
            "student_group",
            "specialty",
            "bio",
            "avatar",
            "role",
            "xp",
            "gold",
            "streak",
            "best_streak",
        ]
    }
    d["level"] = 1 + u["xp"] // 100
    if u["last_active"] not in [
        today(),
        (datetime.now(TZ).date() - timedelta(days=1)).isoformat(),
    ]:
        d["streak"] = 0
    d["mission_progress"] = economy.missions_view(c, u["id"])
    d["missions"] = [m["id"] for m in d["mission_progress"]["daily"] if m["completed"]]
    d["shop"] = economy.shop_view(c, u["id"])
    d["appearance"] = economy.appearance(c, u["id"])
    preference = c.execute(
        "SELECT * FROM tutorial_preferences WHERE user_id=?", (u["id"],)
    ).fetchone()
    d["tutorial"] = (
        dict(preference) if preference else {"show_on_login": 1, "completed": 0}
    )
    earned = {
        r[0] for r in c.execute("SELECT badge FROM badges WHERE user_id=?", (u["id"],))
    }
    p = progress(c, u["id"])
    d["badges"] = [
        dict(b, earned=b["id"] in earned, progress=min(p[b["id"]], b["goal"]))
        for b in BADGES
    ]
    return d


class App(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=str(ROOT / "static"), **kwargs)

    def log_message(self, fmt, *args):
        pass

    def end_headers(self):
        self.send_header("X-Content-Type-Options", "nosniff")
        self.send_header("Referrer-Policy", "same-origin")
        self.send_header(
            "Content-Security-Policy",
            "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'",
        )
        super().end_headers()

    def json(self, data, status=200, cookie=None):
        raw = json.dumps(data, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Cache-Control", "no-store")
        self.send_header("Content-Length", str(len(raw)))
        if cookie:
            self.send_header("Set-Cookie", cookie)
        self.end_headers()
        self.wfile.write(raw)

    def session(self, c):
        cookies = dict(
            x.strip().split("=", 1)
            for x in self.headers.get("Cookie", "").split(";")
            if "=" in x
        )
        token = hashlib.sha256(cookies.get("ocelote", "").encode()).hexdigest()
        return c.execute(
            "SELECT u.* FROM users u JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires>? AND u.suspended=0",
            (token, time.time()),
        ).fetchone()

    def do_GET(self):
        if not self.path.startswith("/api/"):
            if urlparse(self.path).path not in [
                "/",
                "/index.html",
                "/app.js",
                "/style.css",
                "/assets/referencia-inicio.png",
                "/assets/insignias.png",
                "/assets/comunidades.png",
                "/features.js",
                "/features.css",
            ]:
                requested = (
                    ROOT / "static" / urlparse(self.path).path.lstrip("/")
                ).resolve()
                art_root = (ROOT / "static" / "assets" / "shop").resolve()
                if (
                    not requested.is_relative_to(art_root)
                    or requested.suffix.lower() not in (".png", ".webp", ".jpg", ".svg")
                    or not requested.is_file()
                ):
                    self.send_error(404)
                    return
            return super().do_GET()
        try:
            with connect() as c:
                u = self.session(c)
                uid = u["id"] if u else 0
                if self.path == "/api/state":
                    communities = [
                        dict(r)
                        for r in c.execute(
                            (
                                "SELECT c.*,(SELECT COUNT(*) FROM memberships WHERE community_id=c.id) "
                                "members,EXISTS(SELECT 1 FROM memberships WHERE community_id=c.id AND user_id=?) "
                                "joined FROM communities c"
                            ),
                            (uid,),
                        )
                    ]
                    posts = [
                        dict(r)
                        for r in c.execute(
                            (
                                "SELECT p.*,u.name,u.avatar,u.student_group,u.specialty,c.name "
                                "community,c.icon,COALESCE((SELECT SUM(value) FROM votes WHERE post_id=p.id),0) "
                                "score,COALESCE((SELECT value FROM votes WHERE post_id=p.id AND user_id=?),0) "
                                "vote,EXISTS(SELECT 1 FROM saves WHERE post_id=p.id AND user_id=?) saved,(SELECT "
                                "COUNT(*) FROM comments WHERE post_id=p.id AND hidden=0) comments FROM posts p JOIN "
                                "users u ON u.id=p.user_id JOIN communities c ON c.id=p.community_id WHERE p.hidden=0"
                                " ORDER BY p.id DESC"
                            ),
                            (uid, uid),
                        )
                    ]
                    for post in posts:
                        post["appearance"] = economy.appearance(c, post["user_id"])
                    setup = not c.execute(
                        "SELECT password FROM users WHERE username='admin'"
                    ).fetchone()[0]
                    return self.json(
                        dict(
                            user=user_view(c, u),
                            communities=communities,
                            posts=posts,
                            missions=MISSIONS,
                            badges=BADGES,
                            setup=setup,
                            demo=True,
                        )
                    )
                if self.path.startswith("/api/comments/"):
                    pid = int(self.path.rsplit("/", 1)[1])
                    comments = [
                            dict(r)
                            for r in c.execute(
                                (
                                    "SELECT c.id,c.body,c.created,c.user_id,u.name,u.avatar,u.student_group,u.specialty "
                                    "FROM comments c JOIN users u ON u.id=c.user_id JOIN posts p ON p.id=c.post_id WHERE "
                                    "c.post_id=? AND c.hidden=0 AND p.hidden=0 ORDER BY c.id"
                                ),
                                (pid,),
                            )
                    ]
                    for comment in comments:
                        comment["appearance"] = economy.appearance(c, comment["user_id"])
                    return self.json(comments)
                if self.path == "/api/admin":
                    if not u or u["role"] != "admin":
                        return self.json(
                            {"error": "Acceso exclusivo de administración."}, 403
                        )
                    return self.json(
                        dict(
                            users=[
                                dict(r)
                                for r in c.execute(
                                    "SELECT id,username,name,age,student_group,specialty,role,suspended,xp,gold,created FROM users"
                                )
                            ],
                            reports=[
                                dict(r)
                                for r in c.execute(
                                    "SELECT r.*,p.title,u.name FROM reports r JOIN posts p ON p.id=r.post_id JOIN users u ON u.id=r.user_id ORDER BY r.id DESC"
                                )
                            ],
                            audit=[
                                dict(r)
                                for r in c.execute(
                                    "SELECT a.*,u.name FROM audit a LEFT JOIN users u ON u.id=a.actor ORDER BY a.id DESC LIMIT 100"
                                )
                            ],
                            posts=[
                                dict(r)
                                for r in c.execute(
                                    "SELECT id,title,hidden FROM posts ORDER BY id DESC"
                                )
                            ],
                            comments=[
                                dict(r)
                                for r in c.execute(
                                    "SELECT id,body,hidden FROM comments ORDER BY id DESC"
                                )
                            ],
                        )
                    )
                return self.json({"error": "No encontrado"}, 404)
        except (ValueError, sqlite3.Error):
            return self.json({"error": "No se pudo consultar la información."}, 400)

    def do_POST(self):
        try:
            origin = self.headers.get("Origin")
            if origin and urlparse(origin).netloc != self.headers.get("Host"):
                return self.json({"error": "Origen no permitido."}, 403)
            if self.headers.get("Content-Type", "").split(";")[0] != "application/json":
                return self.json({"error": "Se requiere JSON."}, 415)
            size = int(self.headers.get("Content-Length", "0"))
            if size > 20000:
                return self.json({"error": "Contenido demasiado largo."}, 413)
            d = json.loads(self.rfile.read(size))
            path = self.path
            if not isinstance(d, dict):
                raise ValueError("Solicitud inválida.")
            key = (
                self.client_address[0],
                (
                    "auth"
                    if path in ["/api/login", "/api/register", "/api/setup"]
                    else "write"
                ),
            )
            q = LIMITS[key]
            now = time.time()
            while q and q[0] < now - 60:
                q.popleft()
            if len(q) >= (20 if key[1] == "auth" else 100):
                return self.json(
                    {"error": "Demasiados intentos. Espera un minuto."}, 429
                )
            q.append(now)
            with connect() as c:
                c.execute("BEGIN IMMEDIATE")
                u = self.session(c)
                if path in ["/api/register", "/api/login", "/api/setup"]:
                    username = str(d.get("username", "")).strip().lower()
                    pw = str(d.get("password", ""))
                    if len(pw) > 128:
                        raise ValueError("Contraseña demasiado larga.")
                    if path == "/api/setup":
                        if self.client_address[0] not in ["127.0.0.1", "::1"]:
                            return self.json(
                                {
                                    "error": "Configura al administrador desde el servidor local."
                                },
                                403,
                            )
                        admin = c.execute(
                            "SELECT * FROM users WHERE username='admin'"
                        ).fetchone()
                        if admin["password"]:
                            raise ValueError("El administrador ya está configurado.")
                        if len(pw) < 12:
                            raise ValueError(
                                "Usa al menos 12 caracteres en la contraseña."
                            )
                        c.execute(
                            "UPDATE users SET password=? WHERE id=?",
                            (password_hash(pw), admin["id"]),
                        )
                        uid = admin["id"]
                        audit(c, uid, "configuración", "Administrador activado")
                    elif path == "/api/register":
                        import re

                        if not re.fullmatch("[a-z0-9_.-]{3,32}", username):
                            raise ValueError(
                                "Usuario: de 3 a 32 letras minúsculas, números, punto, guion o guion bajo."
                            )
                        if len(pw) < 12:
                            raise ValueError(
                                "Usa al menos 12 caracteres en la contraseña."
                            )
                        fields = self.profile(d)
                        uid = c.execute(
                            "INSERT INTO users(username,password,name,age,student_group,specialty) VALUES(?,?,?,?,?,?)",
                            (username, password_hash(pw), *fields),
                        ).lastrowid
                        audit(c, uid, "registro", "Nueva cuenta de estudiante")
                    else:
                        account = c.execute(
                            "SELECT * FROM users WHERE username=?", (username,)
                        ).fetchone()
                        if (
                            not account
                            or not password_ok(pw, account["password"])
                            or account["suspended"]
                        ):
                            return self.json(
                                {
                                    "error": "Credenciales incorrectas o cuenta no disponible."
                                },
                                401,
                            )
                        uid = account["id"]
                        audit(c, uid, "inicio de sesión", "Acceso correcto")
                    token = secrets.token_urlsafe(32)
                    c.execute("DELETE FROM sessions WHERE expires<?", (time.time(),))
                    c.execute(
                        "INSERT INTO sessions VALUES(?,?,?)",
                        (
                            hashlib.sha256(token.encode()).hexdigest(),
                            uid,
                            time.time() + 86400,
                        ),
                    )
                    c.commit()
                    secure = (
                        "; Secure"
                        if os.environ.get("OCELOTE_SECURE_COOKIE") == "1"
                        else ""
                    )
                    return self.json(
                        {"ok": True},
                        cookie=f"ocelote={token}; HttpOnly; SameSite=Strict; Path=/; Max-Age=86400{secure}",
                    )
                if not u:
                    return self.json({"error": "Inicia sesión para participar."}, 401)
                uid = u["id"]
                if path == "/api/logout":
                    # Invalida únicamente la sesión actual.
                    cookies = dict(
                        x.strip().split("=", 1)
                        for x in self.headers.get("Cookie", "").split(";")
                        if "=" in x
                    )
                    c.execute(
                        "DELETE FROM sessions WHERE token=?",
                        (
                            hashlib.sha256(
                                cookies.get("ocelote", "").encode()
                            ).hexdigest(),
                        ),
                    )
                    c.commit()
                    return self.json(
                        {"ok": True},
                        cookie="ocelote=; HttpOnly; SameSite=Strict; Path=/; Max-Age=0",
                    )
                elif path == "/api/shop/buy":
                    economy.buy(c, uid, str(d.get("item_id", "")))
                elif path == "/api/shop/equip":
                    economy.equip(
                        c, uid, str(d.get("slot", "")), str(d.get("item_id", ""))
                    )
                    audit(c, uid, "apariencia", "Apariencia actualizada")
                elif path == "/api/tutorial":
                    show = d.get("show_on_login", True)
                    completed = d.get("completed", False)
                    if not isinstance(show, bool) or not isinstance(completed, bool):
                        raise ValueError("Preferencias inválidas.")
                    c.execute(
                        (
                            "INSERT INTO tutorial_preferences VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET "
                            "show_on_login=excluded.show_on_login,completed=MAX(tutorial_preferences.completed,excluded.completed)"
                        ),
                        (uid, int(show), int(completed)),
                    )
                elif path == "/api/profile":
                    fields = self.profile(d)
                    bio = self.text(d, "bio", 0, 300)
                    avatar = d.get("avatar", "🐯")
                    if avatar not in ["🐯", "🦊", "🐱", "🐼", "👩🏽", "👨🏻", "👩🏻"]:
                        raise ValueError("Avatar inválido.")
                    c.execute(
                        "UPDATE users SET name=?,age=?,student_group=?,specialty=?,bio=?,avatar=? WHERE id=?",
                        (*fields, bio, avatar, uid),
                    )
                    audit(c, uid, "perfil", "Perfil actualizado")
                elif path == "/api/join":
                    cid = int(d["id"])
                    if not c.execute(
                        "SELECT 1 FROM communities WHERE id=?", (cid,)
                    ).fetchone():
                        raise ValueError("Comunidad inexistente.")
                    if c.execute(
                        "SELECT 1 FROM memberships WHERE user_id=? AND community_id=?",
                        (uid, cid),
                    ).fetchone():
                        c.execute(
                            "DELETE FROM memberships WHERE user_id=? AND community_id=?",
                            (uid, cid),
                        )
                    else:
                        c.execute("INSERT INTO memberships VALUES(?,?)", (uid, cid))
                        activity(c, uid, "join", cid)
                elif path == "/api/post":
                    title = self.text(d, "title", 5, 160)
                    body = self.text(d, "body", 10, 5000)
                    cid = int(d["community_id"])
                    kind = d.get("kind", "post")
                    if kind not in ["post", "question"]:
                        raise ValueError("Tipo inválido.")
                    if not c.execute(
                        "SELECT 1 FROM memberships WHERE user_id=? AND community_id=?",
                        (uid, cid),
                    ).fetchone():
                        raise ValueError("Únete a la comunidad antes de publicar.")
                    c.execute(
                        "INSERT INTO posts(user_id,community_id,title,body,kind) VALUES(?,?,?,?,?)",
                        (uid, cid, title, body, kind),
                    )
                    if len(body) >= 40:
                        fingerprint = hashlib.sha256(
                            " ".join(body.lower().split()).encode()
                        ).hexdigest()
                        activity(c, uid, "post", fingerprint)
                    audit(c, uid, "publicación", title)
                elif path in ["/api/comment", "/api/vote", "/api/save", "/api/report"]:
                    pid = int(d["id"])
                    post = c.execute(
                        "SELECT * FROM posts WHERE id=? AND hidden=0", (pid,)
                    ).fetchone()
                    if not post:
                        raise ValueError("Publicación no disponible.")
                    if path == "/api/comment":
                        body = self.text(d, "body", 2, 2000)
                        c.execute(
                            "INSERT INTO comments(post_id,user_id,body) VALUES(?,?,?)",
                            (pid, uid, body),
                        )
                        if post["user_id"] != uid and len(body) >= 25:
                            activity(c, uid, "comment", pid)
                        audit(c, uid, "comentario", f"Publicación {pid}")
                    elif path == "/api/vote":
                        value = int(d["value"])
                        if value not in [-1, 1]:
                            raise ValueError("Voto inválido.")
                        if post["user_id"] == uid:
                            raise ValueError("No puedes votar tu propia publicación.")
                        old = c.execute(
                            "SELECT value FROM votes WHERE user_id=? AND post_id=?",
                            (uid, pid),
                        ).fetchone()
                        c.execute(
                            "DELETE FROM votes WHERE user_id=? AND post_id=?",
                            (uid, pid),
                        )
                        if not old or old[0] != value:
                            c.execute(
                                "INSERT INTO votes VALUES(?,?,?)", (uid, pid, value)
                            )
                        unlock(c, post["user_id"])
                    elif path == "/api/save":
                        if not c.execute(
                            "DELETE FROM saves WHERE user_id=? AND post_id=?",
                            (uid, pid),
                        ).rowcount:
                            c.execute("INSERT INTO saves VALUES(?,?)", (uid, pid))
                    else:
                        reason = self.text(d, "reason", 5, 500)
                        c.execute(
                            (
                                "INSERT INTO reports(user_id,post_id,reason) VALUES(?,?,?) ON "
                                "CONFLICT(user_id,post_id) DO UPDATE SET reason=excluded.reason,resolved=0"
                            ),
                            (uid, pid, reason),
                        )
                        audit(c, uid, "reporte", f"Publicación {pid}")
                elif path.startswith("/api/admin/"):
                    if u["role"] != "admin":
                        return self.json(
                            {"error": "Acceso exclusivo de administración."}, 403
                        )
                    if path == "/api/admin/user":
                        target = int(d["id"])
                        account = c.execute(
                            "SELECT * FROM users WHERE id=?", (target,)
                        ).fetchone()
                        if not account or account["role"] == "admin":
                            raise ValueError("No puedes suspender al administrador.")
                        c.execute(
                            "UPDATE users SET suspended=? WHERE id=?",
                            (int(bool(d["suspended"])), target),
                        )
                        c.execute("DELETE FROM sessions WHERE user_id=?", (target,))
                        audit(
                            c,
                            uid,
                            "moderación de usuario",
                            f'Usuario {target}; suspendido={bool(d["suspended"])}',
                        )
                    elif path in ["/api/admin/post", "/api/admin/comment"]:
                        table = "posts" if path.endswith("/post") else "comments"
                        target = int(d["id"])
                        if not c.execute(
                            f"SELECT 1 FROM {table} WHERE id=?", (target,)
                        ).fetchone():
                            raise ValueError("Contenido inexistente.")
                        c.execute(
                            f"UPDATE {table} SET hidden=? WHERE id=?",
                            (int(bool(d["hidden"])), target),
                        )
                        audit(
                            c,
                            uid,
                            "moderación de contenido",
                            f'{table} {target}; oculto={bool(d["hidden"])}',
                        )
                    elif path == "/api/admin/report":
                        c.execute(
                            "UPDATE reports SET resolved=1 WHERE id=?", (int(d["id"]),)
                        )
                        audit(c, uid, "reporte resuelto", str(d["id"]))
                    elif path == "/api/admin/community":
                        name = self.text(d, "name", 3, 60)
                        description = self.text(d, "description", 10, 250)
                        icon = self.text(d, "icon", 1, 8)
                        c.execute(
                            "INSERT INTO communities(name,description,icon) VALUES(?,?,?)",
                            (name, description, icon),
                        )
                        audit(c, uid, "comunidad creada", name)
                    else:
                        raise ValueError("Acción inexistente.")
                else:
                    return self.json({"error": "No encontrado"}, 404)
                c.commit()
                return self.json({"ok": True})
        except sqlite3.IntegrityError:
            return self.json(
                {
                    "error": "Ese usuario o nombre ya existe, o la referencia no es válida."
                },
                409,
            )
        except (ValueError, KeyError, TypeError) as e:
            return self.json({"error": str(e) or "Datos inválidos."}, 400)
        except Exception:
            return self.json({"error": "No se pudo guardar. Inténtalo de nuevo."}, 500)

    def text(self, d, key, minimum, maximum):
        value = str(d.get(key, "")).strip()
        if not minimum <= len(value) <= maximum:
            raise ValueError(f"{key}: requiere entre {minimum} y {maximum} caracteres.")
        return value

    def profile(self, d):
        name = self.text(d, "name", 2, 80)
        age = int(d.get("age", 0))
        group = self.text(d, "student_group", 1, 20)
        specialty = self.text(d, "specialty", 2, 80)
        if not 13 <= age <= 100:
            raise ValueError("La edad debe estar entre 13 y 100 años.")
        return name, age, group, specialty


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=8000)
    args = parser.parse_args()
    initialize()
    server = ThreadingHTTPServer((args.host, args.port), App)
    print(f"Ocelote disponible en http://{args.host}:{args.port}", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        server.server_close()
