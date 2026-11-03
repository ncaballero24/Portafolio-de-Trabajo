<template>
  <div class="legend" v-if="percentiles">
    <h4>{{ titulo }}</h4>

    <ul>
      <li v-for="item in legendItems" :key="item.label">
        <span
          class="color-box"
          :style="{ backgroundColor: item.color }"
        ></span>
        <span class="label">{{ item.label }}</span>
      </li>
    </ul>
  </div>
</template>

<script>
import { docTitle } from '../assets/labels.js' // Importas el mismo archivo
/*
const docTitle = {
  poblacion: 'Población',
  recursoFinanciero: 'Recurso Financiero',
  recursoHumano: 'Recurso Humano',
  personalActivo: 'Personal Activo'
}
*/

export default {
  name: 'PopulationLegend',

  props: {
    percentiles: {
      type: Object,
      required: true
    },
    opcion: {
      type: String,
      required: true
    }
  },

  computed: {
    titulo() {
      return docTitle[this.opcion] || this.opcion
    },

    legendItems() {
      const p = this.percentiles

      return [
        { label: `Mayor a ${p.p75.toLocaleString()}`, color: '#08306B' },
        { label: `${p.p65.toLocaleString()} – ${p.p75.toLocaleString()}`, color: '#08519C' },
        { label: `${p.p50.toLocaleString()} – ${p.p65.toLocaleString()}`, color: '#2171B5' },
        { label: `${p.p35.toLocaleString()} – ${p.p50.toLocaleString()}`, color: '#4292C6' },
        { label: `${p.p25.toLocaleString()} – ${p.p35.toLocaleString()}`, color: '#6BAED6' },
        { label: `Menor a ${p.p25.toLocaleString()}`, color: '#C6DBEF' }
      ]
    }
  }
}
</script>

<style scoped>
.legend {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: white;
  padding: 12px 14px;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  font-size: 15px;
  color: #333;
  min-width: 180px;
   z-index: 1000;
}

.legend h4 {
  margin: 0 0 8px;
  font-size: 14px;
  font-weight: 600;
  text-align: center;
}

.legend ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.legend li {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}

.legend li:last-child {
  margin-bottom: 0;
}

.color-box {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  margin-right: 8px;
  border: 1px solid #ccc;
}

.label {
  font-size: 12px;
  font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
}
</style>
