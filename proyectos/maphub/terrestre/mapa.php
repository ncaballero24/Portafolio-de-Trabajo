<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require 'db.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Monitoreo Terrestre C4</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css"/>

<style>

html, body{
    height:100%;
    margin:0;
    font-family:Arial;
}

#map{
    width:100%;
    height:100vh;
}

#controlVista{
    position:absolute;
    top:10px;
    left:60px;
    z-index:1000;
    background:white;
    padding:10px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,.3);
}

#btnIncidencias{
    position:absolute;
    top:10px;
    right:20px;
    z-index:1000;
    background:#0b5ed7;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
}

#panelIncidencias{
    display:none;
    position:absolute;
    top:60px;
    right:20px;
    width:950px;
    max-height:550px;
    overflow:auto;
    background:white;
    z-index:1000;
    padding:15px;
    border-radius:10px;
    box-shadow:0 0 15px rgba(0,0,0,.35);
}

#panelIncidencias table{
    width:100%;
    border-collapse:collapse;
}

#panelIncidencias th{
    background:#0b5ed7;
    color:white;
    padding:8px;
}

#panelIncidencias td{
    border-bottom:1px solid #ddd;
    padding:8px;
    vertical-align:middle;
}

.preview-evidencia{
    width:70px;
    height:50px;
    object-fit:cover;
    border-radius:5px;
    border:1px solid #ccc;
    cursor:pointer;
    display:block;
    margin-bottom:5px;
}

.btn-borrar-evidencia{
    background:#dc3545;
    color:white;
    border:none;
    padding:5px 8px;
    border-radius:5px;
    cursor:pointer;
    margin-bottom:5px;
}

.filtros-incidencias{
    display:flex;
    gap:10px;
    margin-bottom:15px;
}

.filtros-incidencias select{
    padding:7px;
    border-radius:6px;
    border:1px solid #ccc;
}

.estado-pendiente{
    color:red;
    font-weight:bold;
}

.estado-atendida{
    color:green;
    font-weight:bold;
}

#modalImagen{
    display:none;
    position:fixed;
    z-index:5000;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.75);
    justify-content:center;
    align-items:center;
}

#modalImagen img{
    max-width:85%;
    max-height:85%;
    border-radius:10px;
}

#cerrarModal{
    position:absolute;
    top:20px;
    right:35px;
    color:white;
    font-size:40px;
    cursor:pointer;
}

.boton-asignar{
    background:#0b5ed7;
    color:white;
    border:none;
    padding:8px 10px;
    border-radius:5px;
    cursor:pointer;
}

</style>
</head>

<body>

<div id="controlVista">
    <label>Visualización:</label>

    <select id="modoVista">
        <option value="markers">Markers</option>
        <option value="cluster">Cluster</option>
        <option value="heatmap">Heatmap</option>
    </select>
</div>

<button id="btnIncidencias" onclick="togglePanelIncidencias()">
    📋 Ver incidencias
</button>

<div id="panelIncidencias">

<h2>Incidencias registradas</h2>

<div class="filtros-incidencias">

<select id="filtroTipo" onchange="cargarTablaIncidencias()">
    <option value="todos">Todos los tipos</option>
    <option value="choque">Choque</option>
    <option value="asalto">Asalto</option>
    <option value="robo">Robo</option>
    <option value="incendio">Incendio</option>
    <option value="vehiculo_descompuesto">Vehículo descompuesto</option>
</select>

<select id="filtroZona" onchange="cargarTablaIncidencias()">
    <option value="todos">Todas las zonas</option>
    <option value="norte-oriente">Norte-oriente</option>
    <option value="norte-poniente">Norte-poniente</option>
    <option value="sur-oriente">Sur-oriente</option>
    <option value="sur-poniente">Sur-poniente</option>
</select>

<select id="filtroSeveridad" onchange="cargarTablaIncidencias()">
    <option value="todos">Todas las severidades</option>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
</select>

</div>

<table>

<thead>
<tr>
    <th>ID</th>
    <th>Tipo</th>
    <th>Severidad</th>
    <th>Zona</th>
    <th>Descripción</th>
    <th>Estado</th>
    <th>Evidencia</th>
</tr>
</thead>

<tbody id="tablaIncidencias"></tbody>

</table>

</div>

<div id="map"></div>

<div id="modalImagen" onclick="cerrarImagenGrande()">
    <span id="cerrarModal">&times;</span>
    <img id="imagenGrande">
</div>

<div id="modalHistorial" style="
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,.7);
z-index:5000;
justify-content:center;
align-items:center;
">

<div style="
width:900px;
max-height:80vh;
overflow:auto;
background:white;
padding:20px;
border-radius:10px;
">

<h2 id="tituloHistorial"></h2>

<table style="width:100%;border-collapse:collapse;">

<thead>
<tr style="background:#0b5ed7;color:white;">
    <th>Incidencia</th>
    <th>Tipo</th>
    <th>Zona</th>
    <th>Severidad</th>
    <th>Tiempo</th>
    <th>Fecha</th>
    <th>Evidencia</th>
</tr>
</thead>

<tbody id="bodyHistorial"></tbody>

