/* Experiencia de bienvenida, misiones y tienda.
 * Los saldos, requisitos e inventario siempre proceden del servidor.
 */
let shopFilter = "all";
let tourStep = 0;
let tourShowAgain = true;
let dayResetTimer;

const TOUR_STEPS = [
  {
    eyebrow: "TU LUGAR EN EL CETis",
    title: "Una comunidad. Muchas formas de participar.",
    description:
      "Encuentra personas con tus mismos intereses, comparte ideas y construye tu propio camino.",
    icon: "🐾",
    kind: "welcome",
    route: "inicio",
    action: "Explorar el inicio",
  },
  {
    eyebrow: "01 · ENCUENTRA A TU EQUIPO",
    title: "Sigue una comunidad",
    description:
      "En Comunidades, elige un tema y pulsa «Unirme». Sus publicaciones tendrán prioridad en Para ti y podrás publicar ahí.",
    icon: "♧",
    kind: "join",
    route: "comunidades",
    action: "Explorar comunidades",
  },
  {
    eyebrow: "02 · COMPARTE TU VOZ",
    title: "Crea tu primera publicación",
    description:
      "Pulsa +, selecciona una comunidad que sigues y escribe tu título e idea. También puedes marcarla como pregunta académica.",
    icon: "✎",
    kind: "post",
    route: "inicio",
    action: "Crear una publicación",
  },
  {
    eyebrow: "03 · APRENDER ES MEJOR EN EQUIPO",
    title: "Responde, vota y guarda",
    description:
      "Abre una conversación para comentar. Vota los aportes que te sirven, guarda tus favoritos y usa la bandera para reportar problemas.",
    icon: "☏",
    kind: "comment",
    route: "inicio",
    action: "Ver conversaciones",
  },
  {
    eyebrow: "04 · CADA APORTE CUENTA",
    title: "Gana oro. Hazlo tuyo.",
    description:
      "Las misiones entregan oro y experiencia automáticamente. Usa el oro en la tienda: algunos diseños también requieren nivel o una racha alcanzada.",
    icon: "✦",
    kind: "reward",
    route: "misiones",
    action: "Ver mis misiones",
  },
];

const SLOT_LABELS = {
  avatar: "Avatares",
  hair: "Peinados",
  hat: "Sombreros",
  outfit: "Ropa",
  accessory: "Accesorios",
  object: "Objetos",
};

// Recortes vectoriales del atlas: conservan el arte y la transparencia originales.
function spriteMarkup(item, className = "") {
  if (!item?.asset) return "";
  if (!item.rect)
    return `<img class="${className}" src="${esc(item.asset)}" alt="${esc(item.name)}" />`;
  const [x, y, w, h, sw, sh] = item.rect;
  return `<svg class="${className}" preserveAspectRatio="${className === "layer-sprite" ? "none" : "xMidYMid meet"}" viewBox="${x} ${y} ${w} ${h}" role="img" aria-label="${esc(item.name)}"><svg x="${x}" y="${y}" width="${w}" height="${h}" viewBox="${x} ${y} ${w} ${h}" overflow="hidden"><image href="${esc(item.asset)}" width="${sw}" height="${sh}" preserveAspectRatio="none" /></svg></svg>`;
}

function avatarMarkup(user, extraClass = "") {
  const selected = user.appearance || {};
  const appearance = { ...(selected.avatar?.preset || {}), ...selected };
  const base = { asset: "/assets/shop/ocelote-base.png", name: "Tu ocelote" };
  const layers = ["outfit", "hair", "hat", "accessory", "object"]
    .map((slot) => {
      const item = appearance[slot];
      if (!item) return "";
      const [x, y, w, h] = item.placement || [0, 0, 100, 100];
      return `<span class="character-layer layer-${slot}${item.id === "hat_11" ? " layer-behind" : ""}" style="left:${x}%;top:${y}%;width:${w}%;height:${h}%">${spriteMarkup(item, "layer-sprite")}</span>`;
    })
    .join("");
  return `<span class="avatar avatar-composed ${extraClass}"><span class="character-stage">${spriteMarkup(base, "character-base")}${layers}</span></span>`;
}

function startTour() {
  if (!needUser()) return;
  tourStep = 0;
  tourShowAgain = state.user.tutorial?.show_on_login !== 0;
  renderTour();
}

