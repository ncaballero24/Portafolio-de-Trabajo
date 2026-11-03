(() => {
  const contenedorMapa = document.getElementById("mapaMaritimo");

  if (!contenedorMapa || window.mapaNavalInicializado) return;
  window.mapaNavalInicializado = true;

  const mapa = L.map("mapaMaritimo", {
    zoomControl: false,
    preferCanvas: true,
    minZoom: 4,
    maxZoom: 10
  }).setView([23.6345, -102.5528], 5);

  let temporizadorRedimension = null;
  const ajustarMapaAlContenedor = () => {
    window.clearTimeout(temporizadorRedimension);
    temporizadorRedimension = window.setTimeout(() => {
      mapa.invalidateSize({ pan: false });
    }, 120);
  };

  window.addEventListener("resize", ajustarMapaAlContenedor);

  if ("ResizeObserver" in window) {
    const observadorMapa = new ResizeObserver(ajustarMapaAlContenedor);
    observadorMapa.observe(contenedorMapa);
  }

  L.tileLayer(
    "https://server.arcgisonline.com/ArcGIS/rest/services/Ocean/World_Ocean_Base/MapServer/tile/{z}/{y}/{x}",
    {
      attribution: "Tiles &copy; Esri, GEBCO, NOAA, Garmin, NaturalVue"
    }
  ).addTo(mapa);

  const infoRegiones = {
    "Primera Región Naval": {
      numero: "1ª",
      sede: "Veracruz, Veracruz",
      jurisdiccion: "Golfo de México, zona centro-sur",
      estados: "Tamaulipas, Veracruz y Tabasco",
      cobertura: "Operaciones navales, vigilancia marítima, búsqueda y rescate, protección portuaria y respuesta ante emergencias.",
      sectores: ["Sector Naval de Tuxpan", "Sector Naval de Coatzacoalcos", "Instalaciones estratégicas del Golfo"],
      color: "#14b8a6",
      coords: [19.1738, -96.1342]
    },
    "Segunda Región Naval": {
      numero: "2ª",
      sede: "Ensenada, Baja California",
      jurisdiccion: "Pacífico norte y litoral de Baja California",
      estados: "Baja California y Baja California Sur",
      cobertura: "Control marítimo del Pacífico norte, vigilancia costera, apoyo interinstitucional y protección de rutas marítimas.",
      sectores: ["Sector Naval de San Felipe", "Sector Naval de Santa Rosalía", "Área logística de Ensenada"],
      color: "#3b82f6",
      coords: [31.8667, -116.5964]
    },
    "Tercera Región Naval": {
      numero: "3ª",
      sede: "Lerma, Campeche",
      jurisdiccion: "Golfo de México y Península de Yucatán",
      estados: "Campeche y Yucatán",
      cobertura: "Protección marítima del sureste, vigilancia energética, control de costas y coordinación operativa regional.",
      sectores: ["Sector Naval de Champotón", "Sector Naval de Progreso", "Instalaciones de apoyo peninsular"],
      color: "#fb923c",
      coords: [19.8046, -90.5944]
    },
    "Cuarta Región Naval": {
      numero: "4ª",
      sede: "Guaymas, Sonora",
      jurisdiccion: "Mar de Cortés y litoral de Sonora",
      estados: "Sonora",
      cobertura: "Vigilancia del Mar de Cortés, seguridad ribereña, protección pesquera y apoyo a comunidades costeras.",
      sectores: ["Sector Naval de Puerto Peñasco", "Sector Naval de Topolobampo", "Área operativa del Mar de Cortés"],
      color: "#22c55e",
      coords: [27.9181, -110.9089]
    },
    "Quinta Región Naval": {
      numero: "5ª",
      sede: "Isla Mujeres, Quintana Roo",
      jurisdiccion: "Mar Caribe mexicano",
      estados: "Quintana Roo",
      cobertura: "Seguridad marítima del Caribe, protección turística, búsqueda y rescate, y vigilancia de áreas insulares.",
      sectores: ["Sector Naval de Cozumel", "Sector Naval de Chetumal", "Área operativa del Caribe"],
      color: "#2dd4bf",
      coords: [21.2322, -86.7341]
    },
    "Sexta Región Naval": {
      numero: "6ª",
      sede: "Manzanillo, Colima",
      jurisdiccion: "Pacífico centro mexicano",
      estados: "Jalisco, Colima y Michoacán",
      cobertura: "Protección portuaria, control del tráfico marítimo, vigilancia del Pacífico centro y apoyo logístico naval.",
      sectores: ["Sector Naval de Puerto Vallarta", "Sector Naval de Lázaro Cárdenas", "Instalaciones portuarias de Manzanillo"],
      color: "#8b5cf6",
      coords: [19.1138, -104.3385]
    },
    "Séptima Región Naval": {
      numero: "7ª",
      sede: "Acapulco, Guerrero",
      jurisdiccion: "Pacífico sur mexicano",
      estados: "Guerrero, Oaxaca y Chiapas",
      cobertura: "Vigilancia del Pacífico sur, operaciones de auxilio, control costero y presencia naval estratégica.",
      sectores: ["Sector Naval de Huatulco", "Sector Naval de Puerto Chiapas", "Base de apoyo del Pacífico sur"],
      color: "#ef4444",
      coords: [16.8531, -99.8237]
    },
    "Octava Región Naval": {
      numero: "8ª",
      sede: "Mazatlán, Sinaloa",
      jurisdiccion: "Pacífico noroeste y litoral de Sinaloa",
      estados: "Sinaloa y Nayarit",
      cobertura: "Seguridad marítima del Pacífico noroeste, protección pesquera, vigilancia costera y apoyo de reacción regional.",
      sectores: ["Sector Naval de San Blas", "Sector Naval de Topolobampo", "Área logística de Mazatlán"],
      color: "#facc15",
      coords: [23.2494, -106.4111]
    }
  };

  let capaRegiones;
  let regionActiva = null;

  const capaSedes = L.layerGroup().addTo(mapa);

  function obtenerNombreRegion(feature) {
    return feature.properties.nombre || feature.properties.region || feature.properties.name || feature.properties.NOMBRE;
  }

  function estiloRegion(feature) {
    const info = infoRegiones[obtenerNombreRegion(feature)];

    return {
      color: "rgba(255,255,255,0.88)",
      weight: 1.7,
      opacity: 0.9,
      fillColor: info ? info.color : "#64748b",
      fillOpacity: 0.34,
      className: "region-naval-shape"
    };
  }

  function estiloRegionAtenuada(feature) {
    const info = infoRegiones[obtenerNombreRegion(feature)];

    return {
      color: "rgba(203,213,225,0.45)",
      weight: 1,
      opacity: 0.55,
      fillColor: info ? info.color : "#64748b",
      fillOpacity: 0.12
    };
  }

  function actualizarPanel(nombreRegion) {
    const info = infoRegiones[nombreRegion];
    const panel = document.getElementById("panelOperacionNaval");

    if (!panel || !info) return;

    panel.classList.add("region-seleccionada");
    document.getElementById("estadoOperacionNaval").textContent = "Región seleccionada";
    document.getElementById("regionTituloNaval").textContent = nombreRegion;
    document.getElementById("regionNumeroNaval").textContent = info.numero;
    document.getElementById("regionSedeNaval").textContent = info.sede;
    document.getElementById("regionJurisdiccionNaval").textContent = info.jurisdiccion;
    document.getElementById("regionEstadosNaval").textContent = info.estados;
    document.getElementById("regionCoberturaNaval").textContent = info.cobertura;

    const sectores = document.getElementById("regionSectoresNaval");
    sectores.innerHTML = info.sectores.map(sector => `<li>${sector}</li>`).join("");
  }

  function mostrarPanelInicial() {
    const panel = document.getElementById("panelOperacionNaval");
    if (!panel) return;

    panel.classList.remove("region-seleccionada");
    document.getElementById("estadoOperacionNaval").textContent = "Sin selección";
    document.getElementById("regionTituloNaval").textContent = "Centro de Operaciones Marítimas";
    document.getElementById("regionNumeroNaval").textContent = "--";
    document.getElementById("regionSedeNaval").textContent = "Selecciona una región naval en el mapa.";
    document.getElementById("regionJurisdiccionNaval").textContent = "El visor mostrará jurisdicción, estados, cobertura y sedes.";
    document.getElementById("regionEstadosNaval").textContent = "Cobertura nacional por regiones.";
    document.getElementById("regionCoberturaNaval").textContent = "Activa o desactiva capas desde el control del mapa. Por ahora funcionan Regiones Navales y Sedes Navales.";
    document.getElementById("regionSectoresNaval").innerHTML = "<li>Sectores navales por integrar</li><li>Bases aeronavales por integrar</li><li>Rutas marítimas por integrar</li>";
  }

  function seleccionarRegion(layer, nombreRegion) {
    regionActiva = nombreRegion;

    capaRegiones.eachLayer(regionLayer => {
      const nombre = obtenerNombreRegion(regionLayer.feature);
      const info = infoRegiones[nombre];

      if (nombre === nombreRegion) {
        regionLayer.setStyle({
          color: "#facc15",
          weight: 3.2,
          opacity: 1,
          fillColor: info ? info.color : "#94a3b8",
          fillOpacity: 0.64
        });
        regionLayer.bringToFront();
      } else {
        regionLayer.setStyle(estiloRegionAtenuada(regionLayer.feature));
      }
    });

    actualizarPanel(nombreRegion);

    mapa.fitBounds(layer.getBounds(), {
      paddingTopLeft: [48, 48],
      paddingBottomRight: [48, 48],
      maxZoom: 7
    });
  }

  function crearIconoSede(info) {
    return L.divIcon({
      className: "sede-naval-marker",
      html: `
        <div class="sede-naval-pin" style="--region-color:${info.color}">
          <span>⚓</span>
        </div>
      `,
      iconSize: [42, 42],
      iconAnchor: [21, 21],
      popupAnchor: [0, -22]
    });
  }

  function crearPopupSede(nombreRegion, info) {
    return `
      <div class="popup-naval popup-sede-naval">
        <span class="popup-etiqueta">Sede Naval</span>
        <h3>${info.numero} ${nombreRegion}</h3>
        <p><strong>Sede:</strong> ${info.sede}</p>
        <p><strong>Jurisdicción:</strong> ${info.jurisdiccion}</p>
        <p><strong>Estados:</strong> ${info.estados}</p>
      </div>
    `;
  }

  function cargarSedesNavales() {
    capaSedes.clearLayers();

    Object.entries(infoRegiones).forEach(([nombreRegion, info]) => {
      const marker = L.marker(info.coords, { icon: crearIconoSede(info), title: `${info.numero} ${nombreRegion}` })
        .bindPopup(crearPopupSede(nombreRegion, info))
        .on("click", () => {
          const regionLayer = capaRegiones && encontrarLayerRegion(nombreRegion);
          if (regionLayer) seleccionarRegion(regionLayer, nombreRegion);
          else actualizarPanel(nombreRegion);
        });

      marker.addTo(capaSedes);
    });
  }

  function quitarGeometriasPuntuales(geometry) {
    if (!geometry || geometry.type === "Point" || geometry.type === "MultiPoint") {
      return null;
    }

    if (geometry.type === "GeometryCollection") {
      return {
        ...geometry,
        geometries: geometry.geometries
          .map(quitarGeometriasPuntuales)
          .filter(Boolean)
      };
    }

    return geometry;
  }

  function encontrarLayerRegion(nombreRegion) {
    let encontrado = null;
    capaRegiones.eachLayer(layer => {
      if (obtenerNombreRegion(layer.feature) === nombreRegion) encontrado = layer;
    });
    return encontrado;
  }

  function restablecerVistaMapa() {
    regionActiva = null;

    if (capaRegiones) {
      capaRegiones.eachLayer(layer => {
        capaRegiones.resetStyle(layer);
      });

      if (mapa.hasLayer(capaRegiones)) {
        mapa.fitBounds(capaRegiones.getBounds(), { padding: [24, 24] });
      }
    }

    mostrarPanelInicial();
  }

  function buscarRegionPorNombre(valor) {
    const busqueda = valor.trim().toLowerCase();
    if (!busqueda || !capaRegiones) return;

    let encontrado = null;
    let nombreEncontrado = "";

    capaRegiones.eachLayer(layer => {
      const nombre = obtenerNombreRegion(layer.feature);
      const info = infoRegiones[nombre];
      const texto = `${nombre} ${info ? info.numero + " " + info.sede + " " + info.estados : ""}`.toLowerCase();

      if (!encontrado && texto.includes(busqueda)) {
        encontrado = layer;
        nombreEncontrado = nombre;
      }
    });

    if (encontrado) seleccionarRegion(encontrado, nombreEncontrado);
  }

  function crearControlBusqueda() {
    const ControlBusqueda = L.Control.extend({
      options: { position: "topleft" },
      onAdd: function () {
        const panel = L.DomUtil.create("div", "busqueda-mapa-naval");
        panel.innerHTML = `
          <form>
            <input type="search" placeholder="Buscar región, sede o estado">
            <button type="submit">Buscar</button>
            <button type="button" class="reset-mapa">Reset</button>
          </form>
        `;

        L.DomEvent.disableClickPropagation(panel);
        L.DomEvent.disableScrollPropagation(panel);

        const form = panel.querySelector("form");
        const input = panel.querySelector("input");
        form.addEventListener("submit", event => {
          event.preventDefault();
          buscarRegionPorNombre(input.value);
        });
        panel.querySelector(".reset-mapa").addEventListener("click", () => {
          input.value = "";
          restablecerVistaMapa();
        });

        return panel;
      }
    });

    mapa.addControl(new ControlBusqueda());
  }

  function crearControlCapas() {
    const ControlCapas = L.Control.extend({
      options: { position: "topright" },
      onAdd: function () {
        const panel = L.DomUtil.create("div", "panel-capas-naval");
        panel.innerHTML = `
          <div class="panel-capas-titulo">Capas operativas</div><div class="panel-capas-resumen"><strong>8</strong> regiones · <strong>8</strong> sedes</div>
          <label><input type="checkbox" data-layer="regiones" checked> Regiones Navales</label>
          <label><input type="checkbox" data-layer="sedes" checked> Sedes Navales</label>
          <label class="capa-placeholder"><input type="checkbox" disabled> Puertos</label>
          <label class="capa-placeholder"><input type="checkbox" disabled> Bases Aeronavales</label>
          <label class="capa-placeholder"><input type="checkbox" disabled> Sectores Navales</label>
          <label class="capa-placeholder"><input type="checkbox" disabled> Rutas Marítimas</label>
        `;

        L.DomEvent.disableClickPropagation(panel);
        L.DomEvent.disableScrollPropagation(panel);

        panel.querySelector('[data-layer="regiones"]').addEventListener("change", event => {
          if (!capaRegiones) return;
          if (event.target.checked) capaRegiones.addTo(mapa);
          else mapa.removeLayer(capaRegiones);
        });

        panel.querySelector('[data-layer="sedes"]').addEventListener("change", event => {
          if (event.target.checked) capaSedes.addTo(mapa);
          else mapa.removeLayer(capaSedes);
        });

        return panel;
      }
    });

    mapa.addControl(new ControlCapas());
  }

  mostrarPanelInicial();
  crearControlCapas();
  crearControlBusqueda();

  fetch("geoJSON/regiones_navales.geojson")
    .then(response => {
      if (!response.ok) throw new Error("No se pudo cargar geoJSON/regiones_navales.geojson");
      return response.json();
    })
    .then(data => {
      const regionesSinPuntos = {
        ...data,
        features: data.features
          .map(feature => ({
            ...feature,
            geometry: quitarGeometriasPuntuales(feature.geometry)
          }))
          .filter(feature => feature.geometry)
      };

      capaRegiones = L.geoJSON(regionesSinPuntos, {
        style: estiloRegion,
        onEachFeature: function (feature, layer) {
          const nombreRegion = obtenerNombreRegion(feature);
          const info = infoRegiones[nombreRegion];

          if (!info) return;

          layer.bindTooltip(`<strong>${info.numero}</strong> REGIÓN NAVAL`, {
            permanent: true,
            direction: "center",
            className: "tooltip-region tooltip-region-naval"
          });

          layer.bindPopup(`
            <div class="popup-naval">
              <span class="popup-etiqueta">Jurisdicción Naval</span>
              <h3>${info.numero} ${nombreRegion}</h3>
              <p><strong>Sede:</strong> ${info.sede}</p>
              <p><strong>Jurisdicción:</strong> ${info.jurisdiccion}</p>
              <p><strong>Estados:</strong> ${info.estados}</p>
            </div>
          `);

          layer.on("mouseover", function () {
            if (regionActiva !== nombreRegion) {
              this.setStyle({
                color: "#ffffff",
                weight: 2.8,
                opacity: 1,
                fillOpacity: 0.56
              });
              this.bringToFront();
            }
          });

          layer.on("mouseout", function () {
            if (regionActiva !== nombreRegion) {
              this.setStyle(regionActiva ? estiloRegionAtenuada(feature) : estiloRegion(feature));
            }
          });

          layer.on("click", function () {
            seleccionarRegion(this, nombreRegion);
          });
        }
      }).addTo(mapa);

      cargarSedesNavales();

      mapa.fitBounds(capaRegiones.getBounds(), {
        padding: [24, 24]
      });
      ajustarMapaAlContenedor();
    })
    .catch(error => {
      console.error("Error cargando geoJSON/regiones_navales.geojson:", error);
    });

  window.setTimeout(ajustarMapaAlContenedor, 350);
  window.setTimeout(ajustarMapaAlContenedor, 900);
})();
