document.addEventListener('DOMContentLoaded', function(){
  const root = document.getElementById('admin-calendar-root');
  if (!root) return;
  const now = new Date();
  let y = now.getFullYear(), m = now.getMonth()+1;
  render();

  function render(){
    root.innerHTML = '';
    const header = document.createElement('div'); header.className='d-flex justify-content-between mb-2';
    header.innerHTML = `<div><button id="prevA" class="btn btn-sm btn-outline-secondary">&larr;</button> <strong>${y}-${String(m).padStart(2,'0')}</strong> <button id="nextA" class="btn btn-sm btn-outline-secondary">&rarr;</button></div>`;
    root.appendChild(header);
    document.getElementById('prevA').addEventListener('click', ()=>{ let dt=new Date(y,m-2,1); y=dt.getFullYear(); m=dt.getMonth()+1; render(); });
    document.getElementById('nextA').addEventListener('click', ()=>{ let dt=new Date(y,m,1); y=dt.getFullYear(); m=dt.getMonth()+1; render(); });

    fetch(`/emp/worksheet/api/holidays_month?m=${String(m).padStart(2,'0')}&y=${y}`).then(r=>r.json()).then(hol=>{
      const set = {};
      hol.forEach(h=> set[h.date]=h.title||'Holiday');
      const grid = document.createElement('div'); grid.className='d-flex flex-wrap';
      const total = new Date(y,m,0).getDate();
      for(let d=1; d<=total; d++){
        const dateStr = `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const cell = document.createElement('div'); cell.className='day-cell';
        const title = set[dateStr];
        if (title) { cell.classList.add('holiday'); }
        cell.innerHTML = `<div class="date">${d}</div><div class="mt-2">${title? title : ''}</div>`;
        const actions = document.createElement('div'); actions.className='d-flex gap-2 mt-2';
        const markHoliday = document.createElement('button'); markHoliday.className='btn btn-sm btn-outline-primary'; markHoliday.textContent='Mark Holiday';
        const markWeekoff = document.createElement('button'); markWeekoff.className='btn btn-sm btn-outline-secondary'; markWeekoff.textContent='Mark Weekoff';
        const unmark = document.createElement('button'); unmark.className='btn btn-sm btn-outline-danger'; unmark.textContent='Unmark';
        actions.append(markHoliday, markWeekoff, unmark);
        cell.appendChild(actions);
        grid.appendChild(cell);

        markHoliday.addEventListener('click', ()=> toggleHoliday(dateStr,'Holiday'));
        markWeekoff.addEventListener('click', ()=> toggleHoliday(dateStr,'Weekoff'));
        unmark.addEventListener('click', ()=> removeHoliday(dateStr));
      }
      root.appendChild(grid);
    });
  }

  function toggleHoliday(date, title){
    fetch('/emp/worksheet/api/holiday_add', { method:'POST', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date:date,title:title}) }).then(()=> render());
  }

  function removeHoliday(date){
    fetch('/emp/worksheet/api/holiday_delete', { method:'POST', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date:date}) }).then(()=> render());
  }
});