function tourDemo(step) {
  if (step.kind === "join")
    return /* HTML */ `
      <div class="demo-community">
        <span>⚽</span><b>Deportes</b><span class="demo-join">＋ Unirme</span>
      </div>
      <div class="demo-community"><span>🎨</span><b>Arte y cultura</b><span>→</span></div>
      <span class="demo-cursor" aria-hidden="true">↖</span>
    `;
  if (step.kind === "post")
    return /* HTML */ `
      <div class="demo-editor">
        <small>📖 Dudas académicas</small><b>Una idea para aprender juntos</b>
        <div class="demo-lines"><i></i><i></i></div>
        <span class="demo-submit">Publicar →</span>
      </div>
    `;
  if (step.kind === "comment")
    return /* HTML */ `
      <div class="demo-bubble">¿Compartimos nuestros apuntes?</div>
      <div class="demo-bubble answer">¡Sí! Podemos repasarlos juntos. ✨</div>
      <div class="demo-reactions">
        <span>↑ 12</span><span>▢ Responder</span><span>♧ Guardar</span>
      </div>
    `;
  if (step.kind === "reward")
    return /* HTML */ `
      <div class="demo-coin">◈</div>
      <strong class="demo-earned">¡Misión completada!</strong>
      <div class="demo-rewards"><span>+30 oro</span><span>+30 XP</span></div>
    `;
  return /* HTML */ `<div class="tour-mascot">🐯</div>
    <span class="orbit orbit-one">✦</span><span class="orbit orbit-two">📖</span
    ><span class="orbit orbit-three">♧</span>
    <p class="demo-welcome">Tu próxima aventura empieza aquí.</p>`;
}

function renderTour() {
  const step = TOUR_STEPS[tourStep];
  openModal(/* HTML */ `
    <section class="tour-slide" aria-labelledby="tour-title">
      <div class="tour-progress" aria-label="Paso ${tourStep + 1} de ${TOUR_STEPS.length}">
        ${TOUR_STEPS.map(
          (_, i) => /* HTML */ `<span class="${i <= tourStep ? "current" : ""}"></span>`,
        ).join("")}
      </div>
      <div class="tour-demo ${step.kind}" aria-hidden="true">${tourDemo(step)}</div>
      <div class="eyebrow">${step.eyebrow}</div>
      <h2 id="tour-title">${step.title}</h2>
      <p class="tour-description">${step.description}</p>
      <button class="text-button tour-try" data-action="tour-try">${step.action} ↗</button>
      <div class="tour-controls">
        <button data-action="tour-back" ${tourStep === 0 ? "disabled" : ""}>← Atrás</button>
        <span>${tourStep + 1} / ${TOUR_STEPS.length}</span>
        <button class="primary" data-action="tour-next">
          ${tourStep === TOUR_STEPS.length - 1 ? "¡Vamos allá!" : "Siguiente →"}
        </button>
      </div>
      <label class="tour-preference"
        ><input type="checkbox" id="tour-again" ${tourShowAgain ? "checked" : ""} /> Mostrar la guía
        cuando inicie sesión</label
      >
      <button class="text-button tour-skip" data-action="tour-skip">Saltar por ahora</button>
      <p class="subtle">Puedes volver a abrirla desde «Guía rápida».</p>
    </section>
  `);
  $("#dialog").classList.add("tour-dialog");
  $("#dialog").setAttribute("aria-label", "Guía introductoria de Ocelote");
  $("#dialog-content").querySelector('[data-action="tour-next"]').focus();
}

async function finishTour(completed, goTo = null) {
  await api("tutorial", { show_on_login: tourShowAgain, completed });
  closeModal();
  await refresh();
  if (goTo) location.hash = goTo;
}

