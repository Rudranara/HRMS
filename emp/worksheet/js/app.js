// App common JS (placeholder)
console.log('Worksheet app loaded');

// Admin page hooks: holidays and export
document.addEventListener('DOMContentLoaded', function(){
	// holidays
	const holidayForm = document.getElementById('holidayForm');
	if (holidayForm) {
		const list = document.getElementById('holidaysList');
		const loadList = ()=>{
			const [y,m] = (document.getElementById('exportMonth')?.value || new Date().toISOString().slice(0,7)).split('-');
			fetch(`/emp/worksheet/api/holidays_month?m=${m}&y=${y}`).then(r=>r.json()).then(data=>{
				list.innerHTML = data.map(h=> `<div class="d-flex justify-content-between align-items-center p-2 border mb-1"><div>${h.date} — ${h.title||'Holiday'}</div><button class="btn btn-sm btn-outline-danger btn-del" data-date="${h.date}">Delete</button></div>`).join('');
				list.querySelectorAll('.btn-del').forEach(b=> b.addEventListener('click', ()=>{
					const d = b.dataset.date;
					fetch('/emp/worksheet/api/holiday_delete', { method:'POST', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date:d}) }).then(()=>loadList());
				}));
			});
		};
		loadList();
		document.getElementById('addHoliday').addEventListener('click', (e)=>{
			e.preventDefault();
			const d = document.getElementById('holidayDate').value;
			const t = document.getElementById('holidayTitle').value;
			fetch('/emp/worksheet/api/holiday_add', { method:'POST', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({date:d,title:t}) }).then(()=>{ loadList(); });
		});
	}

	// users management
	const usersListEl = document.getElementById('users-list');
	const addUserBtn = document.getElementById('addUserBtn');
	const userModalEl = document.getElementById('userModal');
	const userModal = userModalEl ? new bootstrap.Modal(userModalEl) : null;
	const userForm = document.getElementById('userForm');
	function loadUsers(){
		if (!usersListEl) return;
		fetch('/emp/worksheet/api/users_list').then(r=>r.json()).then(data=>{
			usersListEl.innerHTML = data.map(u=> `<div class="d-flex justify-content-between align-items-center p-2 border mb-1"><div><strong>${u.name}</strong><div class="small text-muted">${u.email} — ${u.role}</div></div><div><button class="btn btn-sm btn-outline-primary btn-edit" data-id="${u.id}" data-email="${u.email}" data-name="${u.name}" data-role="${u.role}">Edit</button> <button class="btn btn-sm btn-outline-danger btn-del" data-id="${u.id}">Delete</button></div></div>`).join('');
			usersListEl.querySelectorAll('.btn-edit').forEach(b=> b.addEventListener('click', ()=>{
				document.getElementById('userId').value = b.dataset.id;
				document.getElementById('userEmail').value = b.dataset.email;
				document.getElementById('userName').value = b.dataset.name;
				document.getElementById('userRole').value = b.dataset.role;
				document.getElementById('userPassword').value = '';
				if (userModal) userModal.show();
			}));
			usersListEl.querySelectorAll('.btn-del').forEach(b=> b.addEventListener('click', ()=>{
				if (!confirm('Delete this user?')) return;
				fetch('/emp/worksheet/api/user_delete', { method:'POST', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({id: b.dataset.id}) }).then(()=> loadUsers());
			}));
		});
	}
	if (addUserBtn) addUserBtn.addEventListener('click', ()=>{
		document.getElementById('userId').value = '';
		document.getElementById('userEmail').value = '';
		document.getElementById('userName').value = '';
		document.getElementById('userRole').value = 'employee';
		document.getElementById('userPassword').value = '';
		if (userModal) userModal.show();
	});
	if (document.getElementById('saveUserBtn')) document.getElementById('saveUserBtn').addEventListener('click', ()=>{
		const id = document.getElementById('userId').value;
		const email = document.getElementById('userEmail').value;
		const name = document.getElementById('userName').value;
		const role = document.getElementById('userRole').value;
		const password = document.getElementById('userPassword').value;
		const body = new URLSearchParams({ id, email, name, role });
		if (password) body.append('password', password);
		fetch('/emp/worksheet/api/user_save',{ method:'POST', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body }).then(()=>{ if (userModal) userModal.hide(); loadUsers(); });
	});
	if (document.getElementById('deleteUserBtn')) document.getElementById('deleteUserBtn').addEventListener('click', ()=>{
		const id = document.getElementById('userId').value; if (!id) { alert('No user selected'); return; }
		if (!confirm('Permanently delete this user?')) return;
		fetch('/emp/worksheet/api/user_delete',{ method:'POST', headers:{'X-CSRF-Token': window.CSRF_TOKEN}, body: new URLSearchParams({id}) }).then(()=>{ if (userModal) userModal.hide(); loadUsers(); });
	});
	loadUsers();

	const exportBtn = document.getElementById('exportCsv');
	if (exportBtn) {
		exportBtn.addEventListener('click', ()=>{
			const val = document.getElementById('exportMonth').value; if (!val) return alert('Pick month');
			const [y,m] = val.split('-');
			window.location = `/emp/worksheet/api/export_csv?m=${m}&y=${y}`;
		});
	}
});
