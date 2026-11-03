"""Transcripción local por lotes con faster-whisper; exporta TXT, JSON y SRT."""
import argparse
import json
from pathlib import Path

EXTENSIONS = {'.wav', '.mp3', '.mp4', '.m4a', '.ogg', '.flac', '.webm'}


def timestamp(seconds):
    milliseconds = max(0, round(seconds * 1000))
    hours, remainder = divmod(milliseconds, 3600000)
    minutes, remainder = divmod(remainder, 60000)
    seconds, milliseconds = divmod(remainder, 1000)
    return f'{hours:02}:{minutes:02}:{seconds:02},{milliseconds:03}'


def export(result, destination):
    destination.parent.mkdir(parents=True, exist_ok=True)
    destination.with_suffix('.txt').write_text(result['text'], encoding='utf8')
    destination.with_suffix('.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf8')
    subtitles = [f"{index}\n{timestamp(seg['start'])} --> {timestamp(seg['end'])}\n{seg['text']}\n" for index, seg in enumerate(result['segments'], 1)]
    destination.with_suffix('.srt').write_text('\n'.join(subtitles), encoding='utf8')


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('entrada', type=Path, help='Archivo o carpeta de audios')
    parser.add_argument('--salida', type=Path, default=Path('resultados'))
    parser.add_argument('--modelo', default='small', help='Nombre del modelo o ruta local')
    parser.add_argument('--idioma', default='es', help='Código de idioma, o auto')
    parser.add_argument('--sobrescribir', action='store_true')
    args = parser.parse_args(argv)
    if not args.entrada.exists():
        parser.error('La entrada no existe.')
    files = [args.entrada] if args.entrada.is_file() else sorted(p for p in args.entrada.iterdir() if p.suffix.lower() in EXTENSIONS)
    if not files or any(p.suffix.lower() not in EXTENSIONS for p in files):
        parser.error('No hay archivos de audio/video compatibles.')
    from faster_whisper import WhisperModel
    model = WhisperModel(args.modelo, device='cpu', compute_type='int8')
    failures = []
    for index, path in enumerate(files, 1):
        # Incluye la extensión original para evitar colisiones entre audio.mp3 y audio.wav.
        destination = args.salida / (path.name + '.transcripcion')
        if not args.sobrescribir and any(destination.with_suffix(ext).exists() for ext in ('.txt', '.json', '.srt')):
            print(f'Omitido (ya existe): {path.name}')
            continue
        print(f'[{index}/{len(files)}] {path.name}', flush=True)
        try:
            segments, info = model.transcribe(str(path), language=None if args.idioma == 'auto' else args.idioma, beam_size=5, vad_filter=True)
            collected = [dict(start=round(seg.start, 3), end=round(seg.end, 3), text=seg.text.strip()) for seg in segments]
            export(dict(file=path.name, language=info.language, duration=info.duration, text=' '.join(seg['text'] for seg in collected), segments=collected), destination)
        except Exception as error:
            failures.append(path.name)
            print(f'No se pudo procesar {path.name}: {error}')
    return 1 if failures else 0


if __name__ == '__main__':
    raise SystemExit(main())
