// ChronoStaff PWA manager screens
Object.assign(window.App, {
  switchManagerTab(tab) {
    this.managerTab = tab;
    const panels = { dashboard: 'mgr-panel-dashboard', reports: 'mgr-panel-reports' };
    const tabs = { dashboard: 'mgr-tab-dashboard', reports: 'mgr-tab-reports' };

    Object.keys(panels).forEach(key => {
      const panel = document.getElementById(panels[key]);
      const tabBtn = document.getElementById(tabs[key]);
      if (panel && tabBtn) {
        if (key === tab) {
          panel.classList.remove('hidden');
          tabBtn.className = 'manager-tab is-active flex-1 py-2 text-sm font-semibold transition';
        } else {
          panel.classList.add('hidden');
          tabBtn.className = 'manager-tab flex-1 py-2 text-sm font-semibold transition';
        }
      }
    });

    if (tab === 'dashboard') {
      this.loadManagerData();
    } else if (tab === 'reports') {
      this.loadManagerReports();
    }
  },

  // ==========================================
  // DASHBOARD MANAGER (Vert Sombre & Formes Nettes)
  // ==========================================

  async loadManagerData() {
    if (this.user) {
      const welcomeEl = document.getElementById('manager-welcome');
      const h = new Date().getHours();
      const greeting = h < 12 ? 'Bonjour' : h < 18 ? 'Bon après-midi' : 'Bonsoir';
      const firstName = (this.user.name || 'Manager').split(' ')[0];
      if (welcomeEl) welcomeEl.innerText = `${greeting}, ${firstName} 👋`;
    }

    const dateLineEl = document.getElementById('manager-date-line');
    if (dateLineEl) {
      dateLineEl.innerText = new Date().toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      });
    }

    const listEl = document.getElementById('manager-present-list');
    listEl.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs bg-slate-900/80 rounded-xl border border-slate-800"><i class="fa-solid fa-spinner fa-spin text-xl mb-2 block text-emerald-500"></i> Actualisation...</div>';

    if (!navigator.onLine) {
      listEl.innerHTML = '<div class="p-6 bg-amber-950/40 border border-amber-800/60 rounded-xl text-amber-300 text-xs text-center font-medium"><i class="fa-solid fa-wifi-slash text-2xl block mb-2 text-amber-400"></i> Hors-ligne — données en direct indisponibles</div>';
      return;
    }

    try {
      const res = await fetch(`${API_BASE}/admin/dashboard/who-is-here`, {
        headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' }
      });
      const data = await res.json();

      const present = data.summary?.currently_present ?? 0;
      const absent = data.summary?.currently_absent ?? 0;
      const total = data.summary?.total_employees ?? (present + absent);
      const punctuality = data.summary?.punctuality_rate ?? 100;
      const hoursToday = data.summary?.total_hours_today ?? 0;
      const lates = data.summary?.late_arrivals_count ?? 0;
      const schedule = data.summary?.work_schedule || { start: '08:00', end: '17:00' };

      // 1. Horaires & Jauge
      const schedEl = document.getElementById('manager-work-schedule');
      if (schedEl) schedEl.innerText = `${schedule.start} — ${schedule.end}`;

      const ratioEl = document.getElementById('manager-presence-ratio');
      if (ratioEl) ratioEl.innerText = `${present} / ${total}`;

      const pct = total > 0 ? Math.round((present / total) * 100) : 0;
      const barEl = document.getElementById('manager-presence-bar');
      if (barEl) barEl.style.width = `${pct}%`;

      const pctEl = document.getElementById('manager-presence-percent');
      if (pctEl) pctEl.innerText = `${pct}% de l'effectif présent`;

      // 2. Mise à jour des 6 KPIs
      const elPunct = document.getElementById('manager-stat-punctuality');
      if (elPunct) elPunct.innerText = `${punctuality}%`;

      const elPres = document.getElementById('manager-stat-present');
      if (elPres) elPres.innerText = present;

      const elAbs = document.getElementById('manager-stat-absent');
      if (elAbs) elAbs.innerText = absent;

      const elTot = document.getElementById('manager-stat-total');
      if (elTot) elTot.innerText = total;

      const elHours = document.getElementById('manager-stat-hours-today');
      if (elHours) elHours.innerText = `${hoursToday} h`;

      const elLates = document.getElementById('manager-stat-lates');
      if (elLates) elLates.innerText = `${lates}`;

      let onSiteCount = 0;
      let offSiteCount = 0;
      (data.present_employees || []).forEach(p => {
        if (p.is_on_site) onSiteCount++;
        else offSiteCount++;
      });
      const splitEl = document.getElementById('manager-stat-onsite-split');
      if (splitEl) splitEl.innerText = `${onSiteCount} site / ${offSiteCount} dist`;

      const refreshEl = document.getElementById('manager-last-refresh');
      if (refreshEl) {
        refreshEl.innerText = 'Màj ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
      }

      // 3. Liste des présents
      if (!data.present_employees || data.present_employees.length === 0) {
        listEl.innerHTML = `
          <div class="p-6 text-center bg-slate-900/80 rounded-xl border border-slate-800 shadow-xs">
            <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center mx-auto mb-2">
              <i class="fa-solid fa-users text-lg text-slate-500"></i>
            </div>
            <p class="text-xs font-semibold text-slate-300">Aucun salarié en service</p>
            <p class="text-[10px] text-slate-500 mt-0.5">L'équipe n'a pas encore pointé ou est en repos</p>
          </div>`;
      } else {
        let html = '';
        data.present_employees.forEach((p, i) => {
          const initials = p.name.split(' ').map(w => w[0]).join('').substr(0, 2).toUpperCase();
          const colors = ['bg-emerald-600', 'bg-teal-600', 'bg-blue-600', 'bg-indigo-600', 'bg-cyan-600'];
          const color = colors[i % colors.length];
          const isLate = p.clock_in_time > schedule.start;
          html += `
            <div class="p-3 bg-slate-900/90 border border-slate-800 hover:border-emerald-900/60 rounded-xl flex items-center justify-between shadow-xs transition">
              <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl ${color} text-white font-bold text-xs flex items-center justify-center shadow-xs">
                  ${initials}
                </div>
                <div>
                  <div class="text-xs font-bold text-white flex items-center">
                    <span>${p.name}</span>
                    ${isLate ? '<span class="ml-1.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-950 border border-rose-700/60 text-rose-300">Retard</span>' : '<span class="ml-1.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-950 border border-emerald-700/60 text-emerald-300">À l\'heure</span>'}
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    <span class="text-emerald-400 font-semibold">${p.department}</span>
                    <span class="mx-1 text-slate-700">•</span>
                    <span>Arrivé à ${p.clock_in_time}</span>
                  </div>
                </div>
              </div>
              <div class="text-right flex flex-col items-end space-y-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold ${p.is_on_site ? 'bg-emerald-950/80 border border-emerald-700/60 text-emerald-300' : 'bg-amber-950/80 border border-amber-700/60 text-amber-300'}">
                  <span class="w-1.5 h-1.5 rounded-full ${p.is_on_site ? 'bg-emerald-400' : 'bg-amber-400'} mr-1"></span>
                  ${p.location_label}
                </span>
                <span class="text-[10px] font-mono text-slate-400">${p.elapsed_time}</span>
              </div>
            </div>`;
        });
        listEl.innerHTML = html;
      }

      // 4. Liste des non pointés (Absents / En attente)
      const absentListEl = document.getElementById('manager-absent-list');
      if (absentListEl) {
        if (!data.absent_employees || data.absent_employees.length === 0) {
          absentListEl.innerHTML = '<div class="p-3 text-center text-slate-500 text-xs bg-slate-900/60 rounded-xl border border-slate-800/80">Tous les salariés de l\'effectif sont présents !</div>';
        } else {
          let aHtml = '';
          data.absent_employees.forEach(a => {
            aHtml += `
              <div class="p-2.5 bg-slate-900/60 border border-slate-800/80 rounded-xl flex items-center justify-between text-xs">
                <div class="flex items-center space-x-2.5">
                  <div class="w-7 h-7 rounded-lg bg-slate-800 text-slate-400 font-bold text-[10px] flex items-center justify-center">
                    <i class="fa-solid fa-user-xmark text-slate-500"></i>
                  </div>
                  <div>
                    <div class="text-xs font-semibold text-slate-300">${a.name}</div>
                    <div class="text-[10px] text-slate-500">${a.department || 'Général'}</div>
                  </div>
                </div>
                <div class="text-right text-[10px] text-slate-500">
                  <span>Dernier vu : ${a.last_seen || 'Jamais'}</span>
                </div>
              </div>`;
          });
          absentListEl.innerHTML = aHtml;
        }
      }

      // 5. Justifications Récentes
      const justifEl = document.getElementById('manager-justifications-list');
      if (justifEl) {
        if (!data.recent_justifications || data.recent_justifications.length === 0) {
          justifEl.innerHTML = '<div class="p-4 text-center text-slate-500 text-xs bg-slate-900/60 rounded-xl border border-slate-800/80">Aucune justification récente déposée.</div>';
        } else {
          let jHtml = '';
          data.recent_justifications.forEach(j => {
            const statusClass = j.status === 'approved'
              ? 'bg-emerald-950/80 border-emerald-700/50 text-emerald-300'
              : (j.status === 'rejected' ? 'bg-rose-950/80 border-rose-700/50 text-rose-300' : 'bg-amber-950/80 border-amber-700/50 text-amber-300');
            const statusLabel = j.status === 'approved' ? 'Validé' : (j.status === 'rejected' ? 'Rejeté' : 'En attente');

            const safeReason = encodeURIComponent(j.reason || 'Aucun détail');
            const safeEmp = encodeURIComponent(j.employee_name || 'Employé');
            const safeType = encodeURIComponent(j.type || 'Justification');

            jHtml += `
              <div class="p-3 bg-slate-900/90 border border-slate-800 rounded-xl flex items-center justify-between cursor-pointer hover:border-emerald-800/60 transition"
                   onclick="App.viewManagerJustification(decodeURIComponent('${safeEmp}'), '${j.date}', decodeURIComponent('${safeType}'), decodeURIComponent('${safeReason}'))">
                <div>
                  <div class="text-xs font-bold text-white">${j.employee_name}</div>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    <span class="text-amber-400 uppercase font-semibold">${j.type}</span>
                    <span class="mx-1 text-slate-600">•</span>
                    <span>${j.date}</span>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border ${statusClass}">
                    ${statusLabel}
                  </span>
                  <i class="fa-solid fa-chevron-right text-[10px] text-slate-500"></i>
                </div>
              </div>`;
          });
          justifEl.innerHTML = jHtml;
        }
      }

    } catch {
      listEl.innerHTML = '<div class="p-4 text-center bg-rose-950/40 border border-rose-800/50 rounded-xl text-rose-300 text-xs">Erreur lors du chargement des données.</div>';
    }
  },

  // Modal de consultation rapide de la justification

  viewManagerJustification(employee, date, type, reason) {
    document.getElementById('mgr-modal-justif-employee').innerText = employee;
    document.getElementById('mgr-modal-justif-date').innerText = date;
    document.getElementById('mgr-modal-justif-type').innerText = type;
    document.getElementById('mgr-modal-justif-reason').innerText = reason;
    document.getElementById('modal-manager-justif').classList.remove('hidden');
  },

  // ==========================================
  // RAPPORTS ÉQUIPE (Adaptation Complète Web -> Mobile)
  // ==========================================

  setReportRange(type) {
    const now = new Date();
    const startInput = document.getElementById('mgr-report-start');
    const endInput = document.getElementById('mgr-report-end');

    if (type === 'today') {
      const today = now.toISOString().split('T')[0];
      if (startInput) startInput.value = today;
      if (endInput) endInput.value = today;
    } else if (type === 'week') {
      const d = new Date(now);
      const day = d.getDay();
      const diff = d.getDate() - day + (day === 0 ? -6 : 1);
      const monday = new Date(d.setDate(diff)).toISOString().split('T')[0];
      const sunday = new Date(d.setDate(diff + 6)).toISOString().split('T')[0];
      if (startInput) startInput.value = monday;
      if (endInput) endInput.value = sunday;
    } else if (type === 'month') {
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
      const today = now.toISOString().split('T')[0];
      if (startInput) startInput.value = firstDay;
      if (endInput) endInput.value = today;
    }
    this.loadManagerReports();
  },

  exportManagerReport(type) {
    const startInput = document.getElementById('mgr-report-start');
    const endInput = document.getElementById('mgr-report-end');
    const start = startInput && startInput.value ? startInput.value : '';
    const end = endInput && endInput.value ? endInput.value : '';

    if (type === 'pdf') {
      window.open(`/admin/reports/export-pdf?from=${encodeURIComponent(start)}&to=${encodeURIComponent(end)}`, '_blank');
    } else if (type === 'csv') {
      window.open(`${API_BASE}/admin/reports/export-csv?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`, '_blank');
    }
  },

  async loadManagerReports() {
    const listEl = document.getElementById('manager-team-reports-list');
    const startInput = document.getElementById('mgr-report-start');
    const endInput = document.getElementById('mgr-report-end');

    if (!startInput.value || !endInput.value) {
      const now = new Date();
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
      const today = now.toISOString().split('T')[0];
      if (!startInput.value) startInput.value = firstDay;
      if (!endInput.value) endInput.value = today;
    }

    const startDate = startInput.value;
    const endDate = endInput.value;

    listEl.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs bg-slate-900/80 rounded-xl border border-slate-800"><i class="fa-solid fa-spinner fa-spin text-xl mb-2 block text-emerald-500"></i> Calcul des heures de l\'équipe...</div>';

    try {
      const res = await fetch(`${API_BASE}/admin/reports?start_date=${startDate}&end_date=${endDate}`, {
        headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' }
      });
      const data = await res.json();

      const employees = data.employees || [];
      const countEl = document.getElementById('mgr-report-count');
      if (countEl) countEl.innerText = `${employees.length} salarié(s)`;

      // Totaux de synthèse
      let totalShifts = 0;
      let totalRaw = 0;
      let totalPaidMins = 0;

      employees.forEach(emp => {
        totalShifts += emp.total_shifts || 0;
        totalRaw += emp.total_raw_hours || 0;
        totalPaidMins += (emp.total_adjusted_hours || 0) * 60;
      });

      const repEmp = document.getElementById('mgr-rep-total-emp');
      if (repEmp) repEmp.innerText = employees.length;

      const repShifts = document.getElementById('mgr-rep-total-shifts');
      if (repShifts) repShifts.innerText = totalShifts;

      const repRaw = document.getElementById('mgr-rep-total-raw');
      if (repRaw) repRaw.innerText = `${Math.round(totalRaw * 10) / 10} h`;

      const paidH = Math.floor(totalPaidMins / 60);
      const paidM = Math.round(totalPaidMins % 60);
      const repPaid = document.getElementById('mgr-rep-total-paid');
      if (repPaid) repPaid.innerText = `${paidH}h ${String(paidM).padStart(2, '0')}m`;

      if (employees.length === 0) {
        listEl.innerHTML = '<div class="p-6 text-center text-slate-400 text-xs bg-slate-900/80 rounded-xl border border-slate-800">Aucune activité enregistrée sur cette période.</div>';
        return;
      }

      let html = '';
      employees.forEach(emp => {
        const shifts = emp.shifts || [];
        html += `
          <div class="p-3.5 bg-slate-900/90 border border-slate-800 hover:border-emerald-900/60 rounded-xl space-y-2.5 transition shadow-xs">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-xs font-bold text-white">${emp.name}</div>
                <div class="text-[10px] text-slate-400">${emp.department || 'Général'} · <span class="text-emerald-400 font-semibold">${emp.total_shifts} shift(s)</span></div>
              </div>
              <div class="text-right">
                <div class="text-sm font-black text-emerald-400 font-mono">${emp.total_formatted || emp.total_adjusted_hours + ' h'}</div>
                <div class="text-[9px] text-slate-500 uppercase font-semibold">Total payé</div>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1.5 border-t border-slate-800/80 text-[10px] text-slate-400">
              <span>Brut : ${emp.total_raw_hours} h</span>
              <span>Pause : -${emp.total_break_deducted_minutes} min</span>
              <button type="button" onclick="App.toggleEmployeeShifts(${emp.id})" class="text-emerald-400 hover:underline font-semibold flex items-center">
                <span>Détails (${shifts.length})</span>
                <i class="fa-solid fa-chevron-down ml-1 text-[8px]" id="icon-shift-toggle-${emp.id}"></i>
              </button>
            </div>

            <!-- Accordéon des shifts détaillés de ce salarié -->
            <div id="shifts-emp-${emp.id}" class="hidden pt-2 border-t border-slate-800/80 space-y-1.5">
              ${shifts.length === 0 ? '<div class="text-[10px] text-slate-500 italic">Aucun shift enregistré sur cette période.</div>' : ''}
              ${shifts.map(s => `
                <div class="p-2 bg-slate-950/70 border border-slate-800/60 rounded-lg flex items-center justify-between text-[10px]">
                  <div>
                    <div class="font-bold text-slate-200">${s.date}</div>
                    <div class="text-slate-400 mt-0.5">
                      <span>IN : ${s.in_time}</span>
                      <span class="mx-1">•</span>
                      <span>OUT : ${s.out_time || 'En cours...'}</span>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold ${s.is_on_site ? 'bg-emerald-950 border border-emerald-800 text-emerald-300' : 'bg-slate-800 text-slate-400'}">
                      ${s.is_on_site ? 'Sur site' : 'À distance'}
                    </span>
                    <div class="font-mono font-bold text-emerald-400 mt-0.5">${s.formatted_duration || s.raw_duration}</div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>`;
      });

      listEl.innerHTML = html;
    } catch {
      listEl.innerHTML = '<div class="p-4 text-center bg-rose-950/40 border border-rose-800/50 rounded-xl text-rose-300 text-xs">Erreur lors du calcul du rapport d\'équipe.</div>';
    }
  },

  toggleEmployeeShifts(empId) {
    const el = document.getElementById(`shifts-emp-${empId}`);
    const icon = document.getElementById(`icon-shift-toggle-${empId}`);
    if (el) {
      const isHidden = el.classList.contains('hidden');
      if (isHidden) {
        el.classList.remove('hidden');
        if (icon) icon.className = 'fa-solid fa-chevron-up ml-1 text-[8px]';
      } else {
        el.classList.add('hidden');
        if (icon) icon.className = 'fa-solid fa-chevron-down ml-1 text-[8px]';
      }
    }
  }
});
