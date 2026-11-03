import urllib.request,json,http.cookiejar,os
if os.environ.get("MAPHUB_TEST_ALLOW_WRITES") != "1":
 raise SystemExit("Usa solo una base de pruebas y define MAPHUB_TEST_ALLOW_WRITES=1")
base=os.environ['MAPHUB_TEST_URL'].rstrip('/')+'/terrestre/'
jar=http.cookiejar.CookieJar();client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
def call(path,data=None):
 req=urllib.request.Request(base+path,data=None if data is None else json.dumps(data).encode(),headers={'Content-Type':'application/json'})
 try:
  with client.open(req,timeout=15) as r:return r.status,json.load(r)
 except urllib.error.HTTPError as e:return e.code,json.load(e)
for path in ['api_me.php','api_patrullas.php','api_incidencias.php','api_dashboard.php','api_predictivo.php']:
 code,data=call(path);print(path,code,'count',len(data) if isinstance(data,list) else len(data.get('data',[])))
assert call('api_actualizar_posicion.php',{'id':1,'lat':19,'lng':-96})[0]==403
assert call('api_login.php',{'username':os.environ['MAPHUB_TEST_USER'],'password':os.environ['MAPHUB_TEST_PASSWORD']})[1]['ok']
code,new=call('api_crear_incidencia.php',{'tipo':'Prueba automatizada','descripcion':'Incidencia ficticia de validación integrada','severidad':2,'lat':19.16,'lng':-96.14});assert code==200,new
id=new['id'];code,dispatch=call('api_despacho.php',{'incidencia_id':id});assert dispatch['success'],dispatch
assert call('api_despacho.php',{'incidencia_id':id})[0]==409
assert call('api_resolver_incidencia.php',{'id':id})[1]['success']
assert call('api_resolver_incidencia.php',{'id':id})[0]==409
code,history=call('api_historial_patrulla.php?patrulla_id='+str(dispatch['patrulla_id']));assert any(str(i['incidencia_id'])==str(id) for i in history['data']),history
print('Flujo crear-asignar-resolver-historial OK; duplicados y acceso sin sesión bloqueados.')