</table>

<br>

<button onclick="cerrarHistorial()"
style="
background:red;
color:white;
border:none;
padding:10px;
border-radius:5px;
cursor:pointer;
">
Cerrar
</button>

</div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<script>

const map = L.map('map').setView([19.1738, -96.1342], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap'
}).addTo(map);

function iconoPatrulla(color='blue'){

    return L.divIcon({
        html:`
            <div style="
                width:22px;
                height:22px;
                border-radius:50%;
                background:${color};
                border:3px solid white;
                box-shadow:0 0 12px ${color};
            "></div>
        `,
        className:'',
        iconSize:[22,22]
    });
}

function crearIcono(severidad){

    severidad = parseInt(severidad);

    let color='yellow';
    let emoji='⚠️';

    if(severidad===3){
        color='orange';
        emoji='🚧';
    }

    if(severidad>=4){
        color='red';
        emoji='🚨';
    }

    return L.divIcon({
        html:`
        <div style="
            background:${color};
            width:42px;
            height:42px;
            border-radius:50%;
            border:3px solid white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
        ">
            ${emoji}
        </div>
        `,
        className:'',
        iconSize:[42,42]
    });
}

let patrullas = {};
let markersLayer = L.layerGroup();
let clusterLayer = L.markerClusterGroup();
let heatLayer = null;

async function cargarPatrullas(){

    const res = await fetch("api_patrullas.php");
    const data = await res.json();

    data.forEach(p=>{

        if(!patrullas[p.id]){

            const marker = L.marker([p.lat,p.lng],{
                icon:iconoPatrulla('blue')
            }).addTo(map);

            marker.on("click",()=>{
                abrirHistorialPatrulla(p.id);
            });

            patrullas[p.id]={
                ...p,
                marker,
                lat:parseFloat(p.lat),
                lng:parseFloat(p.lng)
            };

        }else{

            patrullas[p.id].marker.setLatLng([p.lat,p.lng]);
        }
    });
}

async function cargarIncidencias(){

    markersLayer.clearLayers();
    clusterLayer.clearLayers();

    const res = await fetch("api_incidencias.php");
    const response = await res.json();

    response.data.forEach(i=>{

        if(i.estado === 'Atendida') return;

        const marker = L.marker([i.latitud,i.longitud],{
            icon:crearIcono(i.severidad)
        });

        marker.bindPopup(`
            <div>
                <b>${i.tipo}</b><br>
                Severidad: ${i.severidad}<br>
                ${i.descripcion}<br><br>

                <button class="boton-asignar"
                onclick="despachar(${i.id},${i.latitud},${i.longitud})">
                    Asignar patrulla
                </button>
            </div>
        `);

        markersLayer.addLayer(marker);
        clusterLayer.addLayer(marker);
    });

    map.addLayer(markersLayer);
}

async function cargarHeatmap(){

    const res = await fetch("api_heatmap.php?minutos=99999&limit=1000");
    const response = await res.json();

    let puntos = response.data.map(p=>[
        parseFloat(p[0]),
        parseFloat(p[1]),
        parseFloat(p[2])||1
    ]);

    if(heatLayer){
        map.removeLayer(heatLayer);
    }

    heatLayer = L.heatLayer(puntos,{
        radius:50,
        blur:30,
        maxZoom:17
    });
}

async function despachar(id, lat, lng){

    const res = await fetch("api_despacho.php",{
        method:"POST",
        headers:{
            "Content-Type":"application/json"
        },
        body:JSON.stringify({lat,lng})
    });

    const data = await res.json();

    if(!data.success){
        alert("No hay patrullas");
        return;
    }

    const patrulla = patrullas[data.patrulla_id];

    trazarRuta(patrulla,id,lat,lng);
}

async function trazarRuta(patrulla, incidenciaId, latDestino, lngDestino){

    const res = await fetch("api_ruta.php",{
        method:"POST",
        headers:{
            "Content-Type":"application/json"
        },
        body:JSON.stringify({
            lat1:patrulla.lat,
            lng1:patrulla.lng,
            lat2:latDestino,
            lng2:lngDestino
        })
    });

    const data = await res.json();

    if(!data.routes) return;

    const geojson = data.routes[0].geometry;

    const rutaLayer = L.geoJSON(geojson,{
        style:{
            color:'blue',
            weight:5,
            dashArray:"10,5"
        }
    }).addTo(map);

    let coords = geojson.coordinates;

    let i=0;

    const intervalo = setInterval(()=>{

        if(i>=coords.length){

            clearInterval(intervalo);

            map.removeLayer(rutaLayer);

            fetch("api_resolver_incidencia.php",{
                method:"POST",
                headers:{
                    "Content-Type":"application/json"
                },
                body:JSON.stringify({
                    id:incidenciaId,
                    patrulla_id:patrulla.id
                })
            });

            cargarIncidencias();
            cargarTablaIncidencias();

            return;
        }

        const [lng,lat] = coords[i];

        patrulla.marker.setLatLng([lat,lng]);

        patrulla.lat = lat;
        patrulla.lng = lng;

        i++;

    },40);
}