function renderMissions() {
  const user = state.user;
  const progress = user.mission_progress;
  const count = progress.daily.filter((m) => m.completed).length;
  $("#main").innerHTML = /* HTML */ `
    ${heading(
      "Un poco cada día. Mucho por descubrir.",
      "Misiones que premian tus aportes, no el tiempo frente a la pantalla.",
    )}
    <section class="journey-banner">
      <div>
        <div class="eyebrow">TU AVENTURA DE HOY</div>
        <h2>${count} de 3 misiones completadas</h2>
        <p>Hasta 100 de oro al completar el día.</p>
      </div>
      <div class="journey-ring" style="--progress:${(count / 3) * 100}%">
        <b>${progress.earned_today}<small>oro ganado hoy</small></b>
      </div>
    </section>
    <div class="mission-section-heading">
      <h2>Misiones diarias</h2>
      <span>Se renuevan a las 00:00 · CDMX</span>
    </div>
    ${progress.daily
      .map(
        (m) => /* HTML */ `
          <article
            class="mission-large mission-progress-card ${m.completed ? "mission-completed" : ""}"
          >
            <span class="check ${m.completed ? "done" : ""}">${m.completed ? "✓" : ""}</span>
            <div class="mission-copy">
              <h3>${m.title}</h3>
              <p>${m.detail}</p>
              <div class="mission-meter">
                <div class="progress">
                  <i style="width:${(m.progress / m.goal) * 100}%"></i>
                </div>
                <small>${m.progress} / ${m.goal}</small>
              </div>
            </div>
            <div class="mission-reward"><b>◈ ${m.gold}</b><small>+${m.xp} XP</small></div>
            ${m.completed
              ? '<span class="status-complete">Completada</span>'
              : /* HTML */ `<button class="primary" data-action="mission" data-value="${m.action}">
                  Comenzar →
                </button>`}
          </article>
        `,
      )
      .join("")}
    <section class="daily-bonus ${progress.daily_bonus.completed ? "bonus-completed" : ""}">
      <span>🎁</span>
      <div>
        <strong>El extra de un buen día</strong>
        <p>Completa las tres misiones para recibir +20 oro y +10 XP.</p>
      </div>
      <b>${progress.daily_bonus.completed ? "✓ Recibido" : `${count} / 3`}</b>
    </section>
    <div class="mission-section-heading">
      <h2>Tu siguiente paso</h2>
      <a href="#tienda">Visitar la tienda →</a>
    </div>
    <div class="goal-grid">
      <section class="card weekly-card">
        <div class="eyebrow">MISIÓN SEMANAL · LUNES A DOMINGO</div>
        <h3>Presente en tu comunidad</h3>
        <p>
          Realiza un aporte que cuente para misiones en 4 días distintos. No tienen que ser
          consecutivos.
        </p>
        <div class="week-dots">
          ${[1, 2, 3, 4]
            .map(
              (n) =>
                /* HTML */ `<span class="${n <= progress.weekly.progress ? "done" : ""}"
                  >${n <= progress.weekly.progress ? "✓" : n}</span
                >`,
            )
            .join("")}
        </div>
        <div class="goal-footer">
          <b>◈ 120 oro <small>+60 XP</small></b
          ><span
            >${progress.weekly.completed
              ? "✓ Recibida"
              : `${progress.weekly.progress} / 4 días`}</span
          >
        </div>
      </section>
      <section class="card">
        <div class="eyebrow">PRIMER PASO · UNA SOLA VEZ</div>
        <h3>Encuentra tu lugar</h3>
        <p>Únete a tu primera comunidad. Una pequeña bienvenida para empezar tu colección.</p>
        <div class="goal-footer">
          <b>◈ 25 oro <small>+20 XP</small></b
          >${progress.explore.completed
            ? "<span>✓ Completada</span>"
            : '<a class="text-button" href="#comunidades">Explorar →</a>'}
        </div>
      </section>
    </div>
    <section class="notice">
      Una respuesta cuenta una vez por conversación al día. Las publicaciones repetidas no suman
      progreso adicional. Los comentarios propios, los votos y entrar/salir de comunidades no
      generan oro diario.
    </section>
    <section class="card">
      <h3>La constancia abre nuevas posibilidades</h3>
      <p class="muted">
        Cada 100 XP subes un nivel. Los requisitos de racha usan tu mejor racha alcanzada: descansar
        no te quita lo que ya desbloqueaste.
      </p>
      <div class="goal-footer">
        <span>🔥 Mejor racha: ${user.best_streak} días</span
        ><a href="#tienda" class="text-button">Descubre la colección →</a>
      </div>
    </section>
  `;
}

function itemArtwork(item) {
  return item.slot === "avatar"
    ? avatarMarkup({ appearance: { avatar: item } }, "shop-avatar")
    : spriteMarkup(item, "shop-art");
}

