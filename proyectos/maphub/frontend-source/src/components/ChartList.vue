<template>
    <article class="chart-card">
      <h4>{{ title }}</h4>
      <div v-if="items.length" class="bars">
        <div v-for="item in items" :key="item[labelKey]" class="bar-row">
          <span>{{ item[labelKey] || 'N/D' }}</span>
          <div><i :style="{ width: Math.max(8, (Number(item[valueKey] || 0) / max) * 100) + '%' }"></i></div>
          <strong>{{ item[valueKey] }}{{ suffix }}</strong>
        </div>
      </div>
      <p v-else>Sin datos suficientes.</p>
    </article>
  </template>
<script>
export default {
  props: {
    title: String,
    items: { type: Array, default: () => [] },
    labelKey: String,
    valueKey: String,
    suffix: { type: String, default: '' }
  },
  computed: {
    max() {
      return Math.max(...this.items.map(i => Number(i[this.valueKey] || 0)), 1)
    }
  }
}
</script>
<style scoped>.chart-card{padding:12px;background:#f8fafc;border-radius:8px}.chart-card h4{font-size:13px;color:#1f3a4a}.bar-row{display:grid;grid-template-columns:90px 1fr 40px;gap:8px;align-items:center;font-size:11px;margin:8px 0}.bar-row div{height:8px;background:#e0e9ef;border-radius:5px;overflow:hidden}.bar-row i{display:block;height:100%;background:#229b89}.bar-row span{overflow:hidden;text-overflow:ellipsis}</style>
