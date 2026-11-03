let slideIndex = 0;

function mostrarSlides() {
  const slides = document.querySelectorAll(".slide");

  if (slides.length === 0) return;

  slides.forEach(slide => {
    slide.style.display = "none";
  });

  slideIndex++;

  if (slideIndex > slides.length) {
    slideIndex = 1;
  }

  slides[slideIndex - 1].style.display = "block";

  setTimeout(mostrarSlides, 5000);
}

mostrarSlides();

function cerrarPopup() {
  const popup = document.getElementById("popup");
  if (popup) popup.style.display = "none";
}

function mostrarToast(mensaje, tipo = "exito") {
  const toast = document.getElementById("toast");
  if (!toast) return;

  toast.textContent = mensaje;
  toast.classList.remove("exito", "error", "mostrar");
  toast.classList.add(tipo, "mostrar");

  setTimeout(() => {
    toast.classList.remove("mostrar");
  }, 3500);
}

function suscribirse(event) {
  event.preventDefault();

  const form = event.target;
  const inputCorreo = form.querySelector('input[name="correo"]') || document.getElementById("correo");
  const boton = form.querySelector('button[type="submit"]');
  const correo = inputCorreo.value.trim();

  if (!correo) {
    mostrarToast("Ingresa tu correo electrónico.", "error");
    return;
  }

  if (boton) {
    boton.disabled = true;
    boton.textContent = "Enviando...";
  }

  fetch("api/suscriptores.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "correo=" + encodeURIComponent(correo)
  })
    .then(response => response.json())
    .then(data => {
      mostrarToast(data.mensaje || "Solicitud procesada.", data.ok ? "exito" : "error");

      if (data.ok) {
        form.reset();
        if (form.closest("#popup")) cerrarPopup();
      }
    })
    .catch(() => {
      mostrarToast("No se pudo completar la suscripción.", "error");
    })
    .finally(() => {
      if (boton) {
        boton.disabled = false;
        boton.textContent = "Suscribirme";
      }
    });
}

