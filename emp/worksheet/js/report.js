// Admin report renderer
document.addEventListener('DOMContentLoaded', function(){
  const userSel = document.getElementById('reportUser');
  const monthInp = document.getElementById('reportMonth');
  const loadBtn = document.getElementById('loadReport');
  const summaryBtn = document.getElementById('monthlySummary');
  const root = document.getElementById('report-calendar-root');
  const modalEl = document.getElementById('reportModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  const modalBody = document.getElementById('reportModalBody');

  // load users
  fetch('/emp/worksheet/api/users_list').then(r=>r.json()).then(data=>{
    userSel.innerHTML = data.map(u=> `<option value="${u.id}">${u.name} — ${u.email}</option>`).join('');
  });

  // Use ReportCalendar for rendering the calendar with admin edit capability

  function openDetail(dateStr, row){
    modalBody.innerHTML = `<h6>${dateStr}</h6>`;
    if (!row || !row.data) { modalBody.innerHTML += '<div>No worksheet</div>'; if (modal) modal.show(); return; }
    const data = JSON.parse(row.data);
    const list = document.createElement('div');
    Object.keys(data).forEach(k=>{ const box = document.createElement('div'); box.className='mb-2'; box.innerHTML = `<strong>${k}</strong><div>${data[k]}</div>`; list.appendChild(box); });
    modalBody.appendChild(list);
    if (modal) modal.show();
  }

  loadBtn.addEventListener('click', ()=>{
    const u = userSel.value; const [y,m] = monthInp.value.split('-'); if (!u) return alert('Pick user');
    if (window.ReportCalendar) {
      ReportCalendar.init({ userId: u, year: y, month: parseInt(m,10), rootId: 'report-calendar-root' });
    } else {
      alert('Calendar module not loaded');
    }
  });

  summaryBtn.addEventListener('click', ()=>{
    const u = userSel.value; const [y,m] = monthInp.value.split('-'); if (!u) return alert('Pick user');
    fetch(`/emp/worksheet/api/worksheets_user_month?user_id=${u}&m=${m}&y=${y}`).then(r=>r.json()).then(rows=>{
      fetch(`/emp/worksheet/api/holidays_month?m=${m}&y=${y}`).then(r=>r.json()).then(hol=>{
        const holSet = {}; hol.forEach(h=> holSet[h.date]=h.title);
        let total=0, filled=0, partial=0, empty=0, leave=0, weekoff=0, halfOrMore=0;
        rows.forEach(rw=>{
          total++;
          const data = rw.data ? JSON.parse(rw.data) : {};
          const slots = Object.values(data).filter(x=>{ if(!x) return false; const t = x.replace(/<[^>]*>/g,'').replace(/&nbsp;|\s/g,''); return t.length>0; }).length;
          if (slots===0) empty++; else if (slots<4) partial++; else filled++;
          if ((slots/9) >= 0.5) halfOrMore++;
        });
        // count holidays/weekoff/leave from holidays API
        Object.values(holSet).forEach(t=>{ const lower = t.toLowerCase(); if (lower.indexOf('week')!==-1) weekoff++; else if (lower.indexOf('leave')!==-1) leave++; else {/*holiday*/} });
        const reportHtml = `<div><strong>Total days with worksheets:</strong> ${total}</div>
          <div><strong>Filled:</strong> ${filled}</div>
          <div><strong>Partial:</strong> ${partial}</div>
          <div><strong>Empty:</strong> ${empty}</div>
          <div><strong>50% or more filled:</strong> ${halfOrMore}</div>
          <div><strong>Weekoffs:</strong> ${weekoff}</div>
          <div><strong>Leaves:</strong> ${leave}</div>`;
        modalBody.innerHTML = `<h5>Monthly Summary</h5>` + reportHtml;
        if (modal) modal.show();
      });
    });
  });
});
