export function calcularPercentiles(dataset, campo) {
  // 1. Extraer los valores del campo indicado
  const valores = Object.values(dataset)
    .map(estado => estado[campo])
    .filter(v => typeof v === 'number')
    .sort((a, b) => a - b)

  if (valores.length === 0) {
    throw new Error(`El campo "${campo}" no existe o no es numérico`)
  }

  // 2. Función para calcular un percentil
  function percentil(p) {
    const n = valores.length
    const index = (p / 100) * (n - 1)
    const lower = Math.floor(index)
    const upper = Math.ceil(index)

    if (lower === upper) return valores[lower]

    const peso = index - lower
    return valores[lower] * (1 - peso) + valores[upper] * peso
  }

  // 3. Percentiles solicitados
  return {
    p25: percentil(25),
    p35: percentil(35),
    p50: percentil(50), // mediana
    p65: percentil(65),
    p75: percentil(75)
  }
}
