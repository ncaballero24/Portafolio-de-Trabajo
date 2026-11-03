-- Datos ficticios para una base VACÍA. No ejecutar sobre datos operativos.
INSERT INTO poligonos(nombre,geom) VALUES('Sector demostración',ST_GeomFromText('POLYGON((-96.22 19.10,-96.08 19.10,-96.08 19.23,-96.22 19.23,-96.22 19.10))',4326));
INSERT INTO patrullas(geom,poligono_id) SELECT ST_SetSRID(ST_MakePoint(-96.16 + n*0.004,19.15+n*0.003),4326)::geography,1 FROM generate_series(1,6) n;
INSERT INTO incidencias(tipo,severidad,descripcion,zona,geom,timestamp)
SELECT (ARRAY['Asistencia vial','Accidente','Apoyo ciudadano','Obstrucción'])[1+n%4],1+n%5,
'Incidencia ficticia para comprobar el flujo de atención. Caso '||n,'norte-oriente',
ST_SetSRID(ST_MakePoint(-96.16+n*0.001,19.15+n*0.001),4326)::geography,
NOW()-n*INTERVAL '12 minutes' FROM generate_series(1,18) n;
