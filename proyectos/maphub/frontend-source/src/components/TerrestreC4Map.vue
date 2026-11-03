<template>
  <section class="c4-shell">
    <div ref="map" class="c4-map"></div>

    <aside class="c4-panel">
      <div class="panel-head">
        <span>Monitoreo terrestre</span>
        <h3>Centro de operaciones</h3><span class="mode-label">{{ demo ? 'DEMOSTRACIÓN · DATOS FICTICIOS' : 'SEGUIMIENTO OPERATIVO' }}</span>
        <p role="status" aria-live="polite">{{ statusMessage }}</p>
      </div>

      <form v-if="!user" class="login-card" @submit.prevent="login">
        <strong>Ingreso por rol</strong>
        <input v-model="loginForm.username" placeholder="Nombre de usuario" aria-label="Nombre de usuario" autocomplete="username">
        <input v-model="loginForm.password" type="password" placeholder="Contraseña" aria-label="Contraseña" autocomplete="current-password">
        <button type="submit">Entrar</button>
        <small>Roles disponibles: administrador, operador y consulta.</small>
      </form>

      <div v-else class="session-card">
        <div>
          <strong>{{ user.name || user.username }}</strong>
          <small>{{ roleLabel }}</small>
        </div>
        <button type="button" @click="logout">Salir</button>
      </div>

      <div class="metric-grid">
        <article><strong>{{ patrullas.length }}</strong><small>Patrullas</small></article>
        <article><strong>{{ incidencias.length }}</strong><small>Incidencias</small></article>
        <article><strong>{{ pendientes }}</strong><small>Pendientes</small></article>
        <article><strong>{{ atendidas }}</strong><small>Atendidas</small></article>
      </div>

      <button v-if="canOperate" class="new-incident" @click="showCreate = !showCreate">{{ showCreate ? 'Cerrar formulario' : '+ Registrar incidencia' }}</button>
      <form v-if="showCreate" class="create-incident" @submit.prevent="crearIncidencia">
        <label>Tipo<input v-model.trim="incidentForm.tipo" required maxlength="100"></label>
        <label>Descripción<textarea v-model.trim="incidentForm.descripcion" required minlength="10" maxlength="2000" rows="3"></textarea></label>
        <div class="filters-grid">
          <label>Severidad (1–5)<input v-model.number="incidentForm.severidad" type="number" min="1" max="5" required></label>
          <label>Latitud<input v-model.number="incidentForm.lat" type="number" step="any" min="-90" max="90" required></label>
          <label>Longitud<input v-model.number="incidentForm.lng" type="number" step="any" min="-180" max="180" required></label>
        </div>
        <small>Usa las coordenadas del reporte. Los datos de esta demostración son ficticios.</small>
        <button :disabled="busy">{{ busy ? 'Guardando…' : 'Guardar incidencia' }}</button>
      </form>
      <details class="incident-menu"><summary><span>Unidades</span><strong>{{ patrullas.length }} registradas</strong></summary>
        <div class="patrol-list"><button v-for="patrol in patrullas" :key="patrol.id" @click="openHistory(patrol.id)"><b>Unidad {{ patrol.id }}</b><span>{{ patrol.estado }}</span></button></div>
      </details>
      <div class="layer-switcher" aria-label="Modo de visualización">
        <button :class="{ active: visualMode === 'markers' }" @click="setVisualMode('markers')">Puntos</button>
        <button :class="{ active: visualMode === 'cluster' }" @click="setVisualMode('cluster')">Agrupaciones</button>
        <button :class="{ active: visualMode === 'heatmap' }" @click="setVisualMode('heatmap')">Mapa de calor</button>
        <button :class="{ active: visualMode === 'predictivo' }" @click="setVisualMode('predictivo')">Tendencias</button>
      </div>

      <details class="incident-menu">
        <summary>
          <span>Filtros de incidencias</span>
          <strong>{{ incidenciasFiltradas.length }} visibles</strong>
        </summary>
        <div class="filters-grid">
          <label><span>Desde</span><input v-model="filters.fecha_inicio" type="date"></label>
          <label><span>Hasta</span><input v-model="filters.fecha_fin" type="date"></label>
          <label><span>Tipo</span><select v-model="filters.tipo"><option value="">Todas</option><option v-for="tipo in tipos" :key="tipo" :value="tipo">{{ tipo }}</option></select></label>
          <label><span>Zona</span><select v-model="filters.zona"><option value="">Todas</option><option v-for="zona in zonas" :key="zona" :value="zona">{{ zona }}</option></select></label>
          <label><span>Sev. mínima</span><input v-model.number="filters.severidad_min" min="1" max="5" type="number"></label>
          <label><span>Sev. máxima</span><input v-model.number="filters.severidad_max" min="1" max="5" type="number"></label>
        </div>
        <div class="panel-actions">
          <button type="button" @click="aplicarFiltros">Aplicar filtros</button>
          <button type="button" @click="limpiarFiltros">Limpiar filtros</button>
        </div>
      </details>

      <details class="incident-menu" :open="registroOpen" @toggle="registroOpen = $event.target.open">
        <summary>
          <span>Registro de incidencias</span>
          <strong>{{ incidenciasFiltradas.length }} visibles</strong>
        </summary>

        <div class="menu-body">
          <div class="panel-actions">
            <button type="button" @click="exportar('excel')">Exportar Excel</button>
            <button type="button" @click="exportar('pdf')">Exportar PDF</button>
            <button v-if="canAdmin" type="button" @click="limpiarIncidencias">Limpiar antiguas</button>
            <button type="button" @click="cargarTodo">Actualizar</button>
          </div>

          <p v-if="!incidenciasFiltradas.length" class="empty-history">No hay incidencias con estos filtros.</p><div class="incident-list">
            <article
              v-for="incidencia in incidenciasFiltradas.slice(0, 80)"
              :key="incidencia.id"
              class="incident-card"
              :class="{ resolved: isResolved(incidencia) }"
              @click="focusIncident(incidencia)"
            >
              <div>
                <strong>{{ incidencia.tipo || 'Incidencia' }}</strong>
                <span>{{ incidencia.zona || 'Sin zona' }}</span>
              </div>
              <small>Severidad {{ incidencia.severidad || 'N/D' }} · {{ formatDate(incidencia.timestamp) }}</small>
              <p>{{ incidencia.descripcion || 'Sin descripción registrada.' }}</p>
              <button
                v-if="canOperate && !isResolved(incidencia) && !incidencia.patrulla_atendio"
                type="button"
                @click.stop="asignarPatrulla(incidencia)"
              >
                Asignar patrulla
              </button>
              <button v-if="canOperate && !isResolved(incidencia) && incidencia.patrulla_atendio" @click.stop="resolverIncidencia(incidencia)">Marcar atendida · Unidad {{ incidencia.patrulla_atendio }}</button>
              <small class="incident-state">{{ incidencia.estado }} · #{{ incidencia.id }}</small>
            </article>
          </div>
        </div>
      </details>

      <details class="incident-menu">
        <summary><span>Dashboard operativo</span><strong>Gráficas</strong></summary>
        <div class="dashboard-grid">
          <chart-list title="Incidencias por zona" :items="dashboard.por_zona" label-key="zona" value-key="total" />
          <chart-list title="Tipos frecuentes" :items="dashboard.por_tipo" label-key="tipo" value-key="total" />
          <chart-list title="Tiempos de respuesta" :items="dashboard.tiempos_respuesta" label-key="patrulla_id" value-key="promedio_min" suffix=" min" />
          <chart-list title="Patrullas más activas" :items="dashboard.patrullas_activas" label-key="patrulla_id" value-key="total" />
        </div>
      </details>

      <details v-if="canAdmin" class="incident-menu">
        <summary><span>Configuración</span><strong>{{ config.keep_incidencias }} conservar</strong></summary>
        <div class="config-card">
          <label>
            <span>Incidencias recientes a conservar</span>
            <input v-model.number="config.keep_incidencias" type="number" min="50" max="500">
          </label>
          <button type="button" @click="guardarConfiguracion">Guardar configuración</button>
        </div>
      </details>

      <details v-if="canAdmin" class="incident-menu">
        <summary><span>Bitácora de acciones</span><strong>{{ bitacora.length }}</strong></summary>
        <div class="audit-list">
          <article v-for="item in bitacora" :key="item.id">
            <strong>{{ item.action }}</strong>
            <span>{{ item.username }} · {{ item.role }} · {{ formatDate(item.created_at) }}</span>
            <small>{{ item.target }}</small>
          </article>
        </div>
      </details>
    </aside>

    <dialog ref="historyDialog" class="history-dialog">
      <header>
        <div>
          <span>Historial</span>
          <h3>Patrulla {{ historialPatrulla }}</h3>
        </div>
        <button type="button" @click="closeHistory">×</button>
      </header>
      <div v-if="historial.length" class="history-list">
        <article v-for="item in historial" :key="item.id">
          <strong>{{ item.tipo || 'Incidencia' }} · {{ item.estado || 'Registrada' }}</strong>
          <span>{{ formatDate(item.fecha_resolucion) }}</span>
          <p>{{ item.descripcion || 'Sin descripción.' }}</p>
          <small>Zona: {{ item.zona || 'N/D' }} · Severidad: {{ item.severidad || 'N/D' }} · Tiempo: {{ formatSeconds(item.tiempo_respuesta) }}</small>
        </article>
      </div>
      <p v-else class="empty-history">Sin historial registrado para esta patrulla.</p>
    </dialog>
  </section>