const quantumScriptActual = document.currentScript;
const quantumBaseUrl = quantumScriptActual ? new URL("../", quantumScriptActual.src) : new URL("./", window.location.href);

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".quantum-feature-video, .video-frame").forEach(contenedor => {
    const video = contenedor.querySelector("video");
    const boton = contenedor.querySelector(".video-play-overlay");
    if (!video || !boton) return;

    const prepararVistaPrevia = () => {
      if (video.dataset.vistaPreviaPreparada || !video.paused || !Number.isFinite(video.duration)) return;
      video.dataset.vistaPreviaPreparada = "true";
      contenedor.classList.add("video-cargando-vista-previa");
      const instante = Math.min(1.25, Math.max(0.15, video.duration * 0.04));
      try {
        video.currentTime = instante;
      } catch {
        contenedor.classList.remove("video-cargando-vista-previa");
      }
    };

    const vistaPreviaLista = () => {
      contenedor.classList.remove("video-cargando-vista-previa");
      contenedor.classList.add("video-vista-previa-lista");
    };

    const sincronizarReproduccion = () => {
      contenedor.classList.toggle("video-reproduciendo", !video.paused && !video.ended);
    };

    boton.addEventListener("click", async () => {
      try {
        await video.play();
      } catch {
        video.controls = true;
      }
      sincronizarReproduccion();
    });

    ["play", "playing", "pause", "ended", "loadedmetadata"].forEach(evento => {
      video.addEventListener(evento, sincronizarReproduccion);
    });
    video.addEventListener("loadedmetadata", prepararVistaPrevia, { once: true });
    video.addEventListener("seeked", vistaPreviaLista, { once: true });
    if (video.readyState >= 1) prepararVistaPrevia();
    sincronizarReproduccion();
  });

  const popup = document.getElementById("popup");

  if (popup) {
    const ultimaVista = Number(localStorage.getItem("popupNewsletterVisto") || 0);
    const sieteDias = 7 * 24 * 60 * 60 * 1000;
    const debeMostrar = Date.now() - ultimaVista > sieteDias;

    if (debeMostrar) {
      setTimeout(() => {
        popup.hidden = false;
        popup.style.display = "flex";
        localStorage.setItem("popupNewsletterVisto", String(Date.now()));
      }, 1800);
    } else {
      popup.style.display = "none";
    }
  }

  const menuBtn = document.querySelector(".menu-btn");
  const menuContent = document.querySelector(".menu-content");
  const menu = menuBtn?.closest(".menu");
  const cerrarMenu = () => {
    menuBtn?.setAttribute("aria-expanded", "false");
    menuContent?.classList.remove("abierto");
  };

  menuBtn?.addEventListener("click", () => {
    const abierto = menuBtn.getAttribute("aria-expanded") === "true";
    menuBtn.setAttribute("aria-expanded", String(!abierto));
    menuContent?.classList.toggle("abierto", !abierto);
  });

  document.addEventListener("click", event => {
    if (menu && !menu.contains(event.target)) cerrarMenu();
  });

  window.addEventListener("resize", cerrarMenu);

  document.querySelector("[data-copiar-url]")?.addEventListener("click", async event => {
    try {
      await navigator.clipboard.writeText(event.currentTarget.dataset.copiarUrl);
      event.currentTarget.textContent = "Enlace copiado";
    } catch {
      window.prompt("Copia este enlace:", event.currentTarget.dataset.copiarUrl);
    }
  });

  const modalGaleria = document.getElementById("modalGaleria");
  const imagenGrande = document.getElementById("imagenGrande");
  const tituloGrande = document.getElementById("tituloImagenGrande");
  const contadorGaleria = document.getElementById("contadorGaleria");
  const visorHolografico = modalGaleria?.querySelector(".visor-holografico");
  const elementosGaleria = [...document.querySelectorAll("[data-imagen]")];
  const botonAnteriorGaleria = modalGaleria?.querySelector(".navegar-galeria-anterior");
  const botonSiguienteGaleria = modalGaleria?.querySelector(".navegar-galeria-siguiente");
  let indiceGaleria = 0;
  let galeriaEnTransicion = false;
  let focoAntesDeGaleria = null;

  const actualizarImagenGaleria = indice => {
    const item = elementosGaleria[indice];
    if (!item || !imagenGrande) return;
    const titulo = item.dataset.titulo || "";
    imagenGrande.src = item.dataset.imagen;
    imagenGrande.alt = titulo;
    if (tituloGrande) tituloGrande.textContent = titulo;
    if (contadorGaleria) contadorGaleria.textContent = `${indice + 1} de ${elementosGaleria.length}`;

    [-1, 1].forEach(salto => {
      const vecino = elementosGaleria[(indice + salto + elementosGaleria.length) % elementosGaleria.length];
      if (vecino) new Image().src = vecino.dataset.imagen;
    });
  };

  const moverGaleria = direccion => {
    if (galeriaEnTransicion || elementosGaleria.length < 2 || !visorHolografico) return;
    galeriaEnTransicion = true;
    const claseSalida = direccion > 0 ? "holograma-salida-izquierda" : "holograma-salida-derecha";
    const claseEntrada = direccion > 0 ? "holograma-entrada-derecha" : "holograma-entrada-izquierda";
    visorHolografico.classList.add(claseSalida);

    window.setTimeout(() => {
      indiceGaleria = (indiceGaleria + direccion + elementosGaleria.length) % elementosGaleria.length;
      actualizarImagenGaleria(indiceGaleria);
      visorHolografico.classList.remove(claseSalida);
      visorHolografico.classList.add(claseEntrada);
      window.setTimeout(() => {
        visorHolografico.classList.remove(claseEntrada);
        galeriaEnTransicion = false;
      }, 340);
    }, 190);
  };

  elementosGaleria.forEach((item, indice) => {
    item.addEventListener("click", () => {
      if (!modalGaleria || !imagenGrande) return;
      indiceGaleria = indice;
      focoAntesDeGaleria = document.activeElement;
      actualizarImagenGaleria(indiceGaleria);
      modalGaleria.hidden = false;
      document.body.classList.add("modal-abierto");
      visorHolografico?.classList.remove("holograma-activando");
      if (visorHolografico) void visorHolografico.offsetWidth;
      visorHolografico?.classList.add("holograma-activando");
      window.setTimeout(() => visorHolografico?.classList.remove("holograma-activando"), 720);
      modalGaleria.querySelector(".cerrar-galeria")?.focus();
    });
  });

  const cerrarGaleria = () => {
    if (!modalGaleria || !imagenGrande) return;
    modalGaleria.hidden = true;
    imagenGrande.src = "";
    document.body.classList.remove("modal-abierto");
    galeriaEnTransicion = false;
    visorHolografico?.classList.remove(
      "holograma-salida-izquierda",
      "holograma-salida-derecha",
      "holograma-entrada-izquierda",
      "holograma-entrada-derecha",
      "holograma-activando"
    );
    focoAntesDeGaleria?.focus?.();
  };

  botonAnteriorGaleria?.addEventListener("click", () => moverGaleria(-1));
  botonSiguienteGaleria?.addEventListener("click", () => moverGaleria(1));
  if (elementosGaleria.length < 2) {
    if (botonAnteriorGaleria) botonAnteriorGaleria.hidden = true;
    if (botonSiguienteGaleria) botonSiguienteGaleria.hidden = true;
  }

  modalGaleria?.querySelector(".cerrar-galeria")?.addEventListener("click", cerrarGaleria);
  modalGaleria?.addEventListener("click", event => {
    if (event.target === modalGaleria) cerrarGaleria();
  });

  /* Buscador con sugerencias editoriales */
  document.querySelectorAll("[data-busqueda-sugerencias]").forEach(input => {
    const panel = input.parentElement?.querySelector(".sugerencias-busqueda");
    const endpoint = input.dataset.endpoint;
    if (!panel || !endpoint) return;
    let temporizador;
    let controlador;

    const ocultarSugerencias = () => {
      panel.hidden = true;
      panel.replaceChildren();
    };

    input.addEventListener("input", () => {
      window.clearTimeout(temporizador);
      controlador?.abort();
      const termino = input.value.trim();
      if (termino.length < 2) {
        ocultarSugerencias();
        return;
      }

      temporizador = window.setTimeout(async () => {
        controlador = new AbortController();
        try {
          const endpointUrl = new URL(endpoint, document.baseURI);
          endpointUrl.searchParams.set("q", termino);
          const respuesta = await fetch(endpointUrl, { signal: controlador.signal, headers: { Accept: "application/json" } });
          if (!respuesta.ok) throw new Error("No disponible");
          const datos = await respuesta.json();
          panel.replaceChildren();
          (datos.resultados || []).forEach(item => {
            const enlace = document.createElement("a");
            enlace.href = new URL(item.url, endpointUrl).href;
            enlace.setAttribute("role", "option");
            const titulo = document.createElement("strong");
            titulo.textContent = item.titulo;
            const meta = document.createElement("span");
            meta.textContent = `${item.categoria || "Publicación"} · ${item.fecha || ""}`;
            enlace.append(titulo, meta);
            panel.append(enlace);
          });
          panel.hidden = panel.children.length === 0;
        } catch (error) {
          if (error.name !== "AbortError") ocultarSugerencias();
        }
      }, 220);
    });

    input.addEventListener("keydown", event => {
      if (event.key === "Escape") ocultarSugerencias();
      if (event.key === "ArrowDown" && !panel.hidden) {
        event.preventDefault();
        panel.querySelector("a")?.focus();
      }
    });
    document.addEventListener("click", event => {
      if (!input.parentElement?.contains(event.target)) ocultarSugerencias();
    });
  });

  /* Portada dinámica con pestañas accesibles */
  const tabsQuantum = [...document.querySelectorAll("[data-tab-quantum]")];
  const activarTabQuantum = tab => {
    tabsQuantum.forEach(boton => {
      const activo = boton === tab;
      boton.setAttribute("aria-selected", String(activo));
      boton.tabIndex = activo ? 0 : -1;
      const panel = document.getElementById(boton.getAttribute("aria-controls"));
      if (panel) panel.hidden = !activo;
    });
  };
  tabsQuantum.forEach((tab, indice) => {
    tab.addEventListener("click", () => activarTabQuantum(tab));
    tab.addEventListener("keydown", event => {
      if (!["ArrowLeft", "ArrowRight"].includes(event.key)) return;
      event.preventDefault();
      const siguiente = event.key === "ArrowRight" ? (indice + 1) % tabsQuantum.length : (indice - 1 + tabsQuantum.length) % tabsQuantum.length;
      tabsQuantum[siguiente]?.focus();
      activarTabQuantum(tabsQuantum[siguiente]);
    });
  });

  /* Contadores de impacto */
  const contadores = [...document.querySelectorAll("[data-contador]")];
  const animarContador = elemento => {
    const total = Number(elemento.dataset.contador || 0);
    const inicio = performance.now();
    const duracion = 900;
    const paso = ahora => {
      const progreso = Math.min(1, (ahora - inicio) / duracion);
      const suavizado = 1 - Math.pow(1 - progreso, 3);
      elemento.textContent = Math.round(total * suavizado).toLocaleString("es-MX");
      if (progreso < 1) requestAnimationFrame(paso);
    };
    requestAnimationFrame(paso);
  };
  if (contadores.length) {
    const observadorContadores = new IntersectionObserver(entradas => {
      entradas.forEach(entrada => {
        if (!entrada.isIntersecting || entrada.target.dataset.animado) return;
        entrada.target.dataset.animado = "true";
        animarContador(entrada.target);
        observadorContadores.unobserve(entrada.target);
      });
    }, { threshold: 0.45 });
    contadores.forEach(contador => observadorContadores.observe(contador));
  }

  /* Filtro de línea del tiempo */
  document.querySelectorAll("[data-linea-filtro]").forEach(boton => {
    boton.addEventListener("click", () => {
      const filtro = boton.dataset.lineaFiltro;
      document.querySelectorAll("[data-linea-filtro]").forEach(item => item.classList.toggle("activo", item === boton));
      document.querySelectorAll("[data-linea-anio]").forEach(item => {
        item.hidden = filtro !== "todos" && item.dataset.lineaAnio !== filtro;
      });
    });
  });

  /* Lista local para leer después */
  const CLAVE_GUARDADOS = "quantumLecturasGuardadas";
  const leerGuardados = () => {
    try {
      const datos = JSON.parse(localStorage.getItem(CLAVE_GUARDADOS) || "[]");
      return Array.isArray(datos) ? datos : [];
    } catch {
      return [];
    }
  };
  const escribirGuardados = datos => localStorage.setItem(CLAVE_GUARDADOS, JSON.stringify(datos.slice(0, 80)));
  const urlNormalizada = url => {
    try { return new URL(url, document.baseURI).href; } catch { return url || ""; }
  };
  const metadatosDesdeElemento = elemento => {
    const articuloActual = elemento.closest("[data-noticia-actual]");
    if (articuloActual) {
      return {
        url: urlNormalizada(articuloActual.dataset.url),
        titulo: articuloActual.dataset.titulo || "Publicación",
        imagen: articuloActual.dataset.imagen || "",
        resumen: articuloActual.dataset.resumen || "",
        categoria: articuloActual.dataset.categoria || "Quántum"
      };
    }
    const tarjeta = elemento.closest(".card, .quantum-destacada-card");
    const enlace = tarjeta?.querySelector('a[href*="noticia.php"]');
    return {
      url: urlNormalizada(enlace?.href),
      titulo: tarjeta?.querySelector("h2, h3")?.textContent?.trim() || "Publicación",
      imagen: tarjeta?.querySelector("img")?.src || "",
      resumen: tarjeta?.querySelector("p")?.textContent?.trim() || "",
      categoria: tarjeta?.querySelector(".categoria, .quantum-destacada-content > span")?.textContent?.trim() || "Quántum"
    };
  };
  const estaGuardado = url => leerGuardados().some(item => item.url === urlNormalizada(url));
  const actualizarEstadoGuardados = () => {
    const guardados = leerGuardados();
    document.querySelectorAll("[data-contador-guardados]").forEach(item => item.textContent = String(guardados.length));
    document.querySelectorAll("[data-guardar-noticia]").forEach(boton => {
      const datos = metadatosDesdeElemento(boton);
      const guardado = estaGuardado(datos.url);
      boton.classList.toggle("guardado", guardado);
      boton.setAttribute("aria-pressed", String(guardado));
      boton.textContent = guardado ? "Guardado" : (boton.classList.contains("guardar-noticia-principal") ? "Guardar para después" : "Guardar");
    });
  };
  const alternarGuardado = boton => {
    const datos = metadatosDesdeElemento(boton);
    if (!datos.url) return;
    const guardados = leerGuardados();
    const indice = guardados.findIndex(item => item.url === datos.url);
    if (indice >= 0) guardados.splice(indice, 1);
    else guardados.unshift({ ...datos, fechaGuardado: new Date().toISOString() });
    escribirGuardados(guardados);
    actualizarEstadoGuardados();
    renderizarGuardados();
    mostrarToast(indice >= 0 ? "Publicación retirada de tus lecturas." : "Publicación guardada para después.");
  };

  document.querySelectorAll(".card, .quantum-destacada-card").forEach(tarjeta => {
    if (!tarjeta.querySelector('a[href*="noticia.php"]') || tarjeta.querySelector("[data-guardar-noticia]")) return;
    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = "guardar-noticia-boton guardar-noticia-tarjeta";
    boton.dataset.guardarNoticia = "";
    tarjeta.append(boton);
  });
  document.addEventListener("click", event => {
    const boton = event.target.closest("[data-guardar-noticia]");
    if (boton) alternarGuardado(boton);
  });

  const renderizarGuardados = () => {
    const contenedor = document.querySelector("[data-lista-guardados]");
    if (!contenedor) return;
    const guardados = leerGuardados();
    contenedor.replaceChildren();
    document.querySelector("[data-total-guardados]")?.replaceChildren(document.createTextNode(String(guardados.length)));
    const vacio = document.querySelector("[data-guardados-vacio]");
    if (vacio) vacio.hidden = guardados.length > 0;
    guardados.forEach(item => {
      const articulo = document.createElement("article");
      articulo.className = "card tarjeta-guardada";
      if (item.imagen) {
        const imagen = document.createElement("img");
        imagen.src = item.imagen;
        imagen.alt = "";
        imagen.loading = "lazy";
        articulo.append(imagen);
      }
      const cuerpo = document.createElement("div");
      cuerpo.className = "card-body";
      const categoria = document.createElement("span");
      categoria.className = "categoria";
      categoria.textContent = item.categoria || "Quántum";
      const titulo = document.createElement("h2");
      titulo.textContent = item.titulo;
      const resumen = document.createElement("p");
      resumen.textContent = item.resumen || "Publicación guardada en este dispositivo.";
      const enlace = document.createElement("a");
      enlace.className = "btn";
      enlace.href = item.url;
      enlace.textContent = "Continuar leyendo";
      const quitar = document.createElement("button");
      quitar.type = "button";
      quitar.className = "guardar-noticia-boton guardado";
      quitar.textContent = "Quitar";
      quitar.addEventListener("click", () => {
        escribirGuardados(leerGuardados().filter(guardado => guardado.url !== item.url));
        renderizarGuardados();
        actualizarEstadoGuardados();
      });
      cuerpo.append(categoria, titulo, resumen, enlace, quitar);
      articulo.append(cuerpo);
      contenedor.append(articulo);
    });
  };
  document.querySelector("[data-limpiar-guardados]")?.addEventListener("click", () => {
    escribirGuardados([]);
    renderizarGuardados();
    actualizarEstadoGuardados();
  });
  renderizarGuardados();
  actualizarEstadoGuardados();

  /* Modo y progreso de lectura */
  const articuloLectura = document.querySelector("[data-noticia-actual]");
  const contenidoLectura = document.querySelector("[data-contenido-lectura]");
  const barraProgreso = document.querySelector("[data-progreso-lectura]");
  let escalaLectura = Number(localStorage.getItem("quantumEscalaLectura") || 1);
  const aplicarEscalaLectura = () => {
    escalaLectura = Math.min(1.35, Math.max(0.9, escalaLectura));
    document.documentElement.style.setProperty("--quantum-reading-scale", String(escalaLectura));
    localStorage.setItem("quantumEscalaLectura", String(escalaLectura));
  };
  aplicarEscalaLectura();
  document.querySelector("[data-texto-menos]")?.addEventListener("click", () => { escalaLectura -= 0.1; aplicarEscalaLectura(); });
  document.querySelector("[data-texto-mas]")?.addEventListener("click", () => { escalaLectura += 0.1; aplicarEscalaLectura(); });
  document.querySelector("[data-texto-restablecer]")?.addEventListener("click", () => { escalaLectura = 1; aplicarEscalaLectura(); });
  document.querySelector("[data-modo-lectura]")?.addEventListener("click", event => {
    const activo = document.body.classList.toggle("modo-lectura-activo");
    event.currentTarget.setAttribute("aria-pressed", String(activo));
    event.currentTarget.textContent = activo ? "Salir del modo lectura" : "Modo lectura";
  });
  const actualizarProgreso = () => {
    if (!articuloLectura || !contenidoLectura || !barraProgreso) return;
    const inicio = contenidoLectura.getBoundingClientRect().top + window.scrollY - window.innerHeight * 0.25;
    const fin = contenidoLectura.getBoundingClientRect().bottom + window.scrollY - window.innerHeight;
    const progreso = fin <= inicio ? 0 : Math.min(1, Math.max(0, (window.scrollY - inicio) / (fin - inicio)));
    barraProgreso.style.transform = `scaleX(${progreso})`;
  };
  window.addEventListener("scroll", actualizarProgreso, { passive: true });
  window.addEventListener("resize", actualizarProgreso);
  actualizarProgreso();

  /* Visor multimedia dentro de las noticias */
  const modalMultimedia = document.querySelector("[data-modal-multimedia]");
  const imagenMultimedia = modalMultimedia?.querySelector("[data-multimedia-grande]");
  const captionMultimedia = modalMultimedia?.querySelector("[data-multimedia-caption]");
  const mediosImagen = [...document.querySelectorAll("[data-multimedia-imagen]")];
  let indiceMultimedia = 0;
  const actualizarMultimedia = () => {
    const medio = mediosImagen[indiceMultimedia];
    if (!medio || !imagenMultimedia) return;
    imagenMultimedia.src = medio.dataset.multimediaImagen;
    imagenMultimedia.alt = medio.dataset.multimediaTitulo || "Imagen de la publicación";
    if (captionMultimedia) captionMultimedia.textContent = `${medio.dataset.multimediaTitulo || "Quántum"} · ${indiceMultimedia + 1} de ${mediosImagen.length}`;
  };
  const cerrarMultimedia = () => {
    if (!modalMultimedia) return;
    modalMultimedia.hidden = true;
    document.body.classList.remove("modal-abierto");
  };
  mediosImagen.forEach((medio, indice) => medio.addEventListener("click", () => {
    indiceMultimedia = indice;
    actualizarMultimedia();
    modalMultimedia.hidden = false;
    document.body.classList.add("modal-abierto");
    modalMultimedia.querySelector("[data-cerrar-multimedia]")?.focus();
  }));
  modalMultimedia?.querySelector("[data-cerrar-multimedia]")?.addEventListener("click", cerrarMultimedia);
  modalMultimedia?.querySelector("[data-multimedia-anterior]")?.addEventListener("click", () => { indiceMultimedia = (indiceMultimedia - 1 + mediosImagen.length) % mediosImagen.length; actualizarMultimedia(); });
  modalMultimedia?.querySelector("[data-multimedia-siguiente]")?.addEventListener("click", () => { indiceMultimedia = (indiceMultimedia + 1) % mediosImagen.length; actualizarMultimedia(); });
  modalMultimedia?.addEventListener("click", event => { if (event.target === modalMultimedia) cerrarMultimedia(); });

  /* Aplicación instalable Quántum */
  const botonInstalar = document.querySelector("[data-pwa-instalar]");
  const modalPwa = document.querySelector("[data-pwa-modal]");
  const cerrarPwaBoton = modalPwa?.querySelector("[data-pwa-cerrar]");
  const accionPwa = modalPwa?.querySelector("[data-pwa-accion]");
  const descripcionPwa = modalPwa?.querySelector("[data-pwa-descripcion]");
  const pasosPwa = modalPwa?.querySelector("[data-pwa-pasos]");
  const notaPwa = modalPwa?.querySelector("[data-pwa-nota]");
  const esIos = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
  const estaInstalada = window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
  let eventoInstalacion = null;

  const abrirAyudaPwa = () => {
    if (!modalPwa) return;
    if (esIos) {
      if (descripcionPwa) descripcionPwa.textContent = "Instala Quántum en tu iPhone para abrirla como una aplicación independiente.";
      if (pasosPwa) pasosPwa.innerHTML = "<li>Abre esta página en <strong>Safari</strong>.</li><li>Toca el botón <strong>Compartir</strong>.</li><li>Selecciona <strong>Agregar a pantalla de inicio</strong> y activa <strong>Abrir como app web</strong>.</li>";
    } else {
      if (descripcionPwa) descripcionPwa.textContent = "Tu navegador aún no mostró la instalación automática. Puedes instalar Quántum desde el menú del navegador cuando la opción esté disponible.";
      if (pasosPwa) pasosPwa.innerHTML = "<li>Abre el menú principal del navegador.</li><li>Busca <strong>Instalar aplicación</strong> o <strong>Agregar a pantalla de inicio</strong>.</li><li>Confirma la instalación de Quántum.</li>";
    }
    if (notaPwa) notaPwa.textContent = window.isSecureContext ? "El dispositivo comprobará automáticamente si la aplicación está lista para instalarse." : "Esta demostración local permite revisar el diseño; la instalación completa requiere HTTPS.";
    modalPwa.hidden = false;
    document.body.classList.add("modal-abierto");
    cerrarPwaBoton?.focus();
  };

  const cerrarAyudaPwa = () => {
    if (!modalPwa) return;
    modalPwa.hidden = true;
    document.body.classList.remove("modal-abierto");
    botonInstalar?.focus();
  };

  if (botonInstalar && !estaInstalada) botonInstalar.hidden = false;
  if (botonInstalar && estaInstalada) botonInstalar.hidden = true;

  window.addEventListener("beforeinstallprompt", event => {
    event.preventDefault();
    eventoInstalacion = event;
    if (botonInstalar) {
      botonInstalar.hidden = false;
      botonInstalar.querySelector("strong").textContent = "Instalar Quántum ahora";
    }
  });

  botonInstalar?.addEventListener("click", async () => {
    if (!eventoInstalacion) {
      abrirAyudaPwa();
      return;
    }
    eventoInstalacion.prompt();
    await eventoInstalacion.userChoice;
    eventoInstalacion = null;
  });
  cerrarPwaBoton?.addEventListener("click", cerrarAyudaPwa);
  accionPwa?.addEventListener("click", cerrarAyudaPwa);
  modalPwa?.addEventListener("click", event => { if (event.target === modalPwa) cerrarAyudaPwa(); });
  window.addEventListener("appinstalled", () => {
    eventoInstalacion = null;
    if (botonInstalar) botonInstalar.hidden = true;
    cerrarAyudaPwa();
  });

  if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
      navigator.serviceWorker.register(new URL("sw.js", quantumBaseUrl), { scope: quantumBaseUrl.pathname }).catch(() => {});
    }, { once: true });
  }

  const estadoConexion = document.createElement("div");
  estadoConexion.className = "pwa-estado-conexion";
  estadoConexion.setAttribute("role", "status");
  estadoConexion.setAttribute("aria-live", "polite");
  estadoConexion.hidden = true;
  document.body.append(estadoConexion);
  let estuvoSinConexion = !navigator.onLine;
  const actualizarConexion = () => {
    if (!navigator.onLine) {
      estuvoSinConexion = true;
      estadoConexion.textContent = "Sin conexión · mostrando contenido disponible";
      estadoConexion.classList.add("sin-conexion");
      estadoConexion.hidden = false;
      return;
    }
    if (estuvoSinConexion) {
      estadoConexion.textContent = "Conexión restablecida";
      estadoConexion.classList.remove("sin-conexion");
      estadoConexion.hidden = false;
      estuvoSinConexion = false;
      window.setTimeout(() => { estadoConexion.hidden = true; }, 3200);
    }
  };
  window.addEventListener("online", actualizarConexion);
  window.addEventListener("offline", actualizarConexion);
  actualizarConexion();

  document.addEventListener("keydown", event => {
    if (event.key === "Escape") cerrarMenu();
    if (event.key === "Escape") cerrarGaleria();
    if (modalGaleria && !modalGaleria.hidden && event.key === "ArrowLeft") {
      event.preventDefault();
      moverGaleria(-1);
    }
    if (modalGaleria && !modalGaleria.hidden && event.key === "ArrowRight") {
      event.preventDefault();
      moverGaleria(1);
    }
    if (event.key === "Escape") cerrarMultimedia();
    if (event.key === "Escape") cerrarAyudaPwa();
    if (modalMultimedia && !modalMultimedia.hidden && event.key === "ArrowLeft") {
      event.preventDefault();
      indiceMultimedia = (indiceMultimedia - 1 + mediosImagen.length) % mediosImagen.length;
      actualizarMultimedia();
    }
    if (modalMultimedia && !modalMultimedia.hidden && event.key === "ArrowRight") {
      event.preventDefault();
      indiceMultimedia = (indiceMultimedia + 1) % mediosImagen.length;
      actualizarMultimedia();
    }
  });
});
