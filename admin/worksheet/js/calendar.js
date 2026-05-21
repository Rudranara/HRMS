// Minimal calendar renderer and API hooks
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('calendar-root');
  const modalEl = document.getElementById('worksheetModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  const modalDate = document.getElementById('modalDate');
  const hoursGrid = document.getElementById('hoursGrid');
  const todayBtn = document.getElementById('todayBtn');

  if (!root) return;

  const isAdmin = Boolean(window.IS_ADMIN);
  const contextEmployeeId = Number(
    window.WORKSHEET_EMPLOYEE_ID || window.ADMIN_EMPLOYEE_ID || 0
  );

  if (isAdmin && contextEmployeeId <= 0) {
    console.warn(
      'calendar.js requires window.WORKSHEET_EMPLOYEE_ID in admin mode. Use report_calendar.js for admin worksheet pages.'
    );
    return;
  }

  const apiBase = isAdmin
    ? '/admin/worksheet/api'
    : '/emp/worksheet/api';
  const canMark = Boolean(window.CAN_MARK) && !isAdmin;

  const now = new Date();
  let currentYear = now.getFullYear();
  let currentMonth = now.getMonth() + 1;
  let currentEditors = null;
  let autosaveTimer = null;
  let saving = false;

  renderMonth(currentYear, currentMonth);

  if (todayBtn) {
    todayBtn.addEventListener('click', function () {
      renderMonth(now.getFullYear(), now.getMonth() + 1);
    });
  }

  function buildApiUrl(name, params) {
    const endpointMap = isAdmin
      ? {
          month: 'worksheets_user_month.php',
          holidays: 'holidays_month.php',
          get: 'worksheet_get_user.php',
          save: 'worksheet_save_admin.php',
          holidayAdd: 'holiday_add.php',
          holidayDelete: 'holiday_delete.php'
        }
      : {
          month: 'worksheets_month.php',
          holidays: 'holidays_month.php',
          get: 'worksheet_get.php',
          save: 'worksheet_save.php',
          holidayAdd: 'holiday_add.php',
          holidayDelete: 'holiday_delete.php'
        };

    const search = new URLSearchParams();
    Object.entries(params || {}).forEach(function ([key, value]) {
      if (value !== undefined && value !== null && value !== '') {
        search.set(key, value);
      }
    });

    const suffix = search.toString() ? `?${search.toString()}` : '';
    return `${apiBase}/${endpointMap[name]}${suffix}`;
  }

  async function fetchJson(url, options) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      ...(options || {})
    });

    const text = await response.text();
    let json = null;

    if (text) {
      try {
        json = JSON.parse(text);
      } catch (error) {
        const parseError = new Error(`Invalid JSON response (${response.status})`);
        parseError.cause = error;
        parseError.responseText = text;
        throw parseError;
      }
    }

    if (!response.ok) {
      const requestError = new Error(
        (json && (json.error || json.message)) || `Request failed (${response.status})`
      );
      requestError.status = response.status;
      requestError.response = json;
      throw requestError;
    }

    return json;
  }

  function parseWorksheetData(raw) {
    if (!raw || typeof raw !== 'string') {
      return {};
    }

    try {
      return JSON.parse(raw);
    } catch (error) {
      console.warn('Invalid worksheet data JSON', error);
      return {};
    }
  }

  function installPlainTextPasteHandler(quill) {
    quill.root.addEventListener('paste', function (event) {
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

  function renderMonth(year, month) {
    currentYear = year;
    currentMonth = month;
    root.innerHTML = '';

    const header = document.createElement('div');
    header.className = 'd-flex justify-content-between mb-2 align-items-center';
    header.innerHTML = `<div><button id="prev" class="btn btn-sm btn-outline-secondary me-2">&larr;</button><strong>${year}-${String(month).padStart(2, '0')}</strong><button id="next" class="btn btn-sm btn-outline-secondary ms-2">&rarr;</button></div>`;
    root.appendChild(header);

    document.getElementById('prev').addEventListener('click', function () {
      const dt = new Date(year, month - 2, 1);
      renderMonth(dt.getFullYear(), dt.getMonth() + 1);
    });

    document.getElementById('next').addEventListener('click', function () {
      const dt = new Date(year, month, 1);
      renderMonth(dt.getFullYear(), dt.getMonth() + 1);
    });

    const grid = document.createElement('div');
    grid.className = 'd-flex flex-wrap';
    const total = new Date(year, month, 0).getDate();

    const monthQuery = {
      m: String(month).padStart(2, '0'),
      y: year
    };

    if (isAdmin) {
      monthQuery.employee_id = contextEmployeeId;
    }

    Promise.all([
      fetchJson(buildApiUrl('month', monthQuery)),
      fetchJson(buildApiUrl('holidays', monthQuery))
    ])
      .then(function ([worksheets, holidays]) {
        const workMap = {};
        (worksheets || []).forEach(function (worksheet) {
          workMap[worksheet.date] = worksheet;
        });

        const holidayMap = {};
        (holidays || []).forEach(function (holiday) {
          holidayMap[holiday.date] = holiday.title || 'Holiday';
        });

        const clientToday = new Date();
        const todayStr = `${clientToday.getFullYear()}-${String(
          clientToday.getMonth() + 1
        ).padStart(2, '0')}-${String(clientToday.getDate()).padStart(2, '0')}`;

        for (let day = 1; day <= total; day += 1) {
          const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
          const cell = document.createElement('div');
          cell.className = 'day-cell';
          const statusDiv = document.createElement('div');
          statusDiv.className = 'status text-muted mt-auto';
          cell.innerHTML = `<div class="date">${day}</div>`;

          const cellDate = new Date(`${dateStr}T00:00:00`);
          if (dateStr === todayStr) {
            cell.classList.add('today');
          } else if (cellDate < new Date(`${todayStr}T00:00:00`)) {
            cell.classList.add('past');
          }

          if (holidayMap[dateStr]) {
            const title = holidayMap[dateStr].toLowerCase();
            if (title.includes('week')) {
              cell.classList.add('weekoff');
            } else if (title.includes('leave')) {
              cell.classList.add('leave');
            } else {
              cell.classList.add('holiday');
            }
            statusDiv.textContent = holidayMap[dateStr];
          } else if (workMap[dateStr]) {
            const data = parseWorksheetData(workMap[dateStr].data);
            const filled = Object.values(data).filter(function (html) {
              if (!html) {
                return false;
              }

              const text = String(html)
                .replace(/<[^>]*>/g, '')
                .replace(/&nbsp;|\s/g, '');
              return text.length > 0;
            }).length;

            if (filled === 0) {
              cell.classList.add('empty');
              statusDiv.textContent = 'empty';
            } else if (filled < 4) {
              cell.classList.add('partial');
              statusDiv.textContent = `${filled} filled`;
            } else {
              cell.classList.add('filled');
              statusDiv.textContent = `${filled} filled`;
            }
          } else {
            cell.classList.add('empty');
            statusDiv.textContent = 'empty';
          }

          const actionRow = document.createElement('div');
          actionRow.className = 'd-flex gap-2 mt-2';
          const actionsDiv = document.createElement('div');
          actionsDiv.className = 'actions';

          const openBtn = document.createElement('button');
          openBtn.type = 'button';
          openBtn.className = 'btn btn-sm btn-light';
          openBtn.textContent = 'Open';
          openBtn.addEventListener('click', function () {
            openWorksheet(dateStr);
          });

          if (holidayMap[dateStr]) {
            openBtn.disabled = true;
            openBtn.classList.add('disabled');
          }

          actionRow.appendChild(openBtn);

          if (canMark) {
            if (holidayMap[dateStr]) {
              const unmarkBtn = document.createElement('button');
              unmarkBtn.type = 'button';
              unmarkBtn.className = 'btn btn-sm btn-outline-danger';
              unmarkBtn.textContent = 'U';
              unmarkBtn.title = 'Unmark';
              unmarkBtn.addEventListener('click', async function () {
                try {
                  await fetchJson(buildApiUrl('holidayDelete'), {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': window.CSRF_TOKEN },
                    body: new URLSearchParams({ date: dateStr })
                  });
                  renderMonth(year, month);
                } catch (error) {
                  console.error('Holiday delete failed', error);
                }
              });
              actionsDiv.appendChild(unmarkBtn);
            } else {
              [
                { text: 'H', title: 'Holiday', className: 'btn-outline-info' },
                { text: 'W', title: 'Weekoff', className: 'btn-outline-secondary' },
                { text: 'L', title: 'Leave', className: 'btn-outline-primary' }
              ].forEach(function (mark) {
                const markBtn = document.createElement('button');
                markBtn.type = 'button';
                markBtn.className = `btn btn-sm ${mark.className}`;
                markBtn.title = `Mark as ${mark.title}`;
                markBtn.textContent = mark.text;
                markBtn.addEventListener('click', async function () {
                  try {
                    await fetchJson(buildApiUrl('holidayAdd'), {
                      method: 'POST',
                      headers: { 'X-CSRF-Token': window.CSRF_TOKEN },
                      body: new URLSearchParams({
                        date: dateStr,
                        title: mark.title
                      })
                    });
                    renderMonth(year, month);
                  } catch (error) {
                    console.error('Holiday add failed', error);
                  }
                });
                actionsDiv.appendChild(markBtn);
              });
            }
          }

          cell.appendChild(actionsDiv);
          cell.appendChild(actionRow);
          cell.appendChild(statusDiv);
          grid.appendChild(cell);
        }

        root.appendChild(grid);

        const todayCell = root.querySelector('.day-cell.today');
        if (todayCell) {
          todayCell.classList.add('blink');
          setTimeout(function () {
            todayCell.classList.remove('blink');
          }, 3000);
        }
      })
      .catch(function (error) {
        console.error('Calendar month load failed', error);
        const errorBox = document.createElement('div');
        errorBox.className = 'text-danger small py-3';
        errorBox.textContent = 'Failed to load worksheet calendar.';
        root.appendChild(errorBox);
      });
  }

  function openWorksheet(dateStr) {
    if (!modal || !modalEl || !modalDate || !hoursGrid) {
      return;
    }

    modalDate.textContent = dateStr;
    hoursGrid.innerHTML = '';

    const hours = [
      '09:30-10:30',
      '10:30-11:30',
      '11:30-12:30',
      '12:30-13:30',
      '13:30-14:30',
      '14:30-15:30',
      '15:30-16:30',
      '16:30-17:30',
      '17:30-18:30'
    ];

    const editors = {};
    hours.forEach(function (hour) {
      const div = document.createElement('div');
      div.className = 'col-md-4 hour-box';
      const isLunch = hour === '13:30-14:30';
      if (isLunch) {
        div.classList.add('lunch');
      }
      div.innerHTML = `<label class="form-label">${hour}${isLunch ? ' (Lunch)' : ''}</label><div class="editor" data-hour="${hour}"></div>`;
      hoursGrid.appendChild(div);
    });

    document.querySelectorAll('.editor').forEach(function (editorElement) {
      const hour = editorElement.dataset.hour;
      const quill = new Quill(editorElement, {
        theme: 'snow',
        modules: {
          toolbar: [['bold', 'italic'], [{ list: 'ordered' }, { list: 'bullet' }]]
        }
      });
      installPlainTextPasteHandler(quill);
      editors[hour] = quill;
    });

    currentEditors = editors;

    const getQuery = isAdmin
      ? { employee_id: contextEmployeeId, date: dateStr }
      : { date: dateStr };

    (async function () {
      try {
        const worksheet = await fetchJson(buildApiUrl('get', getQuery));
        if (worksheet && worksheet.data) {
          const data = parseWorksheetData(worksheet.data);
          Object.keys(editors).forEach(function (hour) {
            if (data[hour]) {
              editors[hour].clipboard.dangerouslyPasteHTML(data[hour]);
            }
          });
        }
      } catch (error) {
        console.warn('Worksheet load failed', error);
      }

      modal.show();
      setAutosaveStatus('Changes save automatically while you edit.');
      startAutoSave(dateStr, editors);
    })();

    const saveButton = document.getElementById('saveDraft');
    const submitButton = document.getElementById('submitSheet');

    if (saveButton && saveButton.parentNode) {
      const nextSaveButton = saveButton.cloneNode(true);
      saveButton.parentNode.replaceChild(nextSaveButton, saveButton);
      nextSaveButton.addEventListener('click', async function (event) {
        event.preventDefault();
        setAutosaveStatus('Saving...');
        await saveSheet(dateStr, 'saved', editors, { closeOnSuccess: true });
      });
    }

    if (submitButton && submitButton.parentNode) {
      const nextSubmitButton = submitButton.cloneNode(true);
      submitButton.parentNode.replaceChild(nextSubmitButton, submitButton);
      nextSubmitButton.addEventListener('click', async function (event) {
        event.preventDefault();

        if (!validateEditors(editors)) {
          alert('Please add at least one entry before submit.');
          return;
        }

        setAutosaveStatus('Saving...');
        await saveSheet(dateStr, 'submitted', editors, { closeOnSuccess: true });
      });
    }

    modalEl.addEventListener(
      'hidden.bs.modal',
      function () {
        stopAutoSave();
        currentEditors = null;
        setAutosaveStatus('Changes save automatically while you edit.');
      },
      { once: true }
    );
  }

  function validateEditors(editors) {
    return Object.values(editors).some(function (quill) {
      return quill.getText().trim().length > 0;
    });
  }

  function startAutoSave(dateStr, editors) {
    stopAutoSave();
    autosaveTimer = setInterval(function () {
      if (!saving) {
        saveSheet(dateStr, 'saved', editors);
      }
    }, 30000);
  }

  function stopAutoSave() {
    if (autosaveTimer) {
      clearInterval(autosaveTimer);
      autosaveTimer = null;
    }
  }

  function escapeHtml(text) {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function normalizeWorksheetHtml(html) {
    if (!html || typeof html !== 'string') {
      return '';
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
    const wrapper = doc.body.firstElementChild;
    if (!wrapper) {
      return '';
    }

    function normalizeChildren(node) {
      return Array.from(node.childNodes)
        .map(normalizeNode)
        .join('');
    }

    function normalizeList(node, tagName) {
      const items = Array.from(node.children)
        .filter(function (child) {
          return child.tagName && child.tagName.toLowerCase() === 'li';
        })
        .map(function (child) {
          return `<li>${normalizeChildren(child) || '<br>'}</li>`;
        })
        .join('');
      return items ? `<${tagName}>${items}</${tagName}>` : '';
    }

    function normalizeNode(node) {
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

    return normalizeChildren(wrapper).trim();
  }

  function collectDataFromEditors(editors) {
    const data = {};
    Object.keys(editors).forEach(function (hour) {
      data[hour] = normalizeWorksheetHtml(editors[hour].root.innerHTML);
    });
    return data;
  }

  async function saveSheet(dateStr, status, editors, options) {
    if (saving) {
      return false;
    }

    saving = true;

    const payload = {
      date: dateStr,
      data: collectDataFromEditors(editors),
      status: status
    };

    if (isAdmin) {
      payload.employee_id = contextEmployeeId;
    }

    try {
      const response = await fetchJson(buildApiUrl('save'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.CSRF_TOKEN
        },
        body: JSON.stringify(payload)
      });

      saving = false;
      setAutosaveStatus(`Saved at ${new Date().toLocaleTimeString()}`);
      showSaveMessage(
        (response && response.message) || 'Saved successfully',
        'success'
      );
      renderMonth(currentYear, currentMonth);

      if (options && options.closeOnSuccess && modal) {
        setTimeout(function () {
          modal.hide();
        }, 250);
      }

      return true;
    } catch (error) {
      saving = false;
      setAutosaveStatus('Save failed');
      showSaveMessage(error.message || 'Save failed', 'danger');
      console.error('save error', error);
      return false;
    }
  }

  function showSaveMessage(text, kind) {
    const el = document.getElementById('saveAlert');
    if (!el) {
      return;
    }

    el.className = `alert alert-${kind === 'success' ? 'success' : 'danger'}`;
    el.textContent = text;
    el.classList.remove('d-none');
    setTimeout(function () {
      el.classList.add('d-none');
    }, 3000);
  }

  window.addEventListener('beforeunload', function () {
    if (!currentEditors || !modalDate) {
      return;
    }

    const payload = {
      date: modalDate.textContent,
      data: collectDataFromEditors(currentEditors),
      status: 'saved'
    };

    if (isAdmin) {
      payload.employee_id = contextEmployeeId;
    }

    fetch(buildApiUrl('save'), {
      method: 'POST',
      keepalive: true,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: JSON.stringify(payload)
    }).catch(function () {});
  });

  function setAutosaveStatus(text) {
    const el = document.getElementById('autosaveStatus');
    if (!el) {
      return;
    }

    el.textContent = text;
  }
});
