document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.querySelector(".admin-menu-toggle");
  const nav = document.querySelector(".admin-nav");

  const paginaActual = window.location.pathname.split("/").pop() || "dashboard.php";
  const gruposNavegacion = {
    "editar_noticia.php": "noticias.php",
    "vista_previa.php": "noticias.php",
    "revisiones.php": "noticias.php"
  };
  const paginaNavegacion = gruposNavegacion[paginaActual] || paginaActual;
  nav?.querySelectorAll("a[href]").forEach(enlace => {
    const destino = new URL(enlace.href, window.location.href).pathname.split("/").pop();
    if (destino === paginaNavegacion) {
      enlace.classList.add("activo");
      enlace.setAttribute("aria-current", "page");
    }
  });

  toggle?.addEventListener("click", () => {
    const abierto = toggle.getAttribute("aria-expanded") === "true";
    toggle.setAttribute("aria-expanded", String(!abierto));
    nav?.classList.toggle("abierto", !abierto);
  });

  const formularioEditor = document.querySelector("[data-editor-form]");
  const editor = document.querySelector(".editor-html");
  if (formularioEditor && editor) {
    const clave = formularioEditor.dataset.autosaveKey || "borrador-editor";
    const estado = document.querySelector("[data-autosave-status]");
    const guardado = localStorage.getItem(clave);
    if (!editor.value.trim() && guardado) {
      try {
        const datos = JSON.parse(guardado);
        formularioEditor.querySelectorAll("[name]").forEach(campo => {
          if (datos[campo.name] !== undefined && campo.type !== "file" && campo.name !== "csrf_token") {
            campo.value = datos[campo.name];
          }
        });
      } catch {}
    }

    setInterval(() => {
      if (window.tinymce) tinymce.triggerSave();
      const datos = {};
      formularioEditor.querySelectorAll("[name]").forEach(campo => {
        if (campo.type !== "file" && campo.name !== "csrf_token") datos[campo.name] = campo.value;
      });
      localStorage.setItem(clave, JSON.stringify(datos));
      if (estado) estado.textContent = "Borrador local guardado " + new Date().toLocaleTimeString();
    }, 5000);

    formularioEditor.addEventListener("submit", event => {
      if (event.submitter?.value !== "preview") localStorage.removeItem(clave);
    });
  }

  document.querySelectorAll("[data-confirmar]").forEach(form => {
    form.addEventListener("submit", event => {
      if (!window.confirm(form.dataset.confirmar)) event.preventDefault();
    });
  });

  document.querySelectorAll("[data-editar-galeria]").forEach(boton => {
    const formulario = document.getElementById(boton.getAttribute("aria-controls"));
    if (!formulario) return;

    const cerrar = () => {
      formulario.hidden = true;
      boton.setAttribute("aria-expanded", "false");
      boton.focus();
    };

    boton.addEventListener("click", () => {
      const abrir = formulario.hidden;
      formulario.hidden = !abrir;
      boton.setAttribute("aria-expanded", String(abrir));
      if (abrir) formulario.querySelector('input[name="titulo"]')?.focus();
    });

    formulario.querySelector("[data-cancelar-edicion-galeria]")?.addEventListener("click", cerrar);
  });
});

if (window.tinymce) {
  tinymce.init({
    selector: ".editor-html",
    license_key: "gpl",
    height: 480,
    menubar: false,
    plugins: "autolink lists link image table preview searchreplace fullscreen wordcount autosave",
    toolbar: "undo redo | blocks | bold italic | bullist numlist | link image table | searchreplace preview fullscreen",
    autosave_interval: "30s",
    autosave_retention: "60m",
    content_style: "body{font-family:Arial,sans-serif;font-size:16px;line-height:1.7}",
    promotion: false,
    branding: false
  });
}