function togglePanelIncidencias(){

    const panel = document.getElementById("panelIncidencias");

    if(panel.style.display==="none" || panel.style.display===""){

        panel.style.display="block";
        cargarTablaIncidencias();

    }else{

        panel.style.display="none";
    }
}

async function cargarTablaIncidencias(){

    const res = await fetch("api_incidencias.php");
    const response = await res.json();

    let incidencias = response.data;

    const tipo = document.getElementById("filtroTipo").value;
    const zona = document.getElementById("filtroZona").value;
    const severidad = document.getElementById("filtroSeveridad").value;

    if(tipo !== "todos"){
        incidencias = incidencias.filter(i=>i.tipo===tipo);
    }

    if(zona !== "todos"){
        incidencias = incidencias.filter(i=>i.zona===zona);
    }

    if(severidad !== "todos"){
        incidencias = incidencias.filter(i=>String(i.severidad)===severidad);
    }

    const tbody = document.getElementById("tablaIncidencias");

    tbody.innerHTML="";

    incidencias.forEach(i=>{

        const atendida = i.estado === "Atendida";

        const estadoTexto = atendida
            ? `Atendida por patrulla ${i.patrulla_atendio}`
            : "Pendiente";

        const claseEstado = atendida
            ? "estado-atendida"
            : "estado-pendiente";

        tbody.innerHTML += `
        <tr>

            <td>${i.id}</td>
            <td>${i.tipo}</td>
            <td>${i.severidad}</td>
            <td>${i.zona}</td>
            <td>${i.descripcion}</td>

            <td class="${claseEstado}">
                ${estadoTexto}
            </td>

            <td>

            ${
                i.evidencia_imagen

                ? `
                    <img
                        src="${i.evidencia_imagen}"
                        class="preview-evidencia"
                        onclick="verImagenGrande('${i.evidencia_imagen}')"
                    >

                    <button
                    class="btn-borrar-evidencia"
                    onclick="borrarEvidencia(${i.id})">
                        Borrar imagen
                    </button>
                  `

                : `Sin evidencia`
            }

            <br>

            <input
            type="file"
            accept="image/*"
            onchange="subirEvidencia(${i.id},this)"
            >

            </td>

        </tr>
        `;
    });
}

async function subirEvidencia(id,input){

    const archivo = input.files[0];

    if(!archivo) return;

    const formData = new FormData();

    formData.append("id",id);
    formData.append("imagen",archivo);

    const res = await fetch("api_subir_evidencia.php",{
        method:"POST",
        body:formData
    });

    const data = await res.json();

    if(data.success){

        alert("Imagen subida");
        cargarTablaIncidencias();

    }else{

        alert("Error");
    }
}

async function borrarEvidencia(id){

    if(!confirm("¿Borrar evidencia?")) return;

    const res = await fetch("api_borrar_evidencia.php",{
        method:"POST",
        headers:{
            "Content-Type":"application/json"
        },
        body:JSON.stringify({id})
    });

    const data = await res.json();

    if(data.success){

        cargarTablaIncidencias();

    }else{

        alert("Error");
    }
}

function verImagenGrande(ruta){

    document.getElementById("imagenGrande").src = ruta;

    document.getElementById("modalImagen").style.display="flex";
}

function cerrarImagenGrande(){

    document.getElementById("modalImagen").style.display="none";
}

async function abrirHistorialPatrulla(id){

    const modal = document.getElementById("modalHistorial");
    const body = document.getElementById("bodyHistorial");

    document.getElementById("tituloHistorial").innerHTML =
        "Historial patrulla " + id;

    body.innerHTML = `
    <tr>
        <td colspan="7">Cargando...</td>
    </tr>
    `;

    modal.style.display = "flex";

    const res = await fetch(
        "api_historial_patrulla.php?patrulla_id=" + id
    );

    const response = await res.json();

    body.innerHTML = "";

    if(!response.data.length){

        body.innerHTML = `
        <tr>
            <td colspan="7">
                Sin historial
            </td>
        </tr>
        `;

        return;
    }

    response.data.forEach(i=>{

        let tiempo = Math.floor(i.tiempo_respuesta);

        let minutos = Math.floor(tiempo / 60);
        let segundos = tiempo % 60;

        body.innerHTML += `
        <tr>

            <td>${i.incidencia_id}</td>
            <td>${i.tipo}</td>
            <td>${i.zona}</td>
            <td>${i.severidad}</td>

            <td>
                ${minutos}m ${segundos}s
            </td>

            <td>${i.fecha_resolucion}</td>

            <td>

            ${
                i.evidencia_imagen

                ? `
                    <img
                    src="${i.evidencia_imagen}"
                    class="preview-evidencia"
                    onclick="verImagenGrande('${i.evidencia_imagen}')">
                  `

                : 'Sin evidencia'
            }

            </td>

        </tr>
        `;
    });
}

function cerrarHistorial(){

    document.getElementById("modalHistorial").style.display="none";
}

cargarPatrullas();
cargarIncidencias();
cargarHeatmap();

setInterval(cargarPatrullas,5000);
setInterval(cargarIncidencias,5000);

</script>
</body>
</html>
