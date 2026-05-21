// Report-specific calendar for admin viewing/editing a user's month
var ReportCalendar = (function(){
  let root, modal, modalEl, modalDate, hoursGrid, saveBtn, submitBtn, currentYear, currentMonth, currentUser;
  function init(opts){
    currentUser = opts.userId;
    root = document.getElementById(opts.rootId || 'report-calendar-root');
    modalEl = document.getElementById('worksheetModal') || createModal();
    modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    modalDate = modalEl.querySelector('#modalDate');
    hoursGrid = modalEl.querySelector('#hoursGrid');
    saveBtn = modalEl.querySelector('#saveDraft');
    submitBtn = modalEl.querySelector('#submitSheet');
    const now = new Date(); currentYear = opts.year || now.getFullYear(); currentMonth = opts.month || (now.getMonth()+1);
    renderMonth(currentYear, currentMonth);
  }

  function createModal(){
    // borrow modal HTML from dashboard modal if not present
    const container = document.createElement('div'); container.innerHTML = `
      <div class="modal fade" id="worksheetModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Worksheet for <span id="modalDate"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><form id="worksheetForm"><div id="saveAlert" class="alert d-none" role="alert"></div><div class="row" id="hoursGrid"></div></form></div>
            <div class="modal-footer d-flex justify-content-between align-items-center"><div class="text-muted small" id="autosaveStatus">Idle</div><div><button type="button" id="saveDraft" class="btn btn-secondary">Save Draft</button><button type="button" id="submitSheet" class="btn btn-primary">Submit</button></div></div>
          </div>
        </div>
      </div>`;
    document.body.appendChild(container.firstElementChild);
    return document.getElementById('worksheetModal');
  }

  function renderMonth(y,m){
    if (!root) return;
    root.innerHTML='';
    const header = document.createElement('div'); header.className='d-flex justify-content-between mb-2 align-items-center';
    header.innerHTML = `<div><button id="prev" class="btn btn-sm btn-outline-secondary me-2">&larr;</button><strong>${y}-${String(m).padStart(2,'0')}</strong><button id="next" class="btn btn-sm btn-outline-secondary ms-2">&rarr;</button></div>`;
    root.appendChild(header);
    document.getElementById('prev').addEventListener('click', ()=>{ let dt = new Date(y,m-2,1); renderMonth(dt.getFullYear(), dt.getMonth()+1); });
    document.getElementById('next').addEventListener('click', ()=>{ let dt = new Date(y,m,1); renderMonth(dt.getFullYear(), dt.getMonth()+1); });

    const grid = document.createElement('div'); grid.className='d-flex flex-wrap';
    const total = new Date(y,m,0).getDate();
    Promise.all([
      fetch(`/emp/worksheet/api/worksheets_user_month?user_id=${currentUser}&m=${String(m).padStart(2,'0')}&y=${y}`, { credentials: 'same-origin' }).then(r=>r.json()),
      fetch(`/emp/worksheet/api/holidays_month?m=${String(m).padStart(2,'0')}&y=${y}&user_id=${currentUser}`, { credentials: 'same-origin' }).then(r=>r.json())
    ]).then(([works, holidays])=>{
      const workMap = {}; works.forEach(w=>{ workMap[w.date]=w; });
      const hol = {}; holidays.forEach(h=>{ hol[h.date]=h.title; });
      for(let d=1; d<=total; d++){
        const dateStr = `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const cell = document.createElement('div'); cell.className='day-cell';
        const statusDiv = document.createElement('div'); statusDiv.className='status text-muted mt-auto';
        cell.innerHTML = `<div class="date">${d}</div>`;

        // determine holiday/weekoff/leave
        if (hol[dateStr]) {
          const title = (hol[dateStr] || 'Holiday').toLowerCase();
          if (title.indexOf('week') !== -1) cell.classList.add('weekoff');
          else if (title.indexOf('leave') !== -1) cell.classList.add('leave');
          else cell.classList.add('holiday');
          statusDiv.textContent = hol[dateStr];
        }

        // worksheet status coloring
        const wrow = workMap[dateStr];
        if (wrow && wrow.data) {
          let data = {};
          try { data = JSON.parse(wrow.data); } catch(e){ data = {}; }
          const isNonEmpty = (html)=>{ if (!html) return false; const txt = html.replace(/<[^>]*>/g,'').replace(/&nbsp;|\s/g,''); return txt.length>0; };
          const filled = Object.values(data).filter(v=> isNonEmpty(v)).length;
          if (filled === 0) { cell.classList.add('empty'); statusDiv.textContent = 'empty'; }
          else if (filled < 4) { cell.classList.add('partial'); statusDiv.textContent = `${filled} filled`; }
          else { cell.classList.add('filled'); statusDiv.textContent = `${filled} filled`; }
        } else if (!hol[dateStr]) {
          cell.classList.add('empty'); statusDiv.textContent = 'empty';
        }

        // action buttons
        const actionRow = document.createElement('div'); actionRow.className='d-flex gap-2 mt-2';
        const actionsDiv = document.createElement('div'); actionsDiv.className = 'actions';

        const openBtn = document.createElement('button'); openBtn.setAttribute('type','button'); openBtn.className='btn btn-sm btn-light'; openBtn.textContent='Open';
        if (hol[dateStr]) { openBtn.disabled = true; openBtn.classList.add('disabled'); }
        openBtn.addEventListener('click', ()=> openWorksheetForUser(dateStr, currentUser));
        actionRow.appendChild(openBtn);

        // mark/unmark controls (H, W, L, U) for admins (and allowed markers)
        if (window.CAN_MARK) {
          if (hol[dateStr]) {
            const un = document.createElement('button'); un.setAttribute('type','button'); un.className='btn btn-sm btn-outline-danger'; un.textContent='U'; un.title='Unmark';
            un.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_delete', { method:'POST', credentials:'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr}) }).then(()=> renderMonth(y,m));
            });
            actionsDiv.appendChild(un);
          } else {
            const markH = document.createElement('button'); markH.setAttribute('type','button'); markH.className='btn btn-sm btn-outline-info'; markH.title='Mark as Holiday'; markH.textContent='H';
            markH.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_add', { method:'POST', credentials:'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr, title: 'Holiday'}) }).then(()=> renderMonth(y,m));
            });
            const markW = document.createElement('button'); markW.setAttribute('type','button'); markW.className='btn btn-sm btn-outline-secondary'; markW.title='Mark as Weekoff'; markW.textContent='W';
            markW.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_add', { method:'POST', credentials:'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr, title: 'Weekoff'}) }).then(()=> renderMonth(y,m));
            });
            const markL = document.createElement('button'); markL.setAttribute('type','button'); markL.className='btn btn-sm btn-outline-primary'; markL.title='Mark as Leave'; markL.textContent='L';
            markL.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_add', { method:'POST', credentials:'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr, title: 'Leave'}) }).then(()=> renderMonth(y,m));
            });
            actionsDiv.appendChild(markH);
            actionsDiv.appendChild(markW);
            actionsDiv.appendChild(markL);
          }
        }

        cell.appendChild(actionsDiv);
        cell.appendChild(actionRow);
        cell.appendChild(statusDiv);
        grid.appendChild(cell);
      }
      root.appendChild(grid);
    });
  }

  let currentEditors = null; let autosaveTimer=null; let saving=false;
  function openWorksheetForUser(dateStr, userId){
    modalDate.textContent = dateStr; hoursGrid.innerHTML = '';
    const hours = ['09:30-10:30','10:30-11:30','11:30-12:30','12:30-13:30','13:30-14:30','14:30-15:30','15:30-16:30','16:30-17:30','17:30-18:30'];
    const editors = {};
    hours.forEach(h=>{ const div = document.createElement('div'); div.className='col-md-4 hour-box'; const isLunch = h==='13:30-14:30'; if (isLunch) div.classList.add('lunch'); div.innerHTML = `<label class="form-label">${h}${isLunch? ' (Lunch)':''}</label><div class="editor" data-hour="${h}"></div>`; hoursGrid.appendChild(div); });
    document.querySelectorAll('#worksheetModal .editor').forEach(el=>{ const hour = el.dataset.hour; const q = new Quill(el, { theme:'snow', modules:{ toolbar:[['bold','italic'], [{'list':'ordered'},{'list':'bullet'}]] }}); editors[hour]=q; });
    currentEditors = editors;
    (async ()=>{
      try {
        const r = await fetch(`/emp/worksheet/api/worksheet_get_user?user_id=${userId}&date=${dateStr}`);
        const j = r.ok ? await r.json() : null;
        if (j && j.data){ const data = JSON.parse(j.data); Object.keys(editors).forEach(h=>{ if (data[h]) editors[h].clipboard.dangerouslyPasteHTML(data[h]); }); }
      } catch(e){ console.warn(e); }
      if (modal) modal.show(); startAutoSave(dateStr, userId, editors);
    })();

    const localSave = modalEl.querySelector('#saveDraft'); const localSubmit = modalEl.querySelector('#submitSheet');
    if (localSave){ const ns = localSave.cloneNode(true); localSave.parentNode.replaceChild(ns, localSave); ns.addEventListener('click', ()=>{ saveSheetForUser(dateStr,'saved',editors,userId); }); }
    if (localSubmit){ const ns2 = localSubmit.cloneNode(true); localSubmit.parentNode.replaceChild(ns2, localSubmit); ns2.addEventListener('click', ()=>{ if (validateEditors(editors)) saveSheetForUser(dateStr,'submitted',editors,userId); else alert('Add at least one entry'); }); }

    modalEl.addEventListener('hidden.bs.modal', ()=>{ stopAutoSave(); currentEditors=null; });
  }

  function validateEditors(editors){ return Object.values(editors).some(q=> q.getText().trim().length>0); }
  function startAutoSave(dateStr,userId,editors){ stopAutoSave(); autosaveTimer=setInterval(()=>{ if(!saving) saveSheetForUser(dateStr,'saved',editors,userId); },30000); }
  function stopAutoSave(){ if (autosaveTimer){ clearInterval(autosaveTimer); autosaveTimer=null; } }
  function collectDataFromEditors(editors){ const data={}; Object.keys(editors).forEach(h=> data[h]=editors[h].root.innerHTML); return data; }

  function saveSheetForUser(dateStr,status,editors,userId){ if (saving) return; saving=true; const payload = { user_id: userId, date: dateStr, data: collectDataFromEditors(editors), status };
    fetch('/emp/worksheet/api/worksheet_save_admin', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-CSRF-Token': window.CSRF_TOKEN}, body: JSON.stringify(payload) })
      .then(r=>{ if (!r.ok) throw new Error('HTTP'); return r.json().catch(()=>null); }).then(json=>{ saving=false; const alert = modalEl.querySelector('#saveAlert'); if (alert){ alert.className='alert alert-success'; alert.textContent='Saved'; alert.classList.remove('d-none'); setTimeout(()=>alert.classList.add('d-none'),2000);} if (modal) modal.hide(); renderMonth(currentYear,currentMonth); }).catch(err=>{ saving=false; const alert = modalEl.querySelector('#saveAlert'); if (alert){ alert.className='alert alert-danger'; alert.textContent='Save failed'; alert.classList.remove('d-none'); setTimeout(()=>alert.classList.add('d-none'),3000);} console.error(err); });
  }

  return { init, renderMonth };
})();
