import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

function text(tag, value, className='') { const el=document.createElement(tag); el.textContent=value; el.className=className; return el; }
function inside(point, feature) {
    const [y,x]=point;
    const ring = coords => {let found=false;for(let i=0,j=coords.length-1;i<coords.length;j=i++){const [xi,yi]=coords[i],[xj,yj]=coords[j];if((yi>y)!==(yj>y) && x<(xj-xi)*(y-yi)/(yj-yi)+xi)found=!found;}return found;};
    const polygons=feature.geometry.type==='Polygon'?[feature.geometry.coordinates]:feature.geometry.coordinates;
    return polygons.some(p=>ring(p[0])&&!p.slice(1).some(ring));
}
function baseMap(element) {
    const feature=JSON.parse(element.dataset.boundary);
    const map=L.map(element,{scrollWheelZoom:false}).setView(feature.properties.center,13);
    const tiles=L.tileLayer(element.dataset.tileUrl,{attribution:element.dataset.tileAttribution,maxZoom:19}).addTo(map);
    const outline=L.geoJSON(feature,{interactive:false,style:{color:'#477d67',weight:2,fillColor:'#477d67',fillOpacity:0.035}}).addTo(map);
    map.fitBounds(outline.getBounds(),{padding:[15,15],maxZoom:13});
    let warned=false;
    tiles.on('tileerror',()=>{if(warned)return;warned=true;const warning=text('p','No se pudo cargar alguna tesela del mapa base. Revise la conexión; los formularios y coordenadas siguen disponibles.','map-network-warning');element.after(warning);});
    return {map,feature,outline,tiles};
}
function pin(verified) {return L.divIcon({className:'incident-pin '+(verified?'verified-pin':'pending-pin'),html:'<span></span>',iconSize:[24,24],iconAnchor:[12,12]});}
const priorities=['sin_asignar','baja','media','alta','urgente'];
function priorityCode(value){return priorities.includes(value)?value:'sin_asignar';}
function priorityPin(priority,verified){
    const code=priorityCode(priority),symbol={urgente:'!',alta:'A',media:'M',baja:'B',sin_asignar:'?'}[code];
    return L.divIcon({className:`priority-pin priority-${code} ${verified?'is-verified':'is-unverified'}`,html:`<span>${symbol}</span>`,iconSize:[32,32],iconAnchor:[16,16]});
}
async function initialize() {
    if(!document.querySelector('[data-location-map],[data-incident-map]')) return;
    window.L=L;
    document.querySelectorAll('[data-location-map]').forEach(element=>{
        const {map,feature}=baseMap(element); const shell=element.closest('[data-location-workspace]');
        const editable=element.dataset.editable==='1'; const latInput=shell.querySelector('#latitude'),lngInput=shell.querySelector('#longitude'),source=shell.querySelector('#location_source');
        const message=shell.querySelector('[data-location-message]'); let marker=null;
        function setPoint(lat,lng,update=true) {
            if(!Number.isFinite(lat)||!Number.isFinite(lng)||!inside([lat,lng],feature)){message.dataset.state='error';message.textContent='El punto está fuera del límite de Amarilis. Acerque el mapa y seleccione dentro del contorno verde. La ubicación anterior se conserva.';return false;}
            if(!marker){marker=L.marker([lat,lng],{draggable:editable,title:editable?"Arrastre para ajustar la ubicación":"Ubicación del expediente",alt:"Ubicación seleccionada",icon:pin(!editable&&element.dataset.verified==='1')}).addTo(map);marker.on('dragend',()=>{const p=marker.getLatLng();if(!setPoint(p.lat,p.lng)){marker.setLatLng([Number(latInput.value),Number(lngInput.value)]);}});}else marker.setLatLng([lat,lng]);
            if(editable&&update){latInput.value=lat.toFixed(7);lngInput.value=lng.toFixed(7);source.value='mapa';}
            message.dataset.state='selected';
            message.textContent=(editable?'Ubicación seleccionada: ':'Coordenadas del expediente: ')+lat.toFixed(7)+', '+lng.toFixed(7)+'. '+(editable?'Arrastre el marcador para ajustar el lugar. Pendiente de verificación.':element.dataset.verified==='1'?'Ubicación verificada en el expediente.':'Pendiente de verificación por personal autorizado.');
            return true;
        }
        if(element.dataset.lat!=='' && element.dataset.lng!==''){setPoint(Number(element.dataset.lat),Number(element.dataset.lng),false);map.setView([Number(element.dataset.lat),Number(element.dataset.lng)],16);}
        if(!editable) {if(!marker)message.textContent='Este expediente no tiene coordenadas. No se coloca un punto supuesto en el mapa.';return;}
        map.on('click',e=>setPoint(e.latlng.lat,e.latlng.lng));
        [latInput,lngInput].forEach(input=>input.addEventListener('change',()=>{if(latInput.value!==''&&lngInput.value!==''){if(setPoint(Number(latInput.value),Number(lngInput.value),false)){source.value='coordenadas';map.panTo([Number(latInput.value),Number(lngInput.value)]);}else{if(marker)map.removeLayer(marker);marker=null;message.textContent='Coordenadas fuera del límite de Amarilis. Corríjalas o seleccione un punto dentro del contorno verde.';}}else{if(marker)map.removeLayer(marker);marker=null;message.dataset.state='error';message.textContent='Complete latitud y longitud o seleccione un punto en el mapa.';}}));
        shell.querySelector('[data-clear-location]').addEventListener('click',()=>{if(marker)map.removeLayer(marker);marker=null;latInput.value='';lngInput.value='';source.value='';delete message.dataset.state;message.textContent='Sin ubicación. Acerque el mapa con + y haga clic dentro del contorno verde.';});
        const search=shell.querySelector('[data-place-search]'),input=shell.querySelector('#map-search-input'),results=shell.querySelector('[data-place-results]');
        search.addEventListener('click',async()=>{
            if(input.value.trim().length<3){results.replaceChildren(text('p','Escriba al menos 3 caracteres.'));return;}
            search.disabled=true;results.replaceChildren(text('p','Buscando dirección en OpenStreetMap…'));
            try{const url=new URL(search.dataset.url);url.searchParams.set('q',input.value.trim());const response=await fetch(url,{headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok)throw new Error(data.message||'No se pudo completar la búsqueda.');results.replaceChildren();
                if(!data.results.length)results.append(text('p','No se encontraron lugares dentro del ámbito. Puede seleccionar el punto manualmente.'));
                data.results.forEach(place=>{const button=text('button',place.label);button.type='button';button.addEventListener('click',()=>{if(setPoint(place.latitude,place.longitude)){map.setView([place.latitude,place.longitude],17);results.replaceChildren();}});results.append(button);});
            }catch(e){results.replaceChildren(text('p',e.message));}finally{search.disabled=false;}
        });
        input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();search.click();}});
    });
    if(!document.querySelector('[data-incident-map]')) return;
    await import('leaflet.heat');
    await import('leaflet.markercluster');
    document.querySelectorAll('[data-incident-map]').forEach(element=>{
        const {map}=baseMap(element),shell=element.closest('[data-incident-map-shell]');
        const markers=L.markerClusterGroup({showCoverageOnHover:false,maxClusterRadius:40,disableClusteringAtZoom:17,iconCreateFunction:cluster=>{
            const rank=Math.max(...cluster.getAllChildMarkers().map(m=>priorities.indexOf(priorityCode(m.options.incidentPriority))));
            return L.divIcon({className:`priority-cluster priority-${priorities[rank]}`,html:`<span>${cluster.getChildCount()}</span>`,iconSize:[40,40]});
        }});
        const heat=L.heatLayer([],{radius:24,blur:18,maxZoom:18,max:1,gradient:{0.15:'#316fb5',0.4:'#33bdb2',0.6:'#c1d44c',0.8:'#e8a439',1:'#c74939'}});
        L.control.layers({}, {'Marcadores de reportes':markers,'Calor de reportes verificados':heat},{collapsed:true}).addTo(map);
        let mode='combined';let abort=null;let firstFit=true;
        function layers(){if(map.hasLayer(markers))map.removeLayer(markers);if(map.hasLayer(heat))map.removeLayer(heat);if(mode!=='heat')map.addLayer(markers);if(mode!=='markers')map.addLayer(heat);shell.querySelectorAll('[data-map-mode]').forEach(button=>button.classList.toggle('active',button.dataset.mapMode===mode));}
        shell.querySelectorAll('[data-map-mode]').forEach(button=>button.addEventListener('click',()=>{mode=button.dataset.mapMode;layers();}));layers();
        const form=document.querySelector('[data-map-filters]');
        async function refresh(filters){
            if(abort)abort.abort();abort=new AbortController();const message=shell.querySelector('[data-map-message]'),totals=shell.querySelector('[data-map-totals]');
            message.textContent='Cargando incidencias desde la base de datos…';
            try{const url=new URL(element.dataset.url);Object.entries(filters).forEach(([k,v])=>{if(v!==''&&v!==null)url.searchParams.set(k,v);});const response=await fetch(url,{headers:{Accept:'application/json'},signal:abort.signal});const data=await response.json();if(!response.ok)throw new Error(data.message||'No se pudo cargar el mapa.');
                markers.clearLayers();const points=[];
                data.features.forEach(f=>{const p=f.properties,[lng,lat]=f.geometry.coordinates;const verified=p.verified&&p.location_verified;
                    if(verified)points.push([lat,lng,1]);
                    const popup=document.createElement('div');popup.className='incident-popup';popup.append(text('small',p.code),text('strong',p.title),text('p',p.address),text('p',p.state+' · '+p.priority),text('small',verified?'Reporte y ubicación verificados':'Pendiente de verificación'));const link=text('a','Abrir expediente →');link.href=p.url;popup.append(link);
                    markers.addLayer(L.marker([lat,lng],{icon:priorityPin(p.priority,verified),incidentPriority:p.priority,title:`${p.code} · Prioridad: ${p.priority} · ${verified?'Verificado':'Pendiente de verificación'}`,alt:`Incidencia ${p.priority}`}).bindPopup(popup));});
                if(firstFit&&data.features.length){map.fitBounds(markers.getBounds(),{padding:[35,35],maxZoom:16});firstFit=false;}
                heat.setLatLngs(points);message.textContent=data.meta.message||data.meta.scope;
                if(totals)totals.textContent=`${data.meta.reports} reportes · ${data.meta.verified_geolocated} geolocalizados verificados · ${data.meta.unique_sites} sitios únicos asociados · ${data.meta.without_coordinates} sin coordenadas`;
            }catch(e){if(e.name==='AbortError')return;markers.clearLayers();heat.setLatLngs([]);message.textContent='Mapa sin datos actualizados: '+e.message;if(totals)totals.textContent='Carga no disponible';}
        }
        if(form)form.addEventListener('submit',e=>{e.preventDefault();const filters=Object.fromEntries(new FormData(form));refresh(filters);const page=new URL(location.href);page.search=new URLSearchParams(filters);history.replaceState(null,'',page);});
        refresh(JSON.parse(element.dataset.filters||'{}'));
    });
}
initialize().catch(()=>{document.querySelectorAll('[data-location-message],[data-map-message]').forEach(el=>el.textContent='No se pudo iniciar el mapa. Puede introducir coordenadas reales y continuar con el formulario.');});
document.querySelectorAll('[data-photo-preview]').forEach(input=>input.addEventListener('change',()=>{
    const list=input.closest('section').querySelector('[data-photo-preview-list]');list.replaceChildren();
    Array.from(input.files).slice(0,3).forEach(file=>{if(!file.type.startsWith('image/'))return;const img=document.createElement('img');img.alt='Vista previa local: '+file.name;const url=URL.createObjectURL(file);img.src=url;img.onload=()=>URL.revokeObjectURL(url);list.append(img);});
}));
