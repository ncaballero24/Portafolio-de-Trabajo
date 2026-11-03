(() => {
  'use strict';
  const form=document.getElementById('simposium-editor'); if(!form) return;
  const data=JSON.parse(document.getElementById('simposium-datos').textContent);
  const list=document.getElementById('simposium-jornadas-editor');
  const status=document.getElementById('simposium-estado');
  let dirty=false, saving=false;
  const touch=()=>{dirty=true;status.textContent='Tienes cambios sin guardar.';};
  form.addEventListener('input',touch);
  window.addEventListener('beforeunload',event=>{if(dirty&&!saving){event.preventDefault();event.returnValue='';}});
  const el=(tag,text)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;};
  function field(parent,label,value,onchange,max,kind='text',required=true){
    const wrap=el('label',label);const input=el(kind==='textarea'?'textarea':'input');
    input.className='admin-input';input.value=value;input.maxLength=max;input.required=required;
    if(kind==='textarea')input.rows=3;else input.type=kind;
    input.addEventListener('input',()=>onchange(input.value));wrap.append(input);parent.append(wrap);
  }
  function button(parent,text,action,disabled=false){const b=el('button',text);b.type='button';b.className='btn btn-secundario';b.disabled=disabled;b.onclick=action;parent.append(b);}
  function move(items,index,delta){[items[index],items[index+delta]]=[items[index+delta],items[index]];touch();render();}
  function render(){
    list.replaceChildren();
    data.jornadas.forEach((day,index)=>{
      const card=el('section');card.className='noticia-detalle';card.append(el('h3',`Jornada ${index+1}`));
      const fields=el('div');fields.className='admin-form-grid';
      field(fields,'Título de la jornada',day.titulo,v=>day.titulo=v,240);
      field(fields,'Enfoque',day.enfoque,v=>day.enfoque=v,240);
      field(fields,'Descripción',day.descripcion,v=>day.descripcion=v,6000,'textarea',false);card.append(fields);
      button(card,'Subir jornada',()=>move(data.jornadas,index,-1),index===0);
      button(card,'Bajar jornada',()=>move(data.jornadas,index,1),index===data.jornadas.length-1);
      button(card,'Quitar jornada',()=>{if(confirm('¿Quitar esta jornada y sus actividades? Se aplicará al guardar.')){data.jornadas.splice(index,1);touch();render();}},data.jornadas.length===1);
      day.actividades.forEach((activity,i)=>{
        const row=el('fieldset');row.append(el('legend',`Actividad ${i+1}`));row.className='admin-form-grid';
        field(row,'Hora',activity[0],v=>activity[0]=v,5,'time');
        field(row,'Título',activity[1],v=>activity[1]=v,240);
        field(row,'Descripción',activity[2],v=>activity[2]=v,4000,'textarea',false);
        button(row,'Subir actividad',()=>move(day.actividades,i,-1),i===0);
        button(row,'Bajar actividad',()=>move(day.actividades,i,1),i===day.actividades.length-1);
        button(row,'Quitar actividad',()=>{if(confirm('¿Quitar esta actividad? Se aplicará al guardar.')){day.actividades.splice(i,1);touch();render();}});
        card.append(row);
      });
      button(card,'Agregar actividad',()=>{day.actividades.push(['09:00','','']);touch();render();},day.actividades.length>=50||data.jornadas.reduce((n,d)=>n+d.actividades.length,0)>=200);
      list.append(card);
    });
    document.getElementById('simposium-agregar').disabled=data.jornadas.length>=20;
  }
  document.getElementById('simposium-agregar').onclick=()=>{data.jornadas.push({titulo:'',enfoque:'',descripcion:'',actividades:[]});touch();render();};
  form.addEventListener('submit',event=>{
    form.querySelectorAll('[data-general]').forEach(input=>data[input.dataset.general]=input.value);
    const json=JSON.stringify(data);
    if(new TextEncoder().encode(json).length>500000){event.preventDefault();status.textContent='El programa es demasiado extenso. Reduce el texto antes de guardar.';return;}
    document.getElementById('simposium-json').value=json;saving=true;
    document.getElementById('simposium-guardar').disabled=true;
  });
  render();document.getElementById('simposium-guardar').disabled=false;
})();
