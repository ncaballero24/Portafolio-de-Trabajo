@echo off
cd /d "%~dp0"
echo Ocelote - Comunidad CETis 15
echo Abre http://127.0.0.1:8000 en tu navegador.
echo Manten esta ventana abierta. Para detener el foro: Ctrl+C.
python server.py --port 8000
pause
