# Preparación del arte

Referencias: las cuatro imágenes de avatares y cosméticos proporcionadas por el usuario en este chat. Originales conservados en static/assets/shop/*-original.png.

Se utilizó ImageGen para quitar los fondos a catalogo.png, base.png, bases.png y conjuntos.png. Los sprites se muestran mediante recortes SVG definidos en catalog.json; no se alteran los originales. La composición es estática.

## Instrucciones de edición utilizadas

- Catálogo: conservar los sprites, posición, tamaño, colores y proporción del lienzo; convertir a alfa transparente el fondo crema, los bordes, textos, separadores, paleta y tachón azul. Preservar píxeles blancos internos de los artículos. No agregar objetos ni tablero de transparencia.
- Base neutral: quitar únicamente el fondo blanco; preservar cuerpo, postura, ropa gris, posición y lienzo.
- Par de estudiantes: quitar únicamente el fondo blanco; preservar ambos personajes, cabello, ropa gris, tamaño, posición y proporción.
- Conjuntos: quitar únicamente el fondo crema; preservar ambos personajes completos, ropa, cabello, posiciones y proporción.

Las salidas generadas también se conservaron en la carpeta generated_images de Codex. Esta copia local permite ejecutar el foro sin depender de esa carpeta.
