// ChronoStaff PWA event bindings
Object.assign(window.App, {
  bindEvents() {
    // Formulaires de connexion
    document.getElementById('form-login').addEventListener('submit', (e) => this.handleLogin(e));
    document.getElementById('form-kiosk-login').addEventListener('submit', (e) => this.handleKioskLogin(e));

    // Navigation inférieure
    document.getElementById('btn-nav-punch').addEventListener('click', () => this.showScreen('screen-punch'));
    document.getElementById('btn-nav-history').addEventListener('click', () => {
      this.showScreen('screen-history');
      this.loadHistory();
    });
    document.getElementById('btn-nav-account').addEventListener('click', () => {
      this.updateNotificationControls();
      if (this.user && (this.user.role === 'manager' || this.user.role === 'admin')) {
        this.showScreen('screen-manager-account');
        this.loadManagerAccountInfo();
      } else {
        this.showScreen('screen-account');
        this.loadAccountInfo();
      }
    });
    document.getElementById('btn-nav-manager').addEventListener('click', () => {
      this.showScreen('screen-manager');
      this.loadManagerData();
    });

    // Écran Pointage — PIN clavier
    document.querySelectorAll('.pin-num-key').forEach(btn => {
      btn.addEventListener('click', (e) => this.handleInlinePinKey(e.currentTarget.dataset.val));
    });
    document.getElementById('btn-inline-pin-delete').addEventListener('click', () => this.handleInlinePinDelete());
    document.getElementById('btn-inline-pin-submit').addEventListener('click', () => this.submitInlinePin());

    // Scanner QR
    document.getElementById('btn-open-qr').addEventListener('click', () => this.startScanFlow());
    document.getElementById('btn-test-camera').addEventListener('click', () => this.startScanFlow());
    document.getElementById('btn-test-location').addEventListener('click', () => this.requestLocationPermission());
    document.getElementById('btn-close-scanner').addEventListener('click', () => this.stopScanner());
    document.getElementById('btn-scanner-switch-pin').addEventListener('click', () => this.stopScanner());
    document.getElementById('btn-switch-camera').addEventListener('click', () => this.switchCamera());

    // Légal
    document.getElementById('check-terms').addEventListener('change', (e) => {
      const btn = document.getElementById('btn-accept-terms');
      btn.disabled = !e.target.checked;
      btn.classList.toggle('opacity-50', !e.target.checked);
      btn.classList.toggle('cursor-not-allowed', !e.target.checked);
    });
    document.getElementById('btn-accept-terms').addEventListener('click', () => this.acceptTerms());
    document.querySelectorAll('.btn-enable-notifications').forEach((button) => {
      button.addEventListener('click', () => this.requestNotificationPermission());
    });

    // Synchronisation manuelle
    document.getElementById('btn-manual-sync').addEventListener('click', () => this.triggerAutoSync(true));

    // Filtre date historique & Justifications
    const dateFilter = document.getElementById('history-date-filter');
    if (dateFilter) {
      dateFilter.addEventListener('change', () => this.loadHistory());
    }
    const clearDateFilter = document.getElementById('btn-clear-date-filter');
    if (clearDateFilter) {
      clearDateFilter.addEventListener('click', () => {
        if (dateFilter) dateFilter.value = '';
        this.loadHistory();
      });
    }

    const btnOpenJustif = document.getElementById('btn-open-justification-modal');
    if (btnOpenJustif) {
      btnOpenJustif.addEventListener('click', () => {
        const modal = document.getElementById('modal-justification');
        const dateInput = document.getElementById('justif-date');
        if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
        document.getElementById('justif-error')?.classList.add('hidden');
        document.getElementById('justif-success')?.classList.add('hidden');
        modal?.classList.remove('hidden');
      });
    }

    const btnCloseJustif = document.getElementById('btn-close-justification');
    if (btnCloseJustif) {
      btnCloseJustif.addEventListener('click', () => {
        document.getElementById('modal-justification')?.classList.add('hidden');
      });
    }

    const formJustif = document.getElementById('form-justification');
    if (formJustif) {
      formJustif.addEventListener('submit', (e) => this.submitJustification(e));
    }

    // Téléchargement Rapport Mensuel Employé (PDF)
    const btnDownloadMonthlyReport = document.getElementById('btn-download-monthly-report');
    if (btnDownloadMonthlyReport) {
      btnDownloadMonthlyReport.addEventListener('click', () => this.downloadMonthlyReport());
    }

    // Téléchargement Badge
    const btnDownloadBadge = document.getElementById('btn-download-badge');
    if (btnDownloadBadge) {
      btnDownloadBadge.addEventListener('click', () => this.downloadBadge());
    }

    // Manager
    document.getElementById('btn-refresh-manager').addEventListener('click', () => this.loadManagerData());

    const mgrStart = document.getElementById('mgr-report-start');
    const mgrEnd = document.getElementById('mgr-report-end');
    if (mgrStart) mgrStart.addEventListener('change', () => this.loadManagerReports());
    if (mgrEnd) mgrEnd.addEventListener('change', () => this.loadManagerReports());
  },

  // ==========================================
  // NAVIGATION & ÉCRANS
  // ==========================================
});