function renderShop() {
  const user = state.user;
  const items = user.shop.filter(
    (item) =>
      shopFilter === "all" || (shopFilter === "owned" ? item.owned : item.slot === shopFilter),
  );
  const owned = user.shop.filter((item) => item.owned);
  const equipped = owned.filter((item) => item.equipped);
  const pending = user.shop.some((item) => !item.available);
  $("#main").innerHTML = /* HTML */ `
    ${heading(
      "Tu estilo. Tu aventura.",
      "Personaliza tu perfil con lo que consigues participando.",
    )}
    <section class="wardrobe-banner">
      <div class="wardrobe-preview">${avatarMarkup(user, "large-avatar")}</div>
      <div class="wardrobe-copy">
        <div class="eyebrow">TU VESTIDOR</div>
        <h2>${esc(user.name)}</h2>
        <p>
          ${equipped.length
            ? equipped.map((item) => esc(item.name)).join(" · ")
            : "Tu apariencia original"}
        </p>
        <p class="wardrobe-hint">
          Tu ocelote puede combinar peinados, sombreros, ropa, accesorios y objetos.
          Tus compras y desbloqueos anteriores se conservan.
        </p>
        <small>Nivel ${user.level} · Mejor racha: ${user.best_streak} días</small>
        ${equipped.length
          ? /* HTML */ `<div class="equipment-actions">
              ${equipped
                .map(
                  (item) =>
                    /* HTML */ `<button data-action="unequip" data-slot="${item.slot}">
                      Quitar ${esc(item.name)}
                    </button>`,
                )
                .join("")}
            </div>`
          : ""}
      </div>
      <div class="shop-balance">
        <small>TU ORO</small><b>◈ ${user.gold}</b><a href="#misiones">Ganar más →</a>
      </div>
    </section>
    ${pending
      ? '<div class="notice collection-notice"><strong>Una nueva colección está en camino.</strong><br>Las tarjetas sin ilustración son una vista previa de precios y requisitos. No se cobran ni se pueden comprar hasta que el diseño esté disponible.</div>'
      : ""}
    <div class="tabs shop-tabs">
      ${[
        ["all", "Toda la colección"],
        ["avatar", "Avatares"],
        ["hair", "Peinados"],
        ["hat", "Sombreros"],
        ["outfit", "Ropa"],
        ["accessory", "Accesorios"],
        ["object", "Objetos"],
        ["owned", `Mi inventario (${owned.length})`],
      ]
        .map(
          ([value, label]) =>
            /* HTML */ `<button
              class="tab ${shopFilter === value ? "active" : ""}"
              data-action="shop-filter"
              data-value="${value}"
            >
              ${label}
            </button>`,
        )
        .join("")}
    </div>
    <div class="shop-grid">
      ${items
        .map(
          (item) => /* HTML */ `
            <article class="shop-card ${item.equipped ? "is-equipped" : ""}">
              <div class="shop-art-wrap rarity-${item.rarity.toLowerCase()}">
                <span class="rarity-tag">${item.rarity}</span>${itemArtwork(item)}${item.owned
                  ? '<span class="owned-tag">✓ En tu colección</span>'
                  : ""}
              </div>
              <div class="shop-card-body">
                <small class="shop-category">${SLOT_LABELS[item.slot]}</small>
                <h3>${esc(item.name)}</h3>
                <div class="item-requirements">
                  ${item.level > 1
                    ? /* HTML */ `<span class="${user.level >= item.level ? "met" : ""}"
                        >${user.level >= item.level ? "✓" : "◇"} Nivel ${item.level}
                        <small>(${Math.min(user.level, item.level)}/${item.level})</small></span
                      >`
                    : ""}${item.streak > 0
                    ? /* HTML */ `<span class="${user.best_streak >= item.streak ? "met" : ""}"
                        >${user.best_streak >= item.streak ? "✓" : "◇"} Racha de ${item.streak} días
                        <small
                          >(${Math.min(user.best_streak, item.streak)}/${item.streak})</small
                        ></span
                      >`
                    : ""}${item.level === 1 && !item.streak
                    ? '<span class="met">Disponible desde nivel 1</span>'
                    : ""}
                </div>
                <div class="item-price"><b>◈ ${item.price}</b><small>oro</small></div>
                ${item.equipped
                  ? /* HTML */ `<button
                      class="equipped-button"
                      data-action="unequip"
                      data-slot="${item.slot}"
                    >
                      ✓ Equipado · quitar
                    </button>`
                  : item.owned
                    ? /* HTML */ `<button
                        class="primary"
                        data-action="equip"
                        data-id="${item.id}"
                        data-slot="${item.slot}"
                      >
                        Equipar
                      </button>`
                    : !item.available
                      ? "<button disabled>Diseño en preparación</button>"
                      : !item.unlocked
                        ? "<button disabled>Alcanza el requisito</button>"
                        : !item.affordable
                          ? /* HTML */ `<a href="#misiones" class="earn-link"
                              >Te faltan ${item.price - user.gold} de oro →</a
                            >`
                          : /* HTML */ `<button
                              class="primary"
                              data-action="buy"
                              data-id="${item.id}"
                            >
                              ${item.price ? `Comprar por ${item.price} oro` : "Obtener gratis"}
                            </button>`}
              </div>
            </article>
          `,
        )
        .join("")}
    </div>
    ${!items.length
      ? empty("Tu colección empieza aquí", "Completa misiones y elige el diseño que más te guste.")
      : ""}
    <section class="card shop-explainer">
      <h3>Un ritmo para disfrutar el camino</h3>
      <p>
        Las piezas cuestan entre 80 y 450 de oro: aproximadamente 1–5 días de misiones completas. La
        corona cuesta 900 y requiere nivel 10 y una racha de 14 días. Es una estimación de ahorro:
        los requisitos de nivel y racha se cumplen por separado.
      </p>
      <small
        >Las compras son permanentes, usan solo oro del foro y no afectan tus niveles ni tus
        insignias.</small
      >
    </section>
  `;
}

