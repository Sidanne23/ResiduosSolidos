import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
import './incidents';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);
Chart.defaults.font.family = "'Segoe UI', Arial, sans-serif";
Chart.defaults.font.size = 10;
Chart.defaults.color = '#8b9b7e';
document.querySelectorAll('[data-chart]').forEach(canvas => {
    const rows = JSON.parse(canvas.dataset.values);
    if (!rows.length) return;
    const routes = canvas.dataset.chart === 'routes';
    new Chart(canvas, {
        type: 'bar',
        data: {labels: rows.map(r=>r.label), datasets: routes ? [{label:'Registros del cuadro M24',data:rows.map(r=>r.value),backgroundColor:'#54816a',borderRadius:3,barThickness:14}] : [
            {label:'Asignado',data:rows.map(r=>r.assigned),backgroundColor:'#dce8d4',borderRadius:3,barThickness:16},
            {label:'Ejecutado',data:rows.map(r=>r.executed),backgroundColor:'#568466',borderRadius:3,barThickness:16},
        ]},
        options: {responsive:true,maintainAspectRatio:false,indexAxis:routes?'y':'x',animation:false,
            plugins:{legend:{display:!routes,position:'bottom',align:'start',labels:{boxWidth:9,boxHeight:9,padding:22}},tooltip:{callbacks:{label:ctx=>`${ctx.dataset.label}: ${new Intl.NumberFormat('es-PE',{maximumFractionDigits:2}).format(ctx.raw)}${routes?' registros':' S/'}`}}},
            scales:{x:{grid:{display:routes,color:'#edf1e8'},border:{display:false},ticks:{maxRotation:0}},y:{beginAtZero:true,grid:{display:!routes,color:'#edf1e8'},border:{display:false},ticks:routes?{}:{callback:v=>`${v/1000000} M`}}}}
    });
});
document.querySelector('[data-menu]')?.addEventListener('click', event => {
    const open = document.getElementById('navigation').classList.toggle('open');
    event.currentTarget.setAttribute('aria-expanded', String(open));
});
document.querySelectorAll('[data-operational-chart]').forEach(canvas=>{
    const rows=JSON.parse(canvas.dataset.values);
    new Chart(canvas,{type:'bar',data:{labels:rows.map(r=>r.label),datasets:[{label:'Reportes registrados',data:rows.map(r=>r.value),backgroundColor:'#4d8069',borderRadius:4,maxBarThickness:40}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#edf1e8'}},x:{grid:{display:false}}}}});
});
document.querySelectorAll('[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!window.confirm(form.dataset.confirm)) event.preventDefault();}));
