// ChronoStaff PWA history and reports
Object.assign(window.App, {
  async loadHistory() {
    const container = document.getElementById('history-list');
    container.innerHTML = '<div class="p-8 text-center text-slate-400 text-xs"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Chargement...</div>';

    const filterDate = document.getElementById('history-date-filter')?.value;
    let pendingPunches = await getOfflinePunches();
    if (filterDate) {
      pendingPunches = pendingPunches.filter(p => p.punched_at && p.punched_at.startsWith(filterDate));
    }
    let html = '';

    if (pendingPunches.length > 0) {
      html += `<div class="mb-4"><h3 class="text-xs font-bold uppercase tracking-wider text-amber-700 mb-2 flex items-center"><i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> En attente (${pendingPunches.length})</h3><div class="space-y-2">`;
      pendingPunches.forEach(p => {
        const d = new Date(p.punched_at);
        html += `
          <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <span class="w-9 h-9 rounded-xl bg-amber-200 text-amber-800 flex items-center justify-center font-bold text-xs">${p.type === 'in' ? 'IN' : 'OUT'}</span>
              <div>
                <div class="text-sm font-bold text-slate-900">${p.type === 'in' ? 'Arrivée' : 'Départ'}</div>
                <div class="text-xs text-slate-500">${d.toLocaleDateString('fr-FR')} · ${d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</div>
              </div>
            </div>
            <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900">En attente</span>
          </div>`;
      });
      html += '</div></div>';
    }

    if (navigator.onLine && this.token) {
      try {
        const res = await fetch(`${API_BASE}/employee/history`, {
          headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' }
        });
        const data = await res.json();
        let items = data.data || [];
        if (filterDate) {
          items = items.filter(item => {
            return item.date === filterDate || (item.punched_at && item.punched_at.startsWith(filterDate));
          });
        }
        if (items.length > 0) {
          html += '<h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Historique Validé</h3><div class="space-y-2">';
          items.forEach(item => {
            html += `
              <div class="p-4 bg-white border border-slate-200 rounded-2xl flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-3">
                  <span class="w-9 h-9 rounded-xl ${item.type === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'} flex items-center justify-center font-bold text-xs">${item.type.toUpperCase()}</span>
                  <div>
                    <div class="text-sm font-bold text-slate-900">${item.type === 'in' ? 'Arrivée' : 'Départ'}</div>
                    <div class="text-xs text-slate-400">${item.date_formatted} · ${item.time_formatted} · ${item.department_name}</div>
                  </div>
                </div>
                <div class="text-right">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700"><i class="fa-solid fa-check mr-0.5"></i> Validé</span>
                  ${item.is_on_site ? '<div class="text-[10px] text-emerald-600 mt-0.5">Sur site</div>' : '<div class="text-[10px] text-slate-400 mt-0.5">À distance</div>'}
                </div>
              </div>`;
          });
          html += '</div>';
        } else if (pendingPunches.length === 0) {
          html = filterDate
            ? `<div class="p-12 text-center text-slate-400 text-xs">Aucun pointage trouvé pour le ${filterDate}.</div>`
            : '<div class="p-12 text-center text-slate-400 text-xs">Aucun historique disponible.</div>';
        }
      } catch {
        if (pendingPunches.length === 0) {
          html = '<div class="p-6 text-center text-slate-400 text-xs">Historique distant indisponible.</div>';
        }
      }
    } else if (pendingPunches.length === 0) {
      html = '<div class="p-6 text-center text-slate-400 text-xs">Connexion requise pour consulter l\'historique.</div>';
    }

    container.innerHTML = html;
  },

  // ==========================================
  // JUSTIFICATION (Salarié)
  // ==========================================

  async submitJustification(e) {
    e.preventDefault();
    const date = document.getElementById('justif-date').value;
    const reason = document.getElementById('justif-reason').value;
    const details = document.getElementById('justif-details').value;
    const errEl = document.getElementById('justif-error');
    const succEl = document.getElementById('justif-success');
    const submitBtn = document.getElementById('btn-submit-justification');

    errEl.classList.add('hidden');
    succEl.classList.add('hidden');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Envoi en cours...';

    try {
      const res = await fetch(`${API_BASE}/employee/justification`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.token}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ date, type: reason, reason: details, details })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Erreur lors de l\'envoi');
      succEl.innerText = data.message || 'Justification transmise avec succès !';
      succEl.classList.remove('hidden');
      document.getElementById('justif-details').value = '';
      setTimeout(() => {
        document.getElementById('modal-justification')?.classList.add('hidden');
      }, 1800);
    } catch (err) {
      errEl.innerText = err.message;
      errEl.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = 'Envoyer ma justification';
    }
  },

  // ==========================================
  // TÉLÉCHARGEMENT BILAN MENSUEL EMPLOYÉ (PDF)
  // ==========================================

  async downloadMonthlyReport() {
    if (!this.token) {
      alert('Veuillez vous connecter pour télécharger votre bilan.');
      return;
    }
    const btn = document.getElementById('btn-download-monthly-report');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Génération du bilan...';
    }

    try {
      const now = new Date();
      const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
      const res = await fetch(`${API_BASE}/employee/monthly-report?month=${currentMonth}`, {
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Accept': 'application/json'
        }
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Erreur lors de la génération du rapport');

      if (data.report_url) {
        window.open(data.report_url, '_blank');
      } else {
        throw new Error('URL du rapport indisponible');
      }
    } catch (err) {
      alert(err.message || 'Impossible de télécharger le bilan mensuel.');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    }
  },

  // ==========================================
  // TÉLÉCHARGEMENT BADGE NUMÉRIQUE (PNG)
  // ==========================================
});
