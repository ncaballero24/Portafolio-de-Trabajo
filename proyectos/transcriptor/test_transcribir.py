import json
import tempfile
import unittest
from pathlib import Path
from transcribir import export, timestamp

class ExportTests(unittest.TestCase):
    def test_time_and_files(self):
        self.assertEqual(timestamp(3661.125), '01:01:01,125')
        self.assertEqual(timestamp(-1), '00:00:00,000')
        with tempfile.TemporaryDirectory() as directory:
            path=Path(directory)/'prueba.mp3.transcripcion'
            result={'text':'Hola, comunidad.','segments':[{'start':0,'end':1.5,'text':'Hola, comunidad.'}]}
            export(result,path)
            self.assertEqual(path.with_suffix('.txt').read_text(encoding='utf8'),result['text'])
            self.assertEqual(json.loads(path.with_suffix('.json').read_text(encoding='utf8')),result)
            self.assertIn('00:00:00,000 --> 00:00:01,500',path.with_suffix('.srt').read_text(encoding='utf8'))

if __name__=='__main__':unittest.main()