function confirmPurchase(itemId) {
  const item = state.user.shop.find((item) => item.id === itemId);
  if (!item) return;
  openModal(/* HTML */ `
    <h2>Un nuevo toque para tu perfil</h2>
    <div class="purchase-preview">${itemArtwork(item)}</div>
    <h3>${esc(item.name)}</h3>
    <p class="muted">Se añadirá a tu inventario para que lo equipes cuando quieras.</p>
    <div class="purchase-cost"><span>Precio</span><b>◈ ${item.price} oro</b></div>
    <div class="purchase-cost">
      <span>Saldo después de comprar</span><b>◈ ${state.user.gold - item.price} oro</b>
    </div>
    <div class="form-actions">
      <button data-action="close">Volver</button
      ><button class="primary" data-action="confirm-buy" data-id="${item.id}">
        Confirmar compra
      </button>
    </div>
    <p id="purchase-error" role="alert"></p>
  `);
}

function scheduleDayRefresh() {
  clearTimeout(dayResetTimer);
  if (!state.user) return;
  const delay = Math.max(
    1000,
    new Date(state.user.mission_progress.next_reset).getTime() - Date.now() + 1000,
  );
  dayResetTimer = setTimeout(
    () => refresh().catch((error) => toast(error.message)),
    Math.min(delay, 2147483647),
  );
}

// Separa los eventos de estas funciones de los flujos originales del foro.
document.addEventListener("click", async (event) => {
  const button = event.target.closest("[data-action]");
  if (!button) return;
  const action = button.dataset.action;
  if (
    ![
      "tour",
      "tour-next",
      "tour-back",
      "tour-try",
      "tour-skip",
      "shop-filter",
      "buy",
      "confirm-buy",
      "equip",
      "unequip",
    ].includes(action)
  )
    return;
  event.stopImmediatePropagation();
  try {
    if (action === "tour") return startTour();
    if (action.startsWith("tour-")) {
      tourShowAgain = Boolean($("#tour-again")?.checked);
      if (action === "tour-back") {
        tourStep = Math.max(0, tourStep - 1);
        return renderTour();
      }
      if (action === "tour-next" && tourStep < TOUR_STEPS.length - 1) {
        tourStep++;
        return renderTour();
      }
      button.disabled = true;
      if (action === "tour-try") {
        const step = TOUR_STEPS[tourStep];
        await finishTour(false, step.route);
        if (step.kind === "post") compose();
        return;
      }
      return await finishTour(action === "tour-next");
    }
    if (!needUser()) return;
    if (action === "shop-filter") {
      shopFilter = button.dataset.value;
      return renderShop();
    }
    if (action === "buy") return confirmPurchase(button.dataset.id);
    button.disabled = true;
    if (action === "confirm-buy") {
      await api("shop/buy", { item_id: button.dataset.id });
      closeModal();
      await refresh();
      toast("¡Ya es tuyo! Equípalo desde tu inventario.");
    } else {
      await api("shop/equip", {
        slot: button.dataset.slot,
        item_id: action === "unequip" ? "" : button.dataset.id,
      });
      await refresh();
      toast("Tu apariencia se actualizó.");
    }
  } catch (error) {
    if ($("#purchase-error")) $("#purchase-error").textContent = error.message;
    else toast(error.message);
  } finally {
    button.disabled = false;
  }
});
