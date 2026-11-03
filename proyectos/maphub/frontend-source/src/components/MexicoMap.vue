<template>
  <div ref="map" id="map"></div>
</template>
<script>
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import mexicoStates from '../assets/states.json'
import keyStates from '../assets/keyStates.json'
import {calcularPercentiles} from '../assets/percentil.js'
import { docTitle } from '../assets/labels.js'




function getColorByFeature(percentil,pop) {
  return pop >percentil.p75 ? '#08306B' :
         pop >percentil.p65 ? '#08519C' :
         pop >percentil.p50  ? '#2171B5' :
         pop >percentil.p35  ? '#4292C6' :
         pop >percentil.p25  ? '#6BAED6' :
                          '#C6DBEF'
}

export default {
  emits: ['update-percentiles'], //declarando que el componente emite eventos

  data() {
  return {
    map: null,
    statesLayer: null //se agrego para borrar la capa anterior del mapa
  }
},

  methods: {
  pintarEstados(nuevaOpcion = 'poblacion') {

  //  calcular UNA sola vez
  const percentiles = calcularPercentiles(keyStates, nuevaOpcion)

  //  mandar datos al padre UNA sola vez
  this.$emit('update-percentiles', percentiles)

  //  eliminar capa anterior si existe
  if (this.statesLayer) {
    this.map.removeLayer(this.statesLayer)
  }

  // crear nueva capa
  this.statesLayer = L.geoJSON(mexicoStates, {

    style: feature => {

      const code = feature.properties.id
      const stateInfo = keyStates[code] || {}
      const datos = stateInfo[nuevaOpcion] || 0



      return {
        color: '#1B5E20',
        weight: 1,
        fillColor: getColorByFeature(percentiles, datos),
        fillOpacity: 0.8
      }
    },

    onEachFeature: (feature, layer) => {

      const code = feature.properties.id
      const nombre = keyStates[code].nombre || code
      const stateJson = keyStates[code] || {}
      const dato = stateJson[nuevaOpcion]
      const etiquetasT = docTitle[nuevaOpcion] || nuevaOpcion

      layer.bindTooltip(
        `
        <div class="tooltip-content">
          <div class="tooltip-title">${nombre}</div>
          <div class ="tooltip-population">
            ${etiquetasT}: ${dato.toLocaleString()}
          </div>
        </div>
        `,
        { sticky: true, className: 'tooltip-grande' }
      )
      //Pinta cada estado al pasar al mouse
      layer.on({
  mouseover: e => {
    e.target.setStyle({
      weight: 3,
      color: '#000'
    })
  },
  mouseout: e => {
    this.statesLayer.resetStyle(e.target)
  }
})

    }

  }).addTo(this.map)

}

  },

   props: {         //opcion activa
    opcion: String
  },
   watch: {
    opcion(nuevaOpcion) {
      console.log('La opción cambió a:', nuevaOpcion)
      this.pintarEstados(nuevaOpcion)
    }
  },


  name: 'MexicoMap',
  mounted() {
      this.map = L.map(this.$refs.map, {     //variable global
      minZoom: 5,
      maxZoom: 8,
      maxBoundsViscosity: 0.8
    })

    L.tileLayer(
      'https://{s}.basemaps.cartocdn.com/light_nolabel/{z}/{x}/{y}{r}.png',  //all, muestra todo el mapa con nombres
      { attribution: '© OpenStreetMap © CARTO' }
    ).addTo(this.map)




    this.pintarEstados()

    const bounds = this.statesLayer.getBounds()

    this.map.fitBounds(bounds)
    this.map.setMaxBounds(bounds)




  }
}
</script>


<style >
#map {
  height: 100vh;
  width: 100%;
}

.tooltip-title {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 4px;
}

.tooltip-population {
  font-size: 18px;
  margin-bottom: 4px;
}


</style>