</template>

<script>
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet.heat'

const API_BASE = '../terrestre/'

import ChartList from './ChartList.vue'

function withTimeout(ms = 7000) {
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), ms)
  return { signal: controller.signal, done: () => clearTimeout(timer) }
}

export default {
  name: 'TerrestreC4Map',
  components: { ChartList },

  data() {
    return {
      map: null,
      user: null,
      loginForm: { username: '', password: '' },
      patrullas: [],
      incidencias: [],
      predictivo: [],
      historial: [],
      historialPatrulla: '',
      filters: { fecha_inicio: '', fecha_fin: '', tipo: '', zona: '', severidad_min: '', severidad_max: '' },
      visualMode: 'markers',
      registroOpen: true,
      demo: false,
      showCreate: false,
      busy: false,
      incidentForm: {tipo:'Asistencia vial',descripcion:'',severidad:2,lat:19.17,lng:-96.14},
      layers: { patrullas: [], routes: [], incidencias: [], heatmap: [], cluster: [], predictivo: [] },
      dashboard: { por_zona: [], por_tipo: [], tiempos_respuesta: [], patrullas_activas: [] },
      bitacora: [],
      config: { keep_incidencias: 180 },
      refreshTimer: null,
      dispatchTimer: null,
      eventSource: null,
      heatLegend: null,
      zoomHandler: null,
      statusMessage: 'Conectando con APIs de monitoreo...'
    }
  },

  computed: {
    roleLabel() {
      return this.user?.role === 'administrador' ? 'Administrador' : this.user?.role === 'operador' ? 'Operador' : 'Consulta'
    },
    canOperate() {
      return ['administrador', 'operador'].includes(this.user?.role)
    },
    canAdmin() {
      return this.user?.role === 'administrador'
    },
    pendientes() {
      return this.incidencias.filter(i => !this.isResolved(i)).length
    },
    atendidas() {
      return this.incidencias.filter(i => this.isResolved(i)).length
    },
    tipos() {
      return [...new Set(this.incidencias.map(i => i.tipo).filter(Boolean))].sort()
    },
    zonas() {
      return [...new Set(this.incidencias.map(i => i.zona).filter(Boolean))].sort()
    },
    incidenciasFiltradas() {
      return this.incidencias.slice(0, 220)
    }
  },

  methods: {
    escapeHtml(value) { return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])) },
    async fetchJson(path, options = {}) {
      const timeout = withTimeout()
      try {
        const response = await fetch(API_BASE + path, { ...options, signal: timeout.signal })
        const result = await response.json()
        if (!response.ok) this.statusMessage = result.error || result.message || 'La solicitud no pudo completarse.'
        return result
      } catch (error) {
        this.statusMessage = 'No hay conexión con el servidor. Intenta actualizar.'
        throw error
      } finally {
        timeout.done()
      }
    },

    queryString() {
      const params = new URLSearchParams({ limit: '220' })
      Object.entries(this.filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) params.set(key, value)
      })
      return params.toString()
    },

    async cargarSesion() {
      const response = await this.fetchJson('api_me.php')
      this.user = response?.user || null
      this.demo = Boolean(response?.demo)
    },

    async login() {
      const response = await this.fetchJson('api_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.loginForm)
      })
      if (!response?.ok) {
        this.statusMessage = response?.message || 'No fue posible iniciar sesión.'
        return
      }
      this.user = response.user
      this.loginForm = { username: '', password: '' }
      this.statusMessage = `Sesión iniciada como ${this.roleLabel}.`
      this.startDispatchCycle()
      await this.cargarTodo()
    },

    async logout() {
      await this.fetchJson('api_logout.php', { method: 'POST' })
      this.user = null
      if (this.dispatchTimer) clearInterval(this.dispatchTimer)
      this.dispatchTimer = null
      this.statusMessage = 'Sesión cerrada. Vista en modo consulta.'
    },

    async cargarTodo() {
      await Promise.allSettled([this.cargarDatos(), this.cargarDashboard(), this.cargarConfiguracion(), this.cargarBitacora(), this.cargarPredictivo()])
    },

    async cargarDatos() {
      try {
        const [patrullas, incidenciasResponse] = await Promise.all([
          this.fetchJson('api_patrullas.php'),
          this.fetchJson(`api_incidencias.php?${this.queryString()}`)
        ])
        if (!Array.isArray(patrullas) || !Array.isArray(incidenciasResponse?.data)) throw new Error('Respuesta de datos inválida')
        this.patrullas = patrullas
        this.incidencias = Array.isArray(incidenciasResponse?.data) ? incidenciasResponse.data : []
        this.statusMessage = 'Datos actualizados correctamente.'
        this.renderLayers()
      } catch (error) {
        console.error(error)
        this.statusMessage = 'Sin conexión. Los datos visibles pueden estar desactualizados.'
        this.renderLayers()
      }
    },

    async cargarDashboard() {
      const response = await this.fetchJson('api_dashboard.php')
      if (response?.ok) this.dashboard = response
    },

    async cargarPredictivo() {
      const response = await this.fetchJson('api_predictivo.php')
      this.predictivo = Array.isArray(response?.data) ? response.data : []
      if (this.visualMode === 'predictivo') this.renderLayers()
    },

    async cargarConfiguracion() {
      const response = await this.fetchJson('api_configuracion.php')
      if (response?.ok) this.config.keep_incidencias = response.keep_incidencias
    },

    async cargarBitacora() {
      if (!this.canAdmin) return
      const response = await this.fetchJson('api_bitacora.php?limit=80')
      this.bitacora = Array.isArray(response?.data) ? response.data : []
    },

    aplicarFiltros() {
      this.cargarDatos()
    },

    limpiarFiltros() {
      this.filters = { fecha_inicio: '', fecha_fin: '', tipo: '', zona: '', severidad_min: '', severidad_max: '' }
      this.cargarDatos()
    },

    clearLayerGroup(name) {
      this.layers[name].forEach(layer => this.map.removeLayer(layer))
      this.layers[name] = []
    },

    clearIncidentLayers() {
      this.clearLayerGroup('incidencias')
      this.clearLayerGroup('heatmap')
      this.clearLayerGroup('cluster')
      this.clearLayerGroup('predictivo')
      if (this.heatLegend) {
        this.map.removeControl(this.heatLegend)
        this.heatLegend = null
      }
    },

    setVisualMode(mode) {
      this.visualMode = mode
      this.renderLayers()
    },

    renderLayers() {
      if (!this.map) return
      this.layers.patrullas.forEach(layer => this.map.removeLayer(layer))
      this.layers.patrullas = []
      this.clearLayerGroup('routes')
      this.clearIncidentLayers()
      this.renderPatrullas()
      if (this.visualMode === 'markers') this.renderMarkers()
      if (this.visualMode === 'cluster') this.renderClusters()
      if (this.visualMode === 'heatmap') this.renderHeatmap()
      if (this.visualMode === 'predictivo') this.renderPredictiveModel()
    },

    renderPatrullas() {
      this.patrullas.forEach(p => {
        const lat = parseFloat(p.lat)
        const lng = parseFloat(p.lng)
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return
        const state = String(p.estado || '').toLowerCase()
        const enCamino = state.includes('camino')
        const disponible = state.includes('disponible')
        const color = enCamino ? '#d97706' : disponible ? '#0f6b78' : '#8f2944'
        const fillColor = enCamino ? '#ffb020' : disponible ? '#2fc3d6' : '#ff5b7a'
        const marker = L.circleMarker([lat, lng], {
          radius: enCamino ? 11 : 9,
          color,
          weight: 3,
          fillColor,
          fillOpacity: 0.92,
          className: enCamino ? 'patrol-marker en-route' : 'patrol-marker'
        }).addTo(this.map)
        const targetLat = parseFloat(p.destino_lat)
        const targetLng = parseFloat(p.destino_lng)
        const hasTarget = Number.isFinite(targetLat) && Number.isFinite(targetLng)
        const assignment = p.incidencia_id
          ? `<br>Incidencia asignada: #${p.incidencia_id}${p.distancia_m ? `<br>Distancia: ${Math.round(Number(p.distancia_m))} m` : ''}`
          : ''
        marker.bindPopup(`<b>Patrulla ${p.id}</b><br>Estado: ${this.escapeHtml(p.estado || 'N/D')}${assignment}<br><button class="popup-button" data-patrulla="${p.id}">Ver historial</button>`)
        marker.on('popupopen', () => setTimeout(() => document.querySelector(`[data-patrulla="${p.id}"]`)?.addEventListener('click', () => this.openHistory(p.id))))
        this.layers.patrullas.push(marker)

        if (hasTarget && enCamino) {
          const route = L.polyline([[lat, lng], [targetLat, targetLng]], {
            color: '#d97706',
            weight: 4,
            opacity: 0.82,
            dashArray: '10 9',
            lineCap: 'round',
            className: 'patrol-route'
          }).addTo(this.map)
          const destination = L.circleMarker([targetLat, targetLng], {
            radius: 8,
            color: '#8f2944',
            weight: 3,
            fillColor: '#fff',
            fillOpacity: 1
          }).addTo(this.map)
          destination.bindTooltip(`Destino de patrulla ${p.id} · Incidencia #${p.incidencia_id}`, { direction: 'top' })
          this.layers.routes.push(route, destination)
        }
      })
    },

    renderMarkers() {
      this.incidenciasFiltradas.forEach(i => {
        const lat = parseFloat(i.latitud)
        const lng = parseFloat(i.longitud)
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return
        const resolved = this.isResolved(i)
        const marker = L.circleMarker([lat, lng], {
          radius: 7 + Math.min(parseInt(i.severidad || 1, 10), 5),
          color: resolved ? '#40705a' : '#9c263f',
          weight: 2,
          fillColor: resolved ? '#7ed6a8' : '#ff5b7a',
          fillOpacity: 0.76
        }).addTo(this.map)
        const assignAction = this.canOperate && !resolved && !i.patrulla_atendio
          ? `<br><button class="popup-button assign-patrol-button" data-assign-incident="${i.id}">Asignar patrulla</button>`
          : ''
        marker.bindPopup(`<b>${this.escapeHtml(i.tipo || 'Incidencia')} #${i.id}</b><br>${this.escapeHtml(i.descripcion || 'Sin descripción')}<br>Zona: ${this.escapeHtml(i.zona || 'N/D')}<br>Severidad: ${i.severidad || 'N/D'}<br>Estado: ${this.escapeHtml(i.estado || 'Pendiente')}${assignAction}`)
        marker.on('popupopen', () => {
          setTimeout(() => {
            const button = document.querySelector(`[data-assign-incident="${i.id}"]`)
            button?.addEventListener('click', () => this.asignarPatrulla(i), { once: true })
          })
        })
        this.layers.incidencias.push(marker)
      })
    },

    getSpatialBuckets(cellSize = 90) {
      const buckets = new Map()
      const zoom = this.map?.getZoom() || 11
      this.incidenciasFiltradas.forEach(i => {
        const lat = parseFloat(i.latitud)
        const lng = parseFloat(i.longitud)
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return
        const point = this.map.project([lat, lng], zoom)
        const key = `${Math.floor(point.x / cellSize)},${Math.floor(point.y / cellSize)}`
        const severity = Math.max(1, Math.min(5, parseInt(i.severidad || 1, 10)))
        const bucket = buckets.get(key) || {
          lat: 0,
          lng: 0,
          count: 0,
          severity: 0,
          maxSeverity: 0,
          unresolved: 0,
          types: {}
        }
        bucket.lat += lat
        bucket.lng += lng
        bucket.count += 1
        bucket.severity += severity
        bucket.maxSeverity = Math.max(bucket.maxSeverity, severity)
        bucket.unresolved += this.isResolved(i) ? 0 : 1
        const type = i.tipo || 'Sin tipo'
        bucket.types[type] = (bucket.types[type] || 0) + 1
        buckets.set(key, bucket)
      })
      return [...buckets.values()].map(b => ({
        ...b,
        lat: b.lat / b.count,
        lng: b.lng / b.count,
        severity: b.severity / b.count,
        dominantType: Object.entries(b.types).sort((a, c) => c[1] - a[1])[0]?.[0] || 'Sin tipo'
      }))
    },

    getClusterBuckets() {
      const zoom = this.map?.getZoom() || 11
      const cellSize = zoom <= 8 ? 170 : zoom <= 10 ? 138 : zoom <= 12 ? 108 : zoom <= 14 ? 78 : 52
      return this.getSpatialBuckets(cellSize)
    },

    renderClusters() {
      const zoom = this.map?.getZoom() || 11
      this.getClusterBuckets().forEach(bucket => {
        const size = Math.round(Math.max(40, Math.min(82, 34 + Math.sqrt(bucket.count) * 8)))
        const riskClass = bucket.maxSeverity >= 5 || bucket.severity >= 4
          ? 'risk-critical'
          : bucket.severity >= 3
            ? 'risk-high'
            : 'risk-medium'
        const icon = L.divIcon({
          className: 'cluster-icon',
          html: `<div class="cluster-bubble ${riskClass}" style="width:${size}px;height:${size}px"><span>${bucket.count}</span><small>incid.</small></div>`,
          iconSize: [size, size],
          iconAnchor: [size / 2, size / 2]
        })
        const cluster = L.marker([bucket.lat, bucket.lng], { icon, keyboard: true }).addTo(this.map)
        cluster.bindPopup(`<b>${bucket.count} incidencias agrupadas</b><br>${bucket.unresolved} pendientes<br>Severidad promedio: ${bucket.severity.toFixed(1)}<br>Tipo frecuente: ${bucket.dominantType}`)
        cluster.on('click', () => {
          if (bucket.count > 1 && zoom < 17) this.map.flyTo([bucket.lat, bucket.lng], Math.min(17, zoom + 2))
        })
        this.layers.cluster.push(cluster)
      })
    },

    renderHeatmap() {
      const zoom = this.map?.getZoom() || 11
      const points = this.incidenciasFiltradas
        .map(incidencia => {
          const lat = parseFloat(incidencia.latitud)
          const lng = parseFloat(incidencia.longitud)
          if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null
          const severity = Math.max(1, Math.min(5, parseInt(incidencia.severidad || 1, 10)))
          const unresolvedBoost = this.isResolved(incidencia) ? 0.08 : 0.24
          return [lat, lng, Math.min(1, 0.18 + severity * 0.13 + unresolvedBoost)]
        })
        .filter(Boolean)

      const heat = L.heatLayer(points, {
        radius: zoom <= 8 ? 32 : zoom <= 10 ? 38 : zoom <= 12 ? 46 : 54,
        blur: zoom <= 10 ? 30 : 34,
        maxZoom: 17,
        max: 1,
        minOpacity: 0.34,
        gradient: {
          0.08: '#2737ff',
          0.24: '#00a8ff',
          0.42: '#00e68a',
          0.58: '#d8ff32',
          0.73: '#ffe22e',
          0.86: '#ff8a1f',
          1: '#ef182f'
        }
      }).addTo(this.map)
      this.layers.heatmap.push(heat)
      this.showHeatLegend()
    },

    showHeatLegend() {
      if (this.heatLegend) this.map.removeControl(this.heatLegend)
      this.heatLegend = L.control({ position: 'bottomleft' })
      this.heatLegend.onAdd = () => {
        const container = L.DomUtil.create('div', 'heat-legend')
        container.innerHTML = '<strong>Concentración</strong><div class="heat-scale"></div><div class="heat-scale-labels"><span>Baja</span><span>Media</span><span>Alta</span></div>'
        L.DomEvent.disableClickPropagation(container)
        return container
      }
      this.heatLegend.addTo(this.map)
    },

    renderPredictiveModel() {
      const source = this.predictivo.length ? this.predictivo : this.getClusterBuckets()
      source.slice(0, 12).forEach(bucket => {
        const lat = parseFloat(bucket.lat)
        const lng = parseFloat(bucket.lng)
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return
        const score = Number(bucket.score || (bucket.count * bucket.severity) || bucket.total || 1)
        const zone = L.circle([lat, lng], {
          radius: Math.min(1500, 280 + score * 45),
          color: score > 18 ? '#7a1730' : '#a85a00',
          weight: 2,
          dashArray: '8 6',
          fillColor: score > 18 ? '#ff3366' : '#ffb000',
          fillOpacity: 0.18
        }).addTo(this.map)
        zone.bindPopup(`<b>Zona predictiva</b><br>Riesgo: ${Math.round(score)}<br>Hora pico: ${bucket.hora_pico ?? 'N/D'}<br>Tipo recurrente: ${bucket.tipo_recurrente ?? 'N/D'}`)
        this.layers.predictivo.push(zone)
      })
    },

    focusIncident(incidencia) {
      const lat = parseFloat(incidencia.latitud)
      const lng = parseFloat(incidencia.longitud)
      if (Number.isFinite(lat) && Number.isFinite(lng)) this.map.flyTo([lat, lng], 14)
    },

    async crearIncidencia() {
      this.busy=true
      try {
        const result=await this.fetchJson('api_crear_incidencia.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.incidentForm)})
        if (result.ok) {this.showCreate=false;this.incidentForm.descripcion='';await this.cargarTodo();this.statusMessage='Incidencia registrada correctamente.'}
      } catch {} finally {this.busy=false}
    },
    async resolverIncidencia(incidencia) {
      const result=await this.fetchJson('api_resolver_incidencia.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:incidencia.id})})
      if(result.success) {await this.cargarTodo();this.statusMessage='Atención registrada en el historial de la unidad.'}
    },
    async asignarPatrulla(incidencia) {
      const response = await this.fetchJson('api_despacho.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ incidencia_id: incidencia.id, lat: incidencia.latitud, lng: incidencia.longitud })
      })
      this.statusMessage = response?.success ? `Patrulla ${response.patrulla_id} asignada a incidencia ${incidencia.id}.` : (response?.error || 'No fue posible asignar patrulla.')
      if (response?.success) await this.procesarDespachos()
      await this.cargarTodo()
    },

    async procesarDespachos() {
      if (!this.canOperate || !this.demo) return null
      try {
        const response = await this.fetchJson('api_procesar_despachos.php', { method: 'POST' })
        if (response?.resolved > 0) {
          this.statusMessage = `${response.resolved} incidencia(s) atendida(s); las patrullas volvieron a estar disponibles.`
        }
        return response
      } catch (error) {
        console.error(error)
        return null
      }
    },

    startDispatchCycle() {
      if (this.dispatchTimer) clearInterval(this.dispatchTimer)
      if (!this.canOperate || !this.demo) return
      this.dispatchTimer = setInterval(async () => {
        const response = await this.procesarDespachos()
        if ((response?.moved || 0) > 0 || (response?.resolved || 0) > 0) {
          await Promise.all([this.cargarDatos(), this.cargarDashboard()])
        }
      }, 8000)
    },

    async limpiarIncidencias() {
      if (!confirm(`Se conservarán ${this.config.keep_incidencias} incidencias recientes y se eliminarán antiguas sin historial. ¿Continuar?`)) return
      const response = await this.fetchJson(`api_limpiar_incidencias.php?keep=${this.config.keep_incidencias}`, { method: 'POST' })
      this.statusMessage = response?.ok ? `Limpieza aplicada: ${response.deleted} incidencias eliminadas.` : (response?.error || 'No fue posible limpiar incidencias.')
      await this.cargarTodo()
    },

    async guardarConfiguracion() {
      const response = await this.fetchJson('api_configuracion.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.config)
      })
      this.statusMessage = response?.ok ? 'Configuración guardada.' : (response?.error || 'No fue posible guardar configuración.')
      await this.cargarBitacora()
    },

    exportar(format) {
      window.open(`${API_BASE}api_exportar_incidencias.php?format=${format}&${this.queryString()}`, '_blank')
    },

    async openHistory(id) {
      this.historialPatrulla = id
      this.historial = []
      this.$refs.historyDialog?.showModal()
      const response = await this.fetchJson(`api_historial_patrulla.php?patrulla_id=${encodeURIComponent(id)}`)
      this.historial = Array.isArray(response?.data) ? response.data : []
    },

    closeHistory() {
      this.$refs.historyDialog?.close()
    },

    startRealtime() {
      if (!window.EventSource) return
      this.eventSource = new EventSource(`${API_BASE}api_events.php`)
      this.eventSource.addEventListener('maphub', event => {
        const payload = JSON.parse(event.data || '{}')
        if (payload.changed) this.cargarTodo()
      })
      this.eventSource.onerror = () => {
        this.eventSource?.close()
        this.eventSource = null
        setTimeout(() => {
          if (!this.eventSource) this.startRealtime()
        }, 3000)
      }
    },

    isResolved(incidencia) {
      return String(incidencia.estado || '').toLowerCase().includes('atendida') || String(incidencia.resuelta || '') === '1'
    },

    formatDate(value) {
      if (!value) return 'Sin fecha'
      return new Date(value).toLocaleString('es-MX')
    },

    formatSeconds(value) {
      const seconds = parseInt(value || 0, 10)
      if (!seconds) return 'N/D'
      return `${Math.round(seconds / 60)} min`
    }
  },

  async mounted() {
    this.map = L.map(this.$refs.map).setView([19.17, -96.13], 11)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(this.map)
    this.zoomHandler = () => {
      if (['cluster', 'heatmap'].includes(this.visualMode)) this.renderLayers()
    }
    this.map.on('zoomend', this.zoomHandler)
    await this.cargarSesion()
    if (this.canOperate) await this.procesarDespachos()
    await this.cargarTodo()
    this.refreshTimer = setInterval(this.cargarTodo, 30000)
    this.startDispatchCycle()
    // Polling cada 30 segundos: compatible con PHP CLI y Apache.
  },

  beforeUnmount() {
    if (this.refreshTimer) clearInterval(this.refreshTimer)
    if (this.dispatchTimer) clearInterval(this.dispatchTimer)
    if (this.eventSource) this.eventSource.close()
    if (this.map && this.zoomHandler) this.map.off('zoomend', this.zoomHandler)
    if (this.map) this.map.remove()
  }
}
</script>

