PRAGMA journal_mode=WAL;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    age INTEGER NOT NULL,
    student_group TEXT NOT NULL,
    specialty TEXT NOT NULL,
    bio TEXT NOT NULL DEFAULT '',
    avatar TEXT NOT NULL DEFAULT '🐯',
    role TEXT NOT NULL DEFAULT 'student',
    suspended INTEGER DEFAULT 0,
    xp INTEGER DEFAULT 0,
    gold INTEGER DEFAULT 0,
    streak INTEGER DEFAULT 0,
    best_streak INTEGER DEFAULT 0,
    last_active TEXT,
    created TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sessions (
    token TEXT PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    expires REAL
);

CREATE TABLE IF NOT EXISTS communities (
    id INTEGER PRIMARY KEY,
    name TEXT UNIQUE,
    icon TEXT,
    description TEXT,
    color TEXT DEFAULT '#f4e6da'
);

CREATE TABLE IF NOT EXISTS memberships (
    user_id INTEGER REFERENCES users(id),
    community_id INTEGER REFERENCES communities(id),
    PRIMARY KEY(user_id,community_id)
);

CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    community_id INTEGER REFERENCES communities(id),
    title TEXT,
    body TEXT,
    kind TEXT DEFAULT 'post',
    hidden INTEGER DEFAULT 0,
    created TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY,
    post_id INTEGER REFERENCES posts(id),
    user_id INTEGER REFERENCES users(id),
    body TEXT,
    hidden INTEGER DEFAULT 0,
    created TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS votes (
    user_id INTEGER REFERENCES users(id),
    post_id INTEGER REFERENCES posts(id),
    value INTEGER CHECK(value IN (-1,1)),
    PRIMARY KEY(user_id,post_id)
);

CREATE TABLE IF NOT EXISTS saves (
    user_id INTEGER REFERENCES users(id),
    post_id INTEGER REFERENCES posts(id),
    PRIMARY KEY(user_id,post_id)
);

CREATE TABLE IF NOT EXISTS rewards (
    user_id INTEGER REFERENCES users(id),
    day TEXT,
    mission TEXT,
    PRIMARY KEY(user_id,day,mission)
);

CREATE TABLE IF NOT EXISTS badges (
    user_id INTEGER REFERENCES users(id),
    badge TEXT,
    earned TEXT DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(user_id,badge)
);

CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    post_id INTEGER REFERENCES posts(id),
    reason TEXT,
    resolved INTEGER DEFAULT 0,
    created TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id,post_id)
);

CREATE TABLE IF NOT EXISTS audit (
    id INTEGER PRIMARY KEY,
    actor INTEGER REFERENCES users(id),
    action TEXT,
    detail TEXT,
    created TEXT DEFAULT CURRENT_TIMESTAMP
);

