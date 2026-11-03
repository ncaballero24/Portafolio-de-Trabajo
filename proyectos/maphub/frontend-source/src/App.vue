<template>
  <div id="app">

    <Navbar
      v-model="opcionSeleccionada"
      v-model:mapaSeleccionado="mapaSeleccionado"
    />

    <!-- MAPA MÉXICO -->
    <MexicoMap
      v-if="mapaSeleccionado === 'mexico'"
      :opcion="opcionSeleccionada"
      @update-percentiles="percentiles = $event"
    />

    <!-- MAPA MONITOREO -->
    <MonitoreoMap
      v-if="mapaSeleccionado === 'monitoreo'"
    />

    <TerrestreC4Map
      v-if="mapaSeleccionado === 'terrestre'"
    />

    <!-- LEYENDA SOLO PARA MÉXICO -->
    <PopulationLegend
      v-if="mapaSeleccionado === 'mexico' && percentiles"
      :percentiles="percentiles"
      :opcion="opcionSeleccionada"
    />

  </div>
</template>


<script>
import MexicoMap from './components/MexicoMap.vue'
import MonitoreoMap from './components/MonitoreoMap.vue'
import TerrestreC4Map from './components/TerrestreC4Map.vue'
import Navbar from './components/Navbar.vue'
import PopulationLegend from './components/PopulationLegend.vue'

export default {
  components: {
    MexicoMap,
    MonitoreoMap,
    TerrestreC4Map,
    Navbar,
    PopulationLegend
  },

  data() {
    return {
      opcionSeleccionada: 'poblacion',
      mapaSeleccionado: 'terrestre',
      percentiles: null
    }
  }
}
</script>



<style>
#app {
  height: 100vh;
}
</style>
