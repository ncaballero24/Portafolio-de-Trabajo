# Transcriptor local de audios

Herramienta Python por lotes basada en el trabajo original de transcripción. Procesa audio/video en CPU con faster-whisper y exporta texto, segmentos JSON y subtítulos SRT. No incluye grabaciones ni transcripciones privadas.

```powershell
python -m venv .venv
.venv\Scripts\python -m pip install -r requirements.txt
.venv\Scripts\python transcribir.py carpeta-de-audios --salida resultados --idioma es
```

Para un solo archivo: `python transcribir.py grabacion.mp3`. `--idioma auto` detecta el idioma. `--modelo` acepta un modelo o una carpeta local. El primer uso de un nombre de modelo descarga sus pesos; después puede usarse la copia local. No envía el audio a una API. La transcripción puede contener errores y debe revisarse.

No sobrescribe resultados por defecto; `--sobrescribir` lo habilita. Continúa con el siguiente archivo si uno falla y devuelve código 1 si hubo fallos. PyAV, instalado por la dependencia, decodifica los formatos habituales.

Pruebas: `python -m unittest test_transcribir -v`. Las pruebas verifican exportación y subtítulos sin descargar modelos; no sustituyen la revisión de precisión con audio real.
