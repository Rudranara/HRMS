// Minimal calendar renderer and API hooks
document.addEventListener('DOMContentLoaded', function(){
  const root = document.getElementById('calendar-root');
  const modalEl = document.getElementById('worksheetModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  const modalDate = document.getElementById('modalDate');
  const hoursGrid = document.getElementById('hoursGrid');
  const saveBtn = document.getElementById('saveDraft');
  const submitBtn = document.getElementById('submitSheet');

  if (!root) return;
  const now = new Date();
  let currentYear = now.getFullYear();
  let currentMonth = now.getMonth()+1;
  renderMonth(currentYear, currentMonth);

  const todayBtn = document.getElementById('todayBtn');
  if (todayBtn) todayBtn.addEventListener('click', ()=>{ renderMonth(now.getFullYear(), now.getMonth()+1); });

  function renderMonth(y,m){
    root.innerHTML = '';
    const header = document.createElement('div'); header.className='d-flex justify-content-between mb-2 align-items-center';
    header.innerHTML = `<div><button id="prev" class="btn btn-sm btn-outline-secondary me-2">&larr;</button><strong>${y}-${String(m).padStart(2,'0')}</strong><button id="next" class="btn btn-sm btn-outline-secondary ms-2">&rarr;</button></div>`;
    root.appendChild(header);
    document.getElementById('prev').addEventListener('click', ()=>{ let dt = new Date(y,m-2,1); renderMonth(dt.getFullYear(), dt.getMonth()+1); });
    document.getElementById('next').addEventListener('click', ()=>{ let dt = new Date(y,m,1); renderMonth(dt.getFullYear(), dt.getMonth()+1); });

    const grid = document.createElement('div'); grid.className='d-flex flex-wrap';
    const start = new Date(y,m-1,1);
    const total = new Date(y,m,0).getDate();

    // fetch month data (worksheets + holidays)
    Promise.all([
      fetch(`/emp/worksheet/api/worksheets_month?m=${String(m).padStart(2,'0')}&y=${y}`, { credentials: 'same-origin' }).then(r=>r.json()),
      fetch(`/emp/worksheet/api/holidays_month?m=${String(m).padStart(2,'0')}&y=${y}`, { credentials: 'same-origin' }).then(r=>r.json())
    ]).then(([works, holidays]) => {
      const workMap = {};
      works.forEach(w=>{ workMap[w.date] = w; });
      const holSet = {};
      holidays.forEach(h=>{ holSet[h.date] = h.title || 'Holiday'; });

      const clientToday = new Date();
      const todayStrClient = `${clientToday.getFullYear()}-${String(clientToday.getMonth()+1).padStart(2,'0')}-${String(clientToday.getDate()).padStart(2,'0')}`;
      for(let d=1; d<=total; d++){
        const dateStr = `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const cell = document.createElement('div'); cell.className='day-cell';
        const statusDiv = document.createElement('div'); statusDiv.className='status text-muted mt-auto';
        cell.innerHTML = `<div class="date">${d}</div>`;
        // mark today/past
        const cellDate = new Date(dateStr + 'T00:00:00');
        if (dateStr === todayStrClient) cell.classList.add('today');
        else if (cellDate < new Date(todayStrClient + 'T00:00:00')) cell.classList.add('past');

        if (holSet[dateStr]) {
          const title = (holSet[dateStr] || 'Holiday').toLowerCase();
          if (title.indexOf('week') !== -1) cell.classList.add('weekoff');
          else if (title.indexOf('leave') !== -1) cell.classList.add('leave');
          else cell.classList.add('holiday');
          statusDiv.textContent = holSet[dateStr];
        } else if (workMap[dateStr]) {
          const data = workMap[dateStr].data ? JSON.parse(workMap[dateStr].data) : {};
          const isNonEmpty = (html)=>{
            if (!html) return false;
            const txt = html.replace(/<[^>]*>/g,'').replace(/&nbsp;|\s/g,'');
            return txt.length > 0;
          };
          const filled = Object.values(data).filter(v=> isNonEmpty(v)).length;
          if (filled === 0) { cell.classList.add('empty'); statusDiv.textContent = 'empty'; }
          else if (filled < 4) { cell.classList.add('partial'); statusDiv.textContent = `${filled} filled`; }
          else { cell.classList.add('filled'); statusDiv.textContent = `${filled} filled`; }
        } else { cell.classList.add('empty'); statusDiv.textContent = 'empty'; }

        const actionRow = document.createElement('div'); actionRow.className='d-flex gap-2 mt-2';
        const actionsDiv = document.createElement('div'); actionsDiv.className = 'actions';
        const openBtn = document.createElement('button'); openBtn.setAttribute('type','button'); openBtn.className='btn btn-sm btn-light'; openBtn.textContent='Open';
        openBtn.addEventListener('click', ()=> openWorksheet(dateStr));
        // disable open when holiday/weekoff
        if (holSet[dateStr]) { openBtn.disabled = true; openBtn.classList.add('disabled'); }
        actionRow.appendChild(openBtn);
        // marking actions - allow any logged-in user to mark/unmark (shown in top-right)
        if (window.CAN_MARK) {
          if (holSet[dateStr]) {
            const un = document.createElement('button'); un.setAttribute('type','button'); un.className='btn btn-sm btn-outline-danger'; un.textContent='U'; un.title='Unmark';
            un.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_delete', { method:'POST', credentials: 'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr}) }).then(()=> renderMonth(y,m));
            });
            actionsDiv.appendChild(un);
          } else {
            const markH = document.createElement('button'); markH.setAttribute('type','button'); markH.className='btn btn-sm btn-outline-info'; markH.title='Mark as Holiday'; markH.textContent='H';
            markH.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_add', { method:'POST', credentials: 'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr, title: 'Holiday'}) }).then(()=> renderMonth(y,m));
            });
            const markW = document.createElement('button'); markW.setAttribute('type','button'); markW.className='btn btn-sm btn-outline-secondary'; markW.title='Mark as Weekoff'; markW.textContent='W';
            markW.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_add', { method:'POST', credentials: 'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr, title: 'Weekoff'}) }).then(()=> renderMonth(y,m));
            });
            const markL = document.createElement('button'); markL.setAttribute('type','button'); markL.className='btn btn-sm btn-outline-primary'; markL.title='Mark as Leave'; markL.textContent='L';
            markL.addEventListener('click', ()=>{
              fetch('/emp/worksheet/api/holiday_add', { method:'POST', credentials: 'same-origin', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date: dateStr, title: 'Leave'}) }).then(()=> renderMonth(y,m));
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
      // make today's cell blink briefly on load
      const todayCell = root.querySelector('.day-cell.today');
      if (todayCell) {
        todayCell.classList.add('blink');
        setTimeout(()=> todayCell.classList.remove('blink'), 3000);
      }
    });
  }

  let currentEditors = null;

  function installPlainTextPasteHandler(quill){
    quill.root.addEventListener('paste', (event) => {
      const clipboard = event.clipboardData || window.clipboardData;
      if (!clipboard) return;
      event.preventDefault();
      const text = clipboard.getData('text/plain') || '';
      const range = quill.getSelection(true);
      const index = range ? range.index : quill.getLength();
      const length = range ? range.length : 0;
      quill.deleteText(index, length, 'user');
      quill.insertText(index, text, 'user');
      quill.setSelection(index + text.length, 0, 'silent');
    });
  }

  function openWorksheet(dateStr){
    modalDate.textContent = dateStr;
    hoursGrid.innerHTML = '';
    const hours = [
      '09:30-10:30','10:30-11:30','11:30-12:30','12:30-13:30','13:30-14:30','14:30-15:30','15:30-16:30','16:30-17:30','17:30-18:30'
    ];
    // build rich editors (Quill)
    const editors = {};
    hours.forEach(h=>{
      const div = document.createElement('div'); div.className='col-md-4 hour-box';
      const isLunch = h === '13:30-14:30';
      if (isLunch) div.classList.add('lunch');
      div.innerHTML = `<label class="form-label">${h}${isLunch? ' (Lunch)':''}</label><div class="editor" data-hour="${h}"></div>`;
      hoursGrid.appendChild(div);
    });

    // initialize Quill editors
    document.querySelectorAll('.editor').forEach(el=>{
      const hour = el.dataset.hour;
      const q = new Quill(el, { theme: 'snow', modules: { toolbar: [['bold','italic'], [{'list':'ordered'},{'list':'bullet'}]] }});
      installPlainTextPasteHandler(q);
      editors[hour] = q;
    });
    currentEditors = editors;

    // load existing data (show modal even if fetch fails)
    (async ()=>{
      try {
        const r = await fetch(`/emp/worksheet/api/worksheet_get?date=${dateStr}`, { credentials: 'same-origin' });
        const j = r.ok ? await r.json() : null;
        if (j && j.data) {
          const data = JSON.parse(j.data);
          Object.keys(editors).forEach(h=>{ if (data[h]) editors[h].clipboard.dangerouslyPasteHTML(data[h]); });
        }
      } catch(e){ console.warn('Worksheet load failed', e); }
      // always show modal and start autosave
      if (modal) modal.show();
      startAutoSave(dateStr, editors);
    })();

    // attach fresh handlers to avoid duplicate/old listeners
    const localSave = document.getElementById('saveDraft');
    const localSubmit = document.getElementById('submitSheet');
    if (localSave) {
      const ns = localSave.cloneNode(true);
      localSave.parentNode.replaceChild(ns, localSave);
      ns.addEventListener('click', async (e)=>{
        e.preventDefault();
        console.log('save clicked', dateStr);
        setAutosaveStatus('Saving...');
        await saveSheet(dateStr,'saved', editors, { closeOnSuccess: true });
      });
    }
    if (localSubmit) {
      const ns2 = localSubmit.cloneNode(true);
      localSubmit.parentNode.replaceChild(ns2, localSubmit);
      ns2.addEventListener('click', async (e)=>{
        e.preventDefault();
        if (validateEditors(editors)) {
          console.log('submit clicked', dateStr);
          setAutosaveStatus('Saving...');
          await saveSheet(dateStr,'submitted', editors, { closeOnSuccess: true });
        } else {
          alert('Please add at least one entry before submit.');
        }
      });
    }

    // cleanup on modal hide
    modalEl.addEventListener('hidden.bs.modal', ()=>{ stopAutoSave(); currentEditors = null; });
  }

  let autosaveTimer = null;
  let saving = false;

  function validateEditors(editors){
    return Object.values(editors).some(q=> q.getText().trim().length > 0 );
  }

  function startAutoSave(dateStr, editors){
    stopAutoSave();
    autosaveTimer = setInterval(()=>{ if (!saving) saveSheet(dateStr,'saved',editors); }, 30000);
  }
  function stopAutoSave(){ if (autosaveTimer) { clearInterval(autosaveTimer); autosaveTimer = null; } }

  function escapeHtml(text){
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function normalizeWorksheetHtml(html){
    if (!html || typeof html !== 'string') return '';

    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
    const root = doc.body.firstElementChild;
    if (!root) return '';

    const normalizeChildren = (node) => Array.from(node.childNodes).map(normalizeNode).join('');

    const normalizeList = (node, tagName) => {
      const items = Array.from(node.children)
        .filter((child) => child.tagName && child.tagName.toLowerCase() === 'li')
        .map((child) => `<li>${normalizeChildren(child) || '<br>'}</li>`)
        .join('');
      return items ? `<${tagName}>${items}</${tagName}>` : '';
    };

    function normalizeNode(node){
      if (node.nodeType === Node.TEXT_NODE) {
        return escapeHtml(node.textContent || '');
      }

      if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
      }

      const tag = node.tagName.toLowerCase();
      const content = normalizeChildren(node);

      if (tag === 'br') return '<br>';
      if (tag === 'strong' || tag === 'b') return `<strong>${content}</strong>`;
      if (tag === 'em' || tag === 'i') return `<em>${content}</em>`;
      if (tag === 'u') return `<u>${content}</u>`;
      if (tag === 'ol' || tag === 'ul') return normalizeList(node, tag);
      if (tag === 'li') return `<li>${content || '<br>'}</li>`;
      if (tag === 'p' || tag === 'div') return `<p>${content || '<br>'}</p>`;

      return content;
    }

    return normalizeChildren(root).trim();
  }

  function collectDataFromEditors(editors){
    const data = {};
    Object.keys(editors).forEach(h=>{
      data[h] = normalizeWorksheetHtml(editors[h].root.innerHTML);
    });
    return data;
  }

  async function saveSheet(dateStr, status, editors, options = {}){
    if (saving) return;
    saving = true;
    const payload = { date: dateStr, data: collectDataFromEditors(editors), status };
    console.log('saving payload', payload);
    try {
      const response = await fetch('/emp/worksheet/api/worksheet_save', {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json','X-CSRF-Token': window.CSRF_TOKEN},
        body: JSON.stringify(payload)
      });
      const json = await response.json().catch(() => null);
      if (!response.ok || !json || !json.ok) {
        throw new Error((json && (json.error || json.message)) || 'Save failed');
      }
      saving = false;
      setAutosaveStatus('Saved at ' + (new Date()).toLocaleTimeString());
      console.log('save response', json);
      showSaveMessage(json.message || 'Saved successfully', 'success');
      renderMonth(currentYear, currentMonth);
      if (options.closeOnSuccess && modal) {
        setTimeout(() => modal.hide(), 250);
      }
      return true;
    } catch (err) {
      saving = false;
      setAutosaveStatus('Save failed');
      showSaveMessage(err.message || 'Save failed', 'danger');
      console.error('save error', err);
      return false;
    }
  }

  function showSaveMessage(text, kind){
    const el = document.getElementById('saveAlert'); if (!el) return;
    el.className = 'alert alert-' + (kind === 'success' ? 'success' : 'danger');
    el.textContent = text; el.classList.remove('d-none');
    setTimeout(()=>{ el.classList.add('d-none'); }, 3000);
  }

  // Attempt to save any open editors when user leaves the page
  window.addEventListener('beforeunload', function(e){
    if (!currentEditors) return;
    try {
      const payload = { date: modalDate.textContent, data: collectDataFromEditors(currentEditors), status: 'saved' };
      // use fetch with keepalive to attempt background save
      fetch('/emp/worksheet/api/worksheet_save', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token': window.CSRF_TOKEN}, body: JSON.stringify(payload), keepalive:true });
    } catch(err){ /* ignore */ }
  });

  function setAutosaveStatus(text){
    const el = document.getElementById('autosaveStatus'); if (!el) return; el.textContent = text;
  }
});
