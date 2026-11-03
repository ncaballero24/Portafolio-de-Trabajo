<template>
  <nav class="Navbar" aria-label="Módulos de MapHub"><a class="maphub-brand" href="./">◈ <b>MapHub</b><small>INTELIGENCIA GEOGRÁFICA</small></a>
    <div class="left">
      <select aria-label="Seleccionar módulo" v-model="localMap" @change="emitMapChange">
        <option value="mexico">Mapa de México</option>
        <option value="monitoreo">Mapa Monitoreo Marino</option>
        <option value="terrestre">Monitoreo Terrestre C4</option>
      </select>
    </div>

    <div class="center">
      <h2>{{ title }}</h2>
    </div>

    <div class="right">
      <select
        v-if="localMap === 'mexico'"
        v-model="localLayer"
        @change="emitChange"
      >
        <option value="poblacion">Población</option>
        <option value="recursoFinanciero">Recursos financieros</option>
        <option value="recursoHumano">Recursos humanos</option>
        <option value="personalActivo">Personal activo</option>
      </select>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Navbar',

  props: {
    modelValue: {
      type: String,
      required: true
    },
    mapaSeleccionado: {
      type: String,
      required: true
    }
  },

  emits: ['update:modelValue', 'update:mapaSeleccionado'],

  data() {
    return {
      localLayer: this.modelValue,
      localMap: this.mapaSeleccionado
    }
  },

  computed: {
    title() {
      if (this.localMap === 'mexico') return 'Mapa de México'
      if (this.localMap === 'monitoreo') return 'Mapa de Monitoreo Marino'
      return 'Monitoreo Terrestre C4'
    }
  },

  methods: {
    emitChange() {
      this.$emit('update:modelValue', this.localLayer)
    },

    emitMapChange() {
      this.$emit('update:mapaSeleccionado', this.localMap)
    }
  }
}
</script>

<style scoped>
.Navbar {
  height: 60px;
  background: linear-gradient(90deg, #143a54, #235f7b);
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
.left { width: 30%; }
.center { width: 40%; text-align: center; }
.right { width: 30%; display: flex; justify-content: flex-end; }
.center h2 { margin: 0; font-size: 20px; font-weight: 600; }
.Navbar select {
  padding: 8px 36px 8px 14px;
  border-radius: 20px;
  border: none;
  font-size: 14px;
  background-color: white;
  color: #143a54;
  font-weight: 600;
  cursor: pointer;
}
</style>

<style scoped>
.Navbar{height:72px;box-sizing:border-box;background:#102d3a;padding:0 22px;gap:20px;box-shadow:none;font-family:system-ui,sans-serif}.maphub-brand{color:#fff;text-decoration:none;font-size:22px;white-space:nowrap}.maphub-brand small{display:block;font-size:8px;letter-spacing:2px;color:#80b9ae}.left{width:auto;margin-left:auto}.center,.right{width:auto}.center h2{font-size:13px;color:#b6d0d7}.Navbar select{border:1px solid #426271;background:#1a3c4b;color:white;border-radius:8px;max-width:100%;}
@media(max-width:760px){.Navbar{padding:0 14px;gap:12px}.center,.right{display:none}.maphub-brand{font-size:18px}.maphub-brand small{font-size:6px}.left{min-width:0}.Navbar select{font-size:12px;padding:9px;}}
</style>