<style scoped>
.c4-shell{position:relative;height:calc(100vh - 60px);overflow:hidden;background:#0c1f2f}.c4-map{width:100%;height:100%}.c4-panel{position:absolute;top:18px;right:18px;z-index:900;width:min(520px,calc(100% - 36px));max-height:calc(100% - 36px);overflow:auto;padding:18px;border:1px solid rgba(207,221,228,.9);border-radius:18px;background:rgba(255,255,255,.95);box-shadow:0 22px 60px rgba(5,25,40,.25);backdrop-filter:blur(10px)}.panel-head span,.incident-menu summary span,.filters-grid span,.history-dialog header span,.config-card span{color:#6d8491;font-size:.68rem;font-weight:900;letter-spacing:.14em;text-transform:uppercase}.panel-head h3{margin:6px 0;color:#102f46;font-family:Georgia,"Times New Roman",serif;font-size:1.8rem}.panel-head p{margin:0;color:#5f6f7b;line-height:1.45}.login-card,.session-card,.config-card{display:grid;gap:10px;margin:14px 0;padding:14px;border:1px solid #dce6eb;border-radius:14px;background:#f7fafb}.session-card{display:flex;align-items:center;justify-content:space-between}.login-card input,.filters-grid input,.filters-grid select,.config-card input{min-height:38px;padding:0 12px;border:1px solid #cbd9e0;border-radius:10px;background:white;color:#173047;font:inherit}.login-card button,.session-card button,.config-card button,.layer-switcher button,.panel-actions button{min-height:36px;border:1px solid #cbd9e0;border-radius:10px;background:white;color:#173047;font-weight:800;cursor:pointer}.login-card button,.config-card button{color:white;border-color:#235f7b;background:#235f7b}.metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:16px 0}.metric-grid article{padding:12px 8px;text-align:center;border:1px solid #dce6eb;border-radius:12px;background:#f7fafb}.metric-grid strong{display:block;color:#123852;font-size:1.35rem}.metric-grid small,.session-card small{color:#6d8491;font-size:.65rem;font-weight:800;text-transform:uppercase}.layer-switcher{display:grid;grid-template-columns:repeat(4,1fr);gap:7px;margin-bottom:14px}.layer-switcher button.active{color:white;border-color:#235f7b;background:#235f7b}.incident-menu{margin-top:10px;border:1px solid #dce6eb;border-radius:14px;background:#fff}.incident-menu summary{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px;cursor:pointer}.incident-menu summary strong{color:#102f46}.menu-body,.filters-grid,.dashboard-grid,.audit-list{padding:0 14px 14px}.filters-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.filters-grid label,.config-card label{display:grid;gap:6px}.panel-actions{display:flex;flex-wrap:wrap;gap:8px;padding:0 14px 14px}.panel-actions button:hover{border-color:#235f7b}.incident-list{display:grid;gap:10px;max-height:360px;overflow:auto}.incident-card{padding:14px;border:1px solid #e0e8ec;border-left:5px solid #c53b55;border-radius:14px;background:white;cursor:pointer;transition:.15s}.incident-card:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(23,48,71,.1)}.incident-card.resolved{border-left-color:#4f9a70}.incident-card div{display:flex;justify-content:space-between;gap:10px}.incident-card strong{color:#102f46;text-transform:capitalize}.incident-card span,.incident-card small{color:#6d8491}.incident-card p{margin:8px 0;color:#536470;line-height:1.45}.incident-card button,.popup-button{padding:7px 10px;border:0;border-radius:9px;color:white;background:#235f7b;font-weight:800;cursor:pointer}.dashboard-grid{display:grid;grid-template-columns:1fr;gap:10px}.chart-card{padding:12px;border:1px solid #e0e8ec;border-radius:14px;background:#fbfdfe}.chart-card h4{margin:0 0 10px;color:#102f46}.bar-row{display:grid;grid-template-columns:110px 1fr 64px;gap:8px;align-items:center;margin:6px 0;font-size:.82rem}.bar-row span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#536470}.bar-row div{height:9px;border-radius:20px;background:#e6eef2;overflow:hidden}.bar-row i{display:block;height:100%;border-radius:20px;background:linear-gradient(90deg,#235f7b,#2fc3d6)}.bar-row strong{text-align:right;color:#102f46}.audit-list{display:grid;gap:8px;max-height:240px;overflow:auto}.audit-list article{padding:10px;border:1px solid #e0e8ec;border-radius:12px}.audit-list strong{display:block;color:#102f46}.audit-list span,.audit-list small{display:block;color:#6d8491}.history-dialog{width:min(820px,calc(100% - 34px));border:0;border-radius:18px;padding:0;box-shadow:0 24px 80px rgba(0,0,0,.35)}.history-dialog::backdrop{background:rgba(5,25,40,.6)}.history-dialog header{display:flex;justify-content:space-between;align-items:center;padding:22px 24px;border-bottom:1px solid #dfe8ed}.history-dialog h3{margin:4px 0 0;color:#102f46;font-family:Georgia,"Times New Roman",serif;font-size:1.7rem}.history-dialog header button{width:36px;height:36px;border:0;border-radius:50%;background:#eef4f6;color:#173047;font-size:1.5rem;cursor:pointer}.history-list{display:grid;gap:10px;max-height:520px;overflow:auto;padding:20px 24px}.history-list article{padding:14px;border:1px solid #dfe8ed;border-radius:12px}.history-list strong{display:block;color:#102f46}.history-list span,.history-list small{color:#6d8491}.history-list p{color:#536470}.empty-history{padding:28px;color:#6d8491;text-align:center}
:global(.cluster-icon),:global(.heat-icon){border:0!important;background:transparent!important}
:global(.cluster-bubble){display:grid;place-content:center;text-align:center;border:3px solid rgba(255,255,255,.94);border-radius:50%;color:#fff;box-shadow:0 0 0 3px rgba(18,56,82,.34),0 10px 24px rgba(5,25,40,.3);transform-origin:center;transition:transform .18s,box-shadow .18s}
:global(.cluster-bubble:hover){transform:scale(1.08);box-shadow:0 0 0 4px rgba(18,56,82,.3),0 14px 30px rgba(5,25,40,.38)}
:global(.cluster-bubble span){font-size:1.15rem;font-weight:950;line-height:1}
:global(.cluster-bubble small){margin-top:2px;font-size:.5rem;font-weight:850;letter-spacing:.05em;text-transform:uppercase}
:global(.cluster-bubble.risk-medium){background:linear-gradient(145deg,#207d8f,#164e67)}
:global(.cluster-bubble.risk-high){background:linear-gradient(145deg,#ed9a27,#b75512)}
:global(.cluster-bubble.risk-critical){background:linear-gradient(145deg,#e54b64,#821b39)}
:global(.heat-spot){display:block;border-radius:50%;filter:blur(7px);mix-blend-mode:multiply;transform:translateZ(0);pointer-events:auto}
:global(.heat-spot.heat-medium){background:radial-gradient(circle,rgba(255,235,86,.96) 0 10%,rgba(111,207,114,.72) 30%,rgba(43,170,196,.35) 56%,rgba(43,170,196,0) 74%)}
:global(.heat-spot.heat-high){background:radial-gradient(circle,rgba(255,248,110,.98) 0 9%,rgba(255,155,49,.82) 29%,rgba(239,74,70,.47) 54%,rgba(239,74,70,0) 75%)}
:global(.heat-spot.heat-critical){background:radial-gradient(circle,rgba(255,255,214,1) 0 7%,rgba(255,86,45,.94) 24%,rgba(188,22,63,.66) 49%,rgba(112,14,57,0) 76%)}
:global(.heat-legend){display:grid;gap:5px;min-width:142px;padding:11px 13px;border:1px solid rgba(207,221,228,.95);border-radius:12px;background:rgba(255,255,255,.94);box-shadow:0 8px 24px rgba(5,25,40,.18);color:#173047}
:global(.heat-legend strong){margin-bottom:2px;font-size:.72rem;text-transform:uppercase}
:global(.heat-legend span){display:flex;align-items:center;gap:7px;font-size:.68rem}
:global(.heat-legend i){width:34px;height:7px;border-radius:10px}
:global(.heat-legend i.low){background:linear-gradient(90deg,#2baac4,#6fcf72)}
:global(.heat-legend i.medium){background:linear-gradient(90deg,#ffe85a,#ff9b31)}
:global(.heat-legend i.high){background:linear-gradient(90deg,#ff562d,#bc163f)}
:global(.heat-legend .heat-scale){width:150px;height:13px;border:1px solid rgba(16,47,70,.18);border-radius:20px;background:linear-gradient(90deg,#2737ff 0%,#00a8ff 18%,#00e68a 38%,#d8ff32 55%,#ffe22e 70%,#ff8a1f 85%,#ef182f 100%)}
:global(.heat-legend .heat-scale-labels){display:flex;justify-content:space-between;color:#5b6f7c;font-size:.62rem;font-weight:800;text-transform:uppercase}
:global(.assign-patrol-button){display:block;width:100%;margin-top:10px;background:linear-gradient(135deg,#1d5875,#237b91)!important;box-shadow:0 5px 14px rgba(29,88,117,.22)}
:global(.patrol-marker.en-route){animation:sirenPatrol 1.15s steps(1,end) infinite;stroke-width:5!important}
:global(.patrol-route){animation:sirenRoute 1.15s steps(1,end) infinite,routeFlow 1.4s linear infinite}
@keyframes sirenPatrol{0%,49%{stroke:#d20f32;fill:#ff294d;filter:drop-shadow(0 0 7px rgba(255,24,62,.95))}50%,100%{stroke:#074fc4;fill:#178eff;filter:drop-shadow(0 0 7px rgba(20,119,255,.95))}}
@keyframes sirenRoute{0%,49%{stroke:#e2183f}50%,100%{stroke:#1268dc}}
@keyframes routeFlow{to{stroke-dashoffset:-38}}
@media(max-width:760px){.c4-panel{left:12px;right:12px;top:auto;bottom:12px;width:auto;max-height:58%}.metric-grid,.layer-switcher,.filters-grid{grid-template-columns:repeat(2,1fr)}.bar-row{grid-template-columns:86px 1fr 54px}}

.metric-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
.c4-panel { min-width:0; overflow-x:hidden; }
.session-card > div { display:grid; gap:4px; min-width:0; }
.filters-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
.filters-grid input,.filters-grid select { width:100%; min-width:0; }
.incident-card { overflow-wrap:anywhere; }
@media(max-width:600px) { .filters-grid { grid-template-columns:1fr; } }
</style>

<style scoped>
.c4-shell{display:grid;grid-template-columns:390px minmax(0,1fr);height:calc(100dvh - 72px);background:#f1f5f9;font-family:Inter,system-ui,sans-serif}
.c4-map{grid-column:2;grid-row:1;min-width:0;}
.c4-panel{grid-column:1;grid-row:1;position:relative;top:auto;right:auto;width:auto;max-height:none;border:0;border-right:1px solid #dbe3ed;border-radius:0;box-shadow:none;padding:22px;background:#fff;}
.panel-head h3{font-family:inherit;font-size:23px;letter-spacing:-.6px;margin-bottom:12px;}
.panel-head p{font-size:12px;min-height:36px;margin-top:12px;}
.mode-label{display:inline-block!important;font-size:9px!important;letter-spacing:.08em!important;background:#e2f2ef;color:#176d5c!important;padding:7px 9px;border-radius:6px;}
.metric-grid article{padding:13px 9px;box-shadow:none;}
.metric-grid strong{font-size:24px;}
.layer-switcher{display:grid;grid-template-columns:1fr 1fr;gap:6px;}
.layer-switcher button{min-height:38px;font-size:11px;}
.new-incident,.create-incident button{width:100%;min-height:44px;background:#156f61;color:white;border:0;border-radius:9px;font-weight:700;margin:10px 0;cursor:pointer;}
.create-incident{display:grid;gap:10px;padding:14px;background:#f0f7f5;border-radius:12px;}
.create-incident label{display:grid;gap:5px;font-size:12px;}
.create-incident input,.create-incident textarea{box-sizing:border-box;min-width:0;width:100%;border:1px solid #cbd9e0;padding:9px;border-radius:7px;font:inherit;}
.create-incident small{color:#5c6f7a;line-height:1.5;}
.patrol-list{display:grid;gap:6px;margin-top:12px;}
.patrol-list button{display:flex;justify-content:space-between;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;color:#23364d;}
.incident-card{cursor:pointer;border-left:3px solid #c58a35;}
.incident-card.resolved{border-left-color:#219786;}
.incident-state{display:block;margin-top:9px;font-weight:700;}
.incident-card p{font-size:12px;line-height:1.6;}
button:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline:3px solid #59b8a8;outline-offset:2px;}
@media(max-width:760px){.c4-shell{height:auto;min-height:calc(100dvh - 72px);grid-template-columns:1fr;grid-template-rows:40dvh auto;overflow:visible}.c4-map{grid-column:1;grid-row:1;position:sticky;top:0}.c4-panel{grid-column:1;grid-row:2;width:auto;max-height:none;padding:18px;border-radius:18px 18px 0 0;margin-top:-12px}.history-dialog{max-height:90dvh;overflow:auto}.filters-grid input,.login-card input,.create-incident input,.create-incident textarea{font-size:16px}}

.metric-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
.c4-panel { min-width:0; overflow-x:hidden; }
.session-card > div { display:grid; gap:4px; min-width:0; }
.filters-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
.filters-grid input,.filters-grid select { width:100%; min-width:0; }
.incident-card { overflow-wrap:anywhere; }
@media(max-width:600px) { .filters-grid { grid-template-columns:1fr; } }
</style>
