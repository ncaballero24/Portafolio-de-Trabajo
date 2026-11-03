"use strict";
const $ = (s) => document.querySelector(s),
  esc = (v) =>
    String(v ?? "").replace(
      /[&<>"']/g,
      (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c],
    );
let state,
  sort = "para-ti",
  communityTab = "explorar",
  adminTab = "usuarios",
  search = "",
  timer;
const icons = {
  inicio: "⌂",
  comunidades: "♧",
  perfil: "☺",
  misiones: "⚑",
  logros: "♜",
  guardados: "♧",
  admin: "⚙",
  tienda: "◈",
};
const badgePositions = [
  [-11, -37],
  [-126, -38],
  [-237, -37],
  [-349, -37],
  [-51, -170],
  [-174, -170],
  [-300, -170],
];
async function api(path, data) {
  const response = await fetch("/api/" + path, {
    method: data === undefined ? "GET" : "POST",
    headers: data === undefined ? {} : { "Content-Type": "application/json" },
    body: data === undefined ? undefined : JSON.stringify(data),
  });
  let result;
  try {
    result = await response.json();
  } catch {
    throw Error("No se pudo conectar con el servidor.");
  }
  if (!response.ok) throw Error(result.error || "No se pudo completar la acción.");
  return result;
}
function toast(text) {
  $("#toast").textContent = text;
  $("#toast").classList.add("visible");
  clearTimeout(timer);
  timer = setTimeout(() => $("#toast").classList.remove("visible"), 3500);
}
async function refresh() {
  state = await api("state");
  render();
  scheduleDayRefresh();
}
function route() {
  return (location.hash.slice(1) || "inicio").split("/");
}
function needUser() {
  if (state.user) return true;
  auth("login");
  return false;
}
function openModal(html) {
  $("#dialog").classList.remove("tour-dialog");
  $("#dialog").removeAttribute("aria-label");
  $("#dialog-content").innerHTML = html;
  if (!$("#dialog").open) $("#dialog").showModal();
  setTimeout(() => $('#dialog-content input:not([type="checkbox"])')?.focus(), 50);
}
function closeModal() {
  $("#dialog").close();
  $("#dialog").classList.remove("tour-dialog");
}
function userAvatar(u) {
  return avatarMarkup(u);
}
function heading(title, sub, extra = "") {
  return /* HTML */ `<div class="page-heading">
    <div>
      <h1>${title}</h1>
      <p>${sub}</p>
    </div>
    ${extra}
  </div>`;
}
function empty(title, description, action = "") {
  return /* HTML */ `<div class="empty">
    <div class="empty-icon">✦</div>
    <h2>${title}</h2>
    <p>${description}</p>
    ${action}
  </div>`;
}
function primary(label, action, extra = "") {
  return /* HTML */ `<button class="primary" data-action="${action}" ${extra}>${label}</button>`;
}
function dateLabel(date) {
  const ms = Date.now() - new Date(date.replace(" ", "T") + "Z").getTime();
  if (ms < 60000) return "Ahora";
  if (ms < 3600000) return `Hace ${Math.floor(ms / 60000)} min`;
  if (ms < 86400000) return `Hace ${Math.floor(ms / 3600000)} h`;
  return new Intl.DateTimeFormat("es-MX", { day: "numeric", month: "short" }).format(
    new Date(date.replace(" ", "T") + "Z"),
  );
}
function render() {
  const [page, id] = route(),
    u = state.user;
  $("#account").innerHTML = avatarMarkup(u || {});
  document.querySelectorAll(".mobile-nav a").forEach((link) => {
    const active = link.hash === "#" + page;
    link.classList.toggle("active", active);
    if (active) link.setAttribute("aria-current", "page");
    else link.removeAttribute("aria-current");
  });
  $("#nav").innerHTML = [
    ["inicio", "Inicio"],
    ["perfil", "Mi perfil"],
    ["comunidades", "Comunidades"],
    ["misiones", "Misiones"],
    ["tienda", "Tienda y avatar"],
    ["logros", "Mis logros"],
    ["guardados", "Guardados"],
    ...(u?.role === "admin" ? [["admin", "Administración"]] : []),
  ]
    .map(
      ([key, label]) =>
        /* HTML */ `<a class="nav-item ${page === key ? "active" : ""}" href="#${key}"
          ><span>${icons[key]}</span>${label}${key === "misiones"
            ? /* HTML */ `<span class="nav-count">${3 - (u?.missions.length || 0)}</span>`
            : ""}</a
        >`,
    )
    .join("");
  $("#community-nav").innerHTML = state.communities
    .slice(0, 8)
    .map(
      (c) =>
        /* HTML */ `<a
          class="community-link ${page === "comunidad" && +id === c.id ? "selected" : ""}"
          href="#comunidad/${c.id}"
          ><span class="community-icon" style="background:${esc(c.color)}">${esc(c.icon)}</span
          >${esc(c.name)}${c.joined ? '<small style="margin-left:auto">✓</small>' : ""}</a
        >`,
    )
    .join("");
  renderRight();
  if (["perfil", "misiones", "logros", "guardados", "tienda"].includes(page) && !u) {
    $("#main").innerHTML =
      heading(
        {
          tienda: "Tu tienda y avatar",
          perfil: "Mi perfil",
          misiones: "Tus próximas misiones",
          logros: "Tu colección de logros",
          guardados: "Tus publicaciones guardadas",
        }[page],
        "Tu aventura en la comunidad comienza aquí.",
      ) +
      empty(
        "Deja tu primera huella",
        "Crea tu cuenta para participar, ganar experiencia y encontrar tu comunidad.",
        primary("Crear mi cuenta", "register") +
          ' <button data-action="login">Iniciar sesión</button>',
      );
    return;
  }
  if (page === "tienda") renderShop();
  else if (page === "comunidades") renderCommunities();
  else if (page === "perfil") renderProfile();
  else if (page === "misiones") renderMissions();
  else if (page === "logros") renderBadges();
  else if (page === "post") renderDetail(+id);
  else if (page === "admin") renderAdmin();
  else renderFeed(page, id);
}
function renderRight() {
  let u = state.user;
  $("#right").innerHTML = /* HTML */ `<section class="card">
      <div class="welcome">
        ${userAvatar(u || {})}
        <div>
          <strong>${u ? "¡Hola, " + esc(u.name.split(" ")[0]) + "!" : "¡Hola, ocelote!"}</strong
          ><small>${u ? "Cada aporte cuenta." : "Tu próxima aventura empieza aquí."}</small>
        </div>
        ${u ? /* HTML */ `<span class="level-chip">NIVEL ${u.level}</span>` : ""}
      </div>
      ${u
        ? /* HTML */ `<div class="progress-head">
              <span>Tu experiencia</span><span>${u.xp % 100} / 100 XP</span>
            </div>
            <div class="progress"><i style="width:${u.xp % 100}%"></i></div>
            <div class="wallet">
              <b>◈ ${u.gold} <small>oro</small></b
              ><span class="streak">🔥 ${u.streak} días en racha</span>
            </div>`
        : /* HTML */ `<div style="margin-top:17px">
            ${primary("Unirme a la comunidad", "register")}
          </div>`}
    </section>
    <section class="card">
      <div class="card-head">
        <h2>⚑ Misiones del día</h2>
        <a href="#misiones">Ver todas ↗</a>
      </div>
      ${state.missions
        .map(
          (m) =>
            /* HTML */ `<div class="mission">
              <span class="check ${u?.missions.includes(m.id) ? "done" : ""}"
                >${u?.missions.includes(m.id) ? "✓" : ""}</span
              >
              <div class="mission-copy">
                <strong>${m.title}</strong
                ><small
                  >+${m.gold} monedas de oro ·
                  ${u?.mission_progress.daily.find((x) => x.id === m.id)?.progress ||
                  0}/${m.goal}</small
                >
              </div>
              <span class="reward">+${m.xp} XP</span>
            </div>`,
        )
        .join("")}
      <div class="daily-footer">
        🎁 Completa las 3: +20 oro extra<br />↻ Una nueva oportunidad cada día
      </div>
    </section>
    <section class="card">
      <div class="card-head">
        <h2>🐾 Tu compañero</h2>
        <a href="#perfil">${u ? "Nivel " + u.level : "Conócelo"}</a>
      </div>
      <div
        class="companion"
        role="img"
        aria-label="Ocelote de la comunidad, ilustración de referencia"
      ></div>
      <div class="pet-caption"><span>♥ ♥ ♥</span><span>Siempre en tu equipo.</span></div>
    </section>
    <section class="card announcement">
      <div class="eyebrow">HECHO POR Y PARA ESTUDIANTES</div>
      <h3>Aquí, tu voz cuenta.</h3>
      <p>Una idea, una pregunta o una respuesta pueden hacer la diferencia.</p>
      <a href="#comunidades">Encuentra tu comunidad →</a>
    </section>
    <div class="right-foot">
      Ocelote · Comunidad CETis 15<br />Versión local · Contenido inicial de demostración
    </div>`;
}
function hero() {
  return /* HTML */ `<section class="hero">
    <div class="hero-art" role="img" aria-label="Campus CETis 15 y mascota Ocelote"></div>
    <div class="eyebrow">BIENVENIDO A TU COMUNIDAD</div>
    <h1>Tu comunidad.<br />Tu próxima aventura.</h1>
    <p>Comparte, aprende y crece<br />con otros ocelotes.</p>
    <div class="hero-tags">
      <span>♧ Conecta</span><span>▤ Aprende</span><span>✦ Deja huella</span>
    </div>
  </section>`;
}
function postCard(p, full = false) {
  const bodyMarkup =
    '<p class="post-body">' +
    esc(full ? p.body : p.body.slice(0, 240)) +
    (!full && p.body.length > 240 ? "…" : "") +
    "</p>";
  return /* HTML */ `<article class="post">
    <div class="post-meta">
      ${userAvatar(p)}
      <div class="meta-copy">
        <strong>${esc(p.name)}</strong> · ${dateLabel(p.created)}<br /><small
          >${esc(p.student_group)} · ${esc(p.specialty)}</small
        >
      </div>
      <a class="community-pill" href="#comunidad/${p.community_id}">${esc(p.community)}</a>
    </div>
    <h3><a href="#post/${p.id}">${esc(p.title)}</a></h3>
    ${bodyMarkup}
    <div class="post-actions">
      <div class="voter">
        <button
          aria-label="Votar a favor"
          class="${p.vote === 1 ? "selected" : ""}"
          data-action="vote"
          data-id="${p.id}"
          data-value="1"
        >
          ↑</button
        >${p.score}<button
          aria-label="Votar en contra"
          class="${p.vote === -1 ? "selected" : ""}"
          data-action="vote"
          data-id="${p.id}"
          data-value="-1"
        >
          ↓
        </button>
      </div>
      <a href="#post/${p.id}" style="font-size:11px;color:#90838c">▢ ${p.comments} comentarios</a
      ><button class="save ${p.saved ? "selected" : ""}" data-action="save" data-id="${p.id}">
        ${p.saved ? "▣ Guardado" : "♧ Guardar"}</button
      ><button
        data-action="report"
        data-id="${p.id}"
        aria-label="Reportar publicación"
        title="Reportar"
      >
        ⚑
      </button>
    </div>
  </article>`;
}
function renderFeed(page, id) {
  let posts = state.posts,
    top = "";
  if (page === "comunidad") {
    const c = state.communities.find((c) => c.id === +id);
    if (!c) {
      $("#main").innerHTML = empty(
        "Comunidad no encontrada",
        "Explora las comunidades disponibles.",
      );
      return;
    }
    posts = posts.filter((p) => p.community_id === c.id);
    top =
      /* HTML */ `<a class="back" href="#comunidades">← Todas las comunidades</a>` +
      heading(
        `${esc(c.icon)} ${esc(c.name)}`,
        esc(c.description),
        /* HTML */ `<button
          class="${c.joined ? "" : "primary"}"
          data-action="join"
          data-id="${c.id}"
        >
          ${c.joined ? "✓ Miembro" : "＋ Unirme"}
        </button>`,
      );
  } else if (page === "guardados") {
    posts = posts.filter((p) => p.saved);
    top = heading("Tus ideas favoritas", "Vuelve a las conversaciones que quieres conservar.");
  } else top = hero();
  if (search)
    posts = posts.filter((p) =>
      (p.title + " " + p.body + " " + p.community + " " + p.name)
        .toLowerCase()
        .includes(search.toLowerCase()),
    );
  if (sort === "destacados") posts = [...posts].sort((a, b) => b.score - a.score);
  else if (sort === "para-ti")
    posts = [...posts].sort(
      (a, b) =>
        Number(state.communities.find((c) => c.id === b.community_id)?.joined) -
        Number(state.communities.find((c) => c.id === a.community_id)?.joined),
    );
  $("#main").innerHTML =
    top +
    /* HTML */ `<div class="tabs">
      ${[
        ["para-ti", "✦ Para ti"],
        ["recientes", "◷ Recientes"],
        ["destacados", "☆ Destacados"],
      ]
        .map(
          ([key, label]) =>
            /* HTML */ `<button
              class="tab ${sort === key ? "active" : ""}"
              data-action="sort"
              data-value="${key}"
            >
              ${label}
            </button>`,
        )
        .join("")}<span class="feed-label">${posts.length} conversaciones</span>
    </div>` +
    (posts.map((p) => postCard(p)).join("") ||
      empty(
        "La conversación empieza contigo",
        search
          ? "No encontramos resultados. Prueba con otras palabras."
          : "Comparte una idea o guarda una publicación para verla aquí.",
        primary("Crear publicación", "compose"),
      ));
}
function renderCommunities() {
  let list = state.communities.filter((c) =>
    (c.name + " " + c.description).toLowerCase().includes(search.toLowerCase()),
  );
  if (communityTab === "mias") list = list.filter((c) => c.joined);
  if (communityTab === "populares") list = [...list].sort((a, b) => b.members - a.members);
  const pos = [
    [25.627, 50.107],
    [65.502, 50.107],
    [25.627, 71.413],
    [65.502, 71.413],
    [25.627, 93.469],
    [65.502, 93.469],
  ];
  $("#main").innerHTML =
    heading("Encuentra tu comunidad", "Conecta con quienes comparten tus intereses.") +
    /* HTML */ `<div class="tabs">
        ${[
          ["explorar", "✦ Explorar"],
          ["mias", "♧ Mis comunidades"],
          ["populares", "↗ Populares"],
        ]
          .map(
            ([key, label]) =>
              /* HTML */ `<button
                class="tab ${communityTab === key ? "active" : ""}"
                data-action="community-tab"
                data-value="${key}"
              >
                ${label}
              </button>`,
          )
          .join("")}
      </div>
      <div class="grid">
        ${list
          .map(
            (c) =>
              /* HTML */ `<article class="community-card">
                <a href="#comunidad/${c.id}"
                  ><div
                    class="community-art"
                    style="background-color:${esc(c.color)};${c.id <= 6
                      ? "background-position:" + pos[c.id - 1][0] + "% " + pos[c.id - 1][1] + "%"
                      : "background-image:none"}"
                  ></div
                ></a>
                <div class="community-inner">
                  <a class="community-title" href="#comunidad/${c.id}"
                    ><span>${esc(c.icon)}</span>
                    <h3>${esc(c.name)}</h3></a
                  >
                  <p>${esc(c.description)}</p>
                  <div class="community-bottom">
                    <small>${c.members} ${c.members === 1 ? "miembro" : "miembros"}</small
                    ><button
                      class="${c.joined ? "" : "primary"}"
                      data-action="join"
                      data-id="${c.id}"
                    >
                      ${c.joined ? "✓ Miembro" : "＋ Unirme"}
                    </button>
                  </div>
                </div>
              </article>`,
          )
          .join("")}
      </div>` +
    (list.length
      ? ""
      : empty("Un lugar por descubrir", "Explora las comunidades y únete a tus favoritas."));
}
function badgesHtml(list) {
  return /* HTML */ `<div class="badge-grid">
    ${list
      .map(
        (b) =>
          /* HTML */ `<article class="badge ${b.earned ? "" : "locked"}">
            <div
              class="badge-art"
              role="img"
              aria-label="Insignia ${esc(b.name)}"
              style="background-position:${badgePositions[b.art][0]}px ${badgePositions[
                b.art
              ][1]}px"
            ></div>
            <h3>${esc(b.name)}</h3>
            <p>${esc(b.description)}</p>
            <div class="progress"><i style="width:${(100 * b.progress) / b.goal}%"></i></div>
            <span class="badge-status"
              >${b.earned ? "✦ Desbloqueada" : `◈ ${b.progress} / ${b.goal}`}</span
            >
          </article>`,
      )
      .join("")}
  </div>`;
}
function renderProfile() {
  const u = state.user;
  const own = state.posts.filter((p) => p.user_id === u.id);
  $("#main").innerHTML =
    heading("Mi perfil", "Cada aporte cuenta en tu aventura.") +
    /* HTML */ `<section class="profile-banner">
        <div class="profile-avatar">${avatarMarkup(u, "large-avatar")}</div>
        <div>
          <h2>${esc(u.name)}</h2>
          <p>Nivel ${u.level} · Ocelote ${u.level < 5 ? "explorador" : "aventurero"}</p>
          <p>${esc(u.student_group)} · ${esc(u.specialty)}</p>
          <small>${u.age} años · Solo visible para ti y administración</small>
        </div>
        <button data-action="edit-profile">✎ Editar perfil</button>
      </section>
      ${u.bio ? /* HTML */ `<p class="body-text muted">${esc(u.bio)}</p>` : ""}
      <div class="stats">
        <div class="stat"><b>${u.xp}</b><small>Experiencia total</small></div>
        <div class="stat"><b>◈ ${u.gold}</b><small>Monedas de oro</small></div>
        <div class="stat"><b>${u.streak} 🔥</b><small>Días en racha</small></div>
      </div>
      <section class="card">
        <div class="card-head">
          <h2>Tu estilo también cuenta</h2>
          <a href="#tienda">Tienda y avatar →</a>
        </div>
        <p class="muted">Usa tu oro para construir tu colección y equipar tus diseños favoritos.</p>
      </section>
      <div class="section-heading">
        <h2>Tu colección de logros</h2>
        <a href="#logros">Ver los 7 logros →</a>
      </div>
      ${badgesHtml(u.badges.slice(0, 3))}
      <div class="section-heading">
        <h2>Mis publicaciones</h2>
        <span class="muted">${own.length}</span>
      </div>
      ${own.map((p) => postCard(p)).join("") ||
      empty(
        "Tu primera idea tiene un lugar aquí",
        "Publica en una comunidad y gana tus primeros puntos.",
        primary("Crear publicación", "compose"),
      )}`;
}
function renderBadges() {
  $("#main").innerHTML =
    heading("Insignias de la comunidad", "Cada insignia cuenta una parte de tu historia.") +
    /* HTML */ `<div class="notice">
      ✦ ${state.user.badges.filter((b) => b.earned).length} de 7 desbloqueadas. Las insignias
      obtenidas permanecen en tu colección.
    </div>` +
    badgesHtml(state.user.badges) +
    /* HTML */ `<p class="subtle">
      Criterios iniciales: las rachas de respuestas y preguntas se miden con 5 aportes. “Aporte más
      rankeado” reconoce la publicación líder con al menos 10 votos netos.
    </p>`;
}
async function renderDetail(id) {
  const p = state.posts.find((p) => p.id === id);
  if (!p) {
    $("#main").innerHTML = empty(
      "Publicación no disponible",
      "Puede haber sido retirada por moderación.",
    );
    return;
  }
  $("#main").innerHTML =
    /* HTML */ `<a class="back" href="#comunidad/${p.community_id}">← ${esc(p.community)}</a>` +
    postCard(p, true) +
    /* HTML */ `<section class="card">
      <h2>La conversación <small>(${p.comments})</small></h2>
      ${state.user
        ? /* HTML */ `<form id="comment-form" data-id="${id}" class="comment-form">
            <input
              name="body"
              aria-label="Escribe tu comentario"
              placeholder="¿Qué te gustaría aportar?"
              required
              minlength="2"
              maxlength="2000"
            /><button class="primary">Comentar</button>
          </form>`
        : /* HTML */ `<button data-action="login">Inicia sesión para comentar</button>`}
      <div id="comments">Cargando comentarios…</div>
    </section>`;
  try {
    const comments = await api("comments/" + id);
    if (route()[0] !== "post" || +route()[1] !== id) return;
    $("#comments").innerHTML =
      comments
        .map(
          (c) =>
            /* HTML */ `<div class="comment">
              <div class="post-meta">
                ${userAvatar(c)}
                <div>
                  <strong>${esc(c.name)}</strong> · ${dateLabel(c.created)}<br /><small
                    >${esc(c.student_group)} · ${esc(c.specialty)}</small
                  >
                </div>
              </div>
              <p>${esc(c.body)}</p>
            </div>`,
        )
        .join("") || '<p class="muted">Sé el primero en aportar a esta conversación.</p>';
  } catch (e) {
    toast(e.message);
  }
}
function profileFields(u = {}) {
  return /* HTML */ `<label
      >Nombre<input
        name="name"
        value="${esc(u.name)}"
        required
        minlength="2"
        maxlength="80"
        autocomplete="name"
    /></label>
    <div class="form-row">
      <label
        >Edad<input
          name="age"
          type="number"
          min="13"
          max="100"
          value="${u.age || ""}"
          required /></label
      ><label
        >Grupo<input
          name="student_group"
          value="${esc(u.student_group)}"
          placeholder="Ej. 5A"
          required
          maxlength="20"
      /></label>
    </div>
    <label
      >Especialidad<input
        name="specialty"
        value="${esc(u.specialty)}"
        placeholder="Ej. Programación"
        required
        minlength="2"
        maxlength="80"
        list="specialties"
      /><datalist id="specialties">
        <option>Programación</option>
        <option>Administración</option>
        <option>Mecatrónica</option>
        <option>Contabilidad</option>
        <option>Electrónica</option>
      </datalist></label
    >`;
}
function auth(mode) {
  const register = mode === "register",
    setup = mode === "setup";
  openModal(
    /* HTML */ `<div class="eyebrow">OCELOTE · CETis 15</div>
      <h2>
        ${setup
          ? "Activa la administración"
          : register
            ? "Tu comunidad te espera."
            : "Qué bueno verte de nuevo."}
      </h2>
      <p class="intro">
        ${setup
          ? "El usuario admin ya está reservado. Define su contraseña para activar la cuenta."
          : register
            ? "Crea tu perfil y empieza a dejar huella."
            : "Entra para seguir con tu aventura."}
      </p>
      <form id="auth-form" data-mode="${mode}">
        ${!setup
          ? /* HTML */ `<label
              >Usuario<input
                name="username"
                required
                autocomplete="username"
                minlength="3"
                maxlength="32"
                pattern="[a-zA-Z0-9_.-]+"
                placeholder="tu.usuario"
            /></label>`
          : ""}${register ? profileFields() : ""}<label
          >${setup ? "Contraseña de administrador" : "Contraseña"}<input
            name="password"
            type="password"
            autocomplete="${mode === "login" ? "current-password" : "new-password"}"
            required
            minlength="${mode === "login" ? 1 : 12}"
            maxlength="128"
            placeholder="${mode === "login" ? "Tu contraseña" : "Al menos 12 caracteres"}"
        /></label>
        <div class="form-error" role="alert"></div>
        <div class="form-actions">
          <button class="primary">
            ${setup ? "Activar administrador" : register ? "Crear mi cuenta" : "Iniciar sesión"}
          </button>
        </div>
      </form>
      <div class="auth-footer">
        ${setup
          ? "Solo se configura una vez, desde este servidor."
          : register
            ? '¿Ya tienes cuenta? <button class="text-button" data-action="login">Inicia sesión</button>'
            : '¿Primera vez aquí? <button class="text-button" data-action="register">Crea tu cuenta</button>'}${state.setup &&
        !setup
          ? '<br><button class="text-button" data-action="setup">Configurar administrador local</button>'
          : ""}
      </div>`,
  );
}
function compose() {
  if (!needUser()) return;
  const joined = state.communities.filter((c) => c.joined);
  if (!joined.length) {
    closeModal();
    location.hash = "comunidades";
    toast("Únete a una comunidad para crear tu primera publicación.");
    return;
  }
  const current = route()[0] === "comunidad" ? +route()[1] : 0;
  openModal(
    /* HTML */ `<h2>Una idea puede conectar.</h2>
      <p class="intro">Comparte algo con tu comunidad.</p>
      <form id="post-form">
        <div class="form-row">
          <label
            >Comunidad<select name="community_id">
              ${joined
                .map(
                  (c) =>
                    /* HTML */ `<option value="${c.id}" ${c.id === current ? "selected" : ""}>
                      ${esc(c.name)}
                    </option>`,
                )
                .join("")}
            </select></label
          ><label
            >Tipo<select name="kind">
              <option value="post">Publicación</option>
              <option value="question">Pregunta académica</option>
            </select></label
          >
        </div>
        <label
          >Título<input
            name="title"
            required
            minlength="5"
            maxlength="160"
            placeholder="¿Qué quieres compartir?" /></label
        ><label
          >Tu publicación<textarea
            name="body"
            required
            minlength="10"
            maxlength="5000"
            placeholder="Dale contexto a tu idea…"
            rows="6"
          ></textarea>
        </label>
        <div class="form-error" role="alert"></div>
        <div class="form-actions">
          <button type="button" data-action="close">Cancelar</button
          ><button class="primary">Publicar</button>
        </div>
      </form>`,
  );
}
async function renderAdmin() {
  if (state.user?.role !== "admin") {
    $("#main").innerHTML = empty(
      "Administración",
      "Este espacio está reservado al administrador.",
      primary("Iniciar sesión", "login"),
    );
    return;
  }
  $("#main").innerHTML =
    heading("Administración", "Usuarios, contenido y actividad de la comunidad.") +
    '<div class="loading">Consultando registros…</div>';
  try {
    const a = await api("admin");
    if (route()[0] !== "admin") return;
    let content = "";
    if (adminTab === "usuarios")
      content = /* HTML */ `<div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Estudiante</th>
              <th>Edad</th>
              <th>Grupo / especialidad</th>
              <th>XP / oro</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            ${a.users
              .map(
                (u) =>
                  /* HTML */ `<tr>
                    <td>
                      ${esc(u.name)}<small
                        >@${esc(u.username)} ·
                        ${u.role === "admin" ? "Administrador" : "Estudiante"}</small
                      >
                    </td>
                    <td>${u.age}</td>
                    <td>${esc(u.student_group)}<small>${esc(u.specialty)}</small></td>
                    <td>${u.xp} / ${u.gold}</td>
                    <td>
                      ${u.role === "admin"
                        ? "Administrador"
                        : /* HTML */ `<button
                            data-action="admin-user"
                            data-id="${u.id}"
                            data-value="${u.suspended ? 0 : 1}"
                          >
                            ${u.suspended ? "Reactivar" : "Suspender"}
                          </button>`}
                    </td>
                  </tr>`,
              )
              .join("")}
          </tbody>
        </table>
      </div>`;
    if (adminTab === "contenido")
      content =
        /* HTML */ `<h3>Publicaciones</h3>` +
        a.posts
          .map(
            (p) =>
              /* HTML */ `<div class="admin-row">
                <div>${esc(p.title)}<small>${p.hidden ? "Oculta" : "Visible"}</small></div>
                <button data-action="admin-post" data-id="${p.id}" data-value="${p.hidden ? 0 : 1}">
                  ${p.hidden ? "Restaurar" : "Ocultar"}
                </button>
              </div>`,
          )
          .join("") +
        /* HTML */ `<h3 style="margin-top:25px">Comentarios</h3>` +
        a.comments
          .map(
            (c) =>
              /* HTML */ `<div class="admin-row">
                <div>${esc(c.body)}<small>${c.hidden ? "Oculto" : "Visible"}</small></div>
                <button
                  data-action="admin-comment"
                  data-id="${c.id}"
                  data-value="${c.hidden ? 0 : 1}"
                >
                  ${c.hidden ? "Restaurar" : "Ocultar"}
                </button>
              </div>`,
          )
          .join("");
    if (adminTab === "reportes")
      content =
        a.reports
          .map(
            (r) =>
              /* HTML */ `<div class="admin-row">
                <div>
                  <strong>${esc(r.title)}</strong>
                  <p>${esc(r.reason)}</p>
                  <small
                    >Reportado por ${esc(r.name)} · ${r.resolved ? "Resuelto" : "Pendiente"}</small
                  ><a class="text-button" href="#post/${r.post_id}">Ver publicación →</a>
                </div>
                ${r.resolved
                  ? ""
                  : /* HTML */ `<button data-action="admin-report" data-id="${r.id}">
                      Resolver
                    </button>`}
              </div>`,
          )
          .join("") || empty("Todo en orden", "No hay reportes de la comunidad.");
    if (adminTab === "registro")
      content =
        '<p class="subtle">Últimas 100 acciones registradas por el servidor.</p>' +
        a.audit
          .map(
            (l) =>
              /* HTML */ `<div class="log">
                <strong>${esc(l.name)} · ${esc(l.action)}</strong>
                <div>${esc(l.detail)}</div>
                <small>${esc(l.created)} UTC</small>
              </div>`,
          )
          .join("");
    if (adminTab === "comunidades")
      content = /* HTML */ `<form id="community-form">
        <label>Nombre<input name="name" required minlength="3" maxlength="60" /></label
        ><label>Icono<input name="icon" value="🌟" required maxlength="8" /></label
        ><label
          >Descripción<textarea
            name="description"
            required
            minlength="10"
            maxlength="250"
          ></textarea>
        </label>
        <div class="form-error" role="alert"></div>
        <button class="primary">Crear comunidad</button>
      </form>`;
    $("#main").innerHTML =
      heading("Administración", "Control de la comunidad · Acceso restringido") +
      /* HTML */ `<div class="stats">
          <div class="stat">
            <b>${a.users.filter((u) => u.role !== "admin").length}</b
            ><small>Cuentas de estudiantes</small>
          </div>
          <div class="stat">
            <b>${a.posts.filter((p) => !p.hidden).length}</b><small>Publicaciones visibles</small>
          </div>
          <div class="stat">
            <b>${a.reports.filter((r) => !r.resolved).length}</b><small>Reportes pendientes</small>
          </div>
        </div>
        <div class="tabs admin-tabs">
          ${["usuarios", "contenido", "reportes", "comunidades", "registro"]
            .map(
              (t) =>
                /* HTML */ `<button
                  class="tab ${adminTab === t ? "active" : ""}"
                  data-action="admin-tab"
                  data-value="${t}"
                >
                  ${t[0].toUpperCase() + t.slice(1)}
                </button>`,
            )
            .join("")}
        </div>
        <section class="card">${content}</section>`;
  } catch (e) {
    toast(e.message);
  }
}
document.addEventListener("click", async (event) => {
  const button = event.target.closest("[data-action]");
  if (!button) return;
  const action = button.dataset.action,
    id = +button.dataset.id,
    value = button.dataset.value;
  try {
    if (action === "close") return closeModal();
    if (["login", "register", "setup"].includes(action)) return auth(action);
    if (action === "compose") return compose();
    if (action === "sort") {
      sort = value;
      return render();
    }
    if (action === "community-tab") {
      communityTab = value;
      return render();
    }
    if (action === "admin-tab") {
      adminTab = value;
      return render();
    }
    if (action === "rules")
      return openModal(
        '<h2>Un espacio que cuidamos juntos.</h2><ol class="rules"><li>Respeta a tus compañeros y conversa con empatía.</li><li>Comparte información útil y reconoce sus fuentes.</li><li>No publiques contraseñas, datos privados ni información de otras personas.</li><li>Evita el acoso, el spam y el contenido que dañe a la comunidad.</li><li>Utiliza la bandera para reportar publicaciones. Administración revisará el caso.</li></ol>',
      );
    if (action === "account") {
      if (!state.user) return auth("login");
      return openModal(
        /* HTML */ `<h2>${esc(state.user.name)}</h2>
          <p class="intro">
            @${esc(state.user.username)} ·
            ${state.user.role === "admin" ? "Administrador" : "Estudiante"}
          </p>
          <div class="form-actions">
            <button data-action="go-profile">Ver mi perfil</button
            ><button data-action="go-saved">Guardados</button
            ><button data-action="go-shop">Tienda y avatar</button
            ><button data-action="tour">Guía rápida</button>${state.user.role === "admin"
              ? '<button data-action="go-admin">Administración</button>'
              : ""}<button class="primary" data-action="logout">Cerrar sesión</button>
          </div>`,
      );
    }
    if (["go-profile", "go-saved", "go-admin", "go-shop"].includes(action)) {
      closeModal();
      location.hash = {
        "go-profile": "perfil",
        "go-saved": "guardados",
        "go-admin": "admin",
        "go-shop": "tienda",
      }[action];
      return;
    }
    if (!needUser()) return;
    if (action === "edit-profile")
      return openModal(
        /* HTML */ `<h2>Tu perfil, a tu manera.</h2>
          <p class="intro">
            La edad es privada. Tu nombre, grupo y especialidad acompañan tus publicaciones.
          </p>
          <form id="profile-form">
            ${profileFields(state.user)}<label
              >Sobre mí<textarea name="bio" maxlength="300">${esc(state.user.bio)}</textarea></label
            ><label
              >Avatar<select name="avatar">
                ${["🐯", "🦊", "🐱", "🐼", "👩🏽", "👨🏻", "👩🏻"]
                  .map(
                    (a) =>
                      /* HTML */ `<option ${a === state.user.avatar ? "selected" : ""}>
                        ${a}
                      </option>`,
                  )
                  .join("")}
              </select></label
            >
            <div class="form-error" role="alert"></div>
            <div class="form-actions"><button class="primary">Guardar cambios</button></div>
          </form>`,
      );
    if (action === "mission") {
      if (value === "post") return compose();
      location.hash = value === "join" ? "comunidades" : "inicio";
      if (value === "comment") toast("Abre una conversación y comparte tu respuesta.");
      return;
    }
    if (action === "report")
      return openModal(
        /* HTML */ `<h2>Ayúdanos a cuidar la comunidad</h2>
          <p class="intro">El reporte será revisado por administración.</p>
          <form id="report-form" data-id="${id}">
            <label
              >¿Qué ocurre?<textarea
                name="reason"
                minlength="5"
                maxlength="500"
                required
              ></textarea>
            </label>
            <div class="form-error" role="alert"></div>
            <div class="form-actions"><button class="primary">Enviar reporte</button></div>
          </form>`,
      );
    button.disabled = true;
    const previous = state.user.gold;
    if (action === "logout") {
      await api("logout", {});
      closeModal();
      location.hash = "inicio";
    } else if (["join", "save", "vote"].includes(action)) {
      await api(action, { id, value: +value });
      if (action === "save") toast("Tus guardados se actualizaron.");
    } else if (action.startsWith("admin-")) {
      const kind = action.slice(6);
      await api("admin/" + kind, { id, hidden: +value, suspended: +value });
      toast("Cambio registrado.");
    }
    await refresh();
    if (state.user && state.user.gold > previous)
      toast(`¡Misión completada! +${state.user.gold - previous} oro. Tu XP también creció.`);
  } catch (e) {
    toast(e.message);
  } finally {
    button.disabled = false;
  }
});
document.addEventListener("submit", async (event) => {
  const form = event.target;
  if (!form.id) return;
  event.preventDefault();
  const data = Object.fromEntries(new FormData(form));
  const submit = form.querySelector('button:not([type="button"])');
  const error = form.querySelector(".form-error");
  if (submit) submit.disabled = true;
  try {
    const previous = state.user?.gold || 0;
    if (form.id === "auth-form") {
      await api(form.dataset.mode, data);
      closeModal();
      await refresh();
      toast("¡Bienvenido a Ocelote!");
      if (form.dataset.mode === "setup") location.hash = "admin";
      if (form.dataset.mode === "register" || state.user?.tutorial?.show_on_login !== 0)
        startTour();
      return;
    }
    const endpoints = {
      "post-form": "post",
      "profile-form": "profile",
      "comment-form": "comment",
      "report-form": "report",
      "community-form": "admin/community",
    };
    if (!endpoints[form.id]) return;
    if (form.dataset.id) data.id = +form.dataset.id;
    await api(endpoints[form.id], data);
    closeModal();
    await refresh();
    if (form.id === "post-form") location.hash = "inicio";
    if (form.id === "community-form") form.reset();
    toast(
      state.user.gold > previous
        ? `¡Misión completada! +${state.user.gold - previous} oro y más experiencia.`
        : form.id === "report-form"
          ? "Reporte enviado a administración."
          : "Guardado correctamente.",
    );
  } catch (e) {
    if (error) {
      error.textContent = e.message;
      error.style.display = "block";
    } else toast(e.message);
  } finally {
    if (submit) submit.disabled = false;
  }
});
$("#search").addEventListener("input", (e) => {
  search = e.target.value;
  const page = route()[0];
  if (!["inicio", "comunidades", "comunidad", "guardados"].includes(page)) location.hash = "inicio";
  else render();
});
window.addEventListener("hashchange", () => {
  if (state) render();
  window.scrollTo(0, 0);
});
document.addEventListener("keydown", (e) => {
  if (e.key === "/" && !["INPUT", "TEXTAREA", "SELECT"].includes(document.activeElement.tagName)) {
    e.preventDefault();
    $("#search").focus();
  }
});
refresh()
  .then(() => {
    const preference = state.user?.tutorial;
    if (state.user && preference && !preference.completed && preference.show_on_login) startTour();
  })
  .catch((e) => {
    $("#main").innerHTML = empty(
      "No pudimos conectar",
      esc(e.message),
      '<a class="primary" href="/">Reintentar</a>',
    );
  });
