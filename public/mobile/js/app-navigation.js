// ChronoStaff PWA navigation
Object.assign(window.App, {
  showScreen(screenId) {
    document.querySelectorAll('.app-screen').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById(screenId);
    if (target) target.classList.remove('hidden');

    const appHeader = document.getElementById('app-header');
    const bottomNav = document.getElementById('bottom-nav');
    const kioskBar = document.getElementById('kiosk-bar');

    // Mettre à jour l'état actif des boutons
    const navMap = [
      { id: 'btn-nav-punch', screens: ['screen-punch'] },
      { id: 'btn-nav-manager', screens: ['screen-manager'] },
      { id: 'btn-nav-history', screens: ['screen-history'] },
      { id: 'btn-nav-account', screens: ['screen-account', 'screen-manager-account'] },
    ];
    navMap.forEach(b => {
      const el = document.getElementById(b.id);
      if (!el) return;
      if (b.screens.includes(screenId)) {
        el.className = el.className.replace('nav-btn-inactive', 'nav-btn-active');
        el.classList.add('nav-btn-active');
        el.classList.remove('nav-btn-inactive');
      } else {
        el.className = el.className.replace('nav-btn-active', 'nav-btn-inactive');
        el.classList.add('nav-btn-inactive');
        el.classList.remove('nav-btn-active');
      }
    });

    if (screenId === 'screen-login' || screenId === 'screen-legal') {
      appHeader.classList.add('hidden');
      bottomNav.classList.add('hidden');
      kioskBar.classList.add('hidden');
    } else if (this.kiosk) {
      // En mode borne : logo en haut + bandeau borne en bas (pas de nav perso)
      appHeader.classList.remove('hidden');
      bottomNav.classList.add('hidden');
      kioskBar.classList.remove('hidden');
      document.getElementById('kiosk-name-badge').innerText = this.kiosk.name || 'Poste Fixe';
    } else {
      // En mode individuel
      appHeader.classList.remove('hidden');
      bottomNav.classList.remove('hidden');
      kioskBar.classList.add('hidden');

      const isManager = this.user && (this.user.role === 'manager' || this.user.role === 'admin');
      const managerNavBtn = document.getElementById('btn-nav-manager');
      const historyNavBtn = document.getElementById('btn-nav-history');
      const punchNavBtn = document.getElementById('btn-nav-punch');

      if (isManager) {
        managerNavBtn?.classList.remove('hidden');
        punchNavBtn?.classList.add('hidden');
        historyNavBtn?.classList.add('hidden');
      } else {
        managerNavBtn?.classList.add('hidden');
        punchNavBtn?.classList.remove('hidden');
        historyNavBtn?.classList.remove('hidden');
      }
    }
  },

  // ==========================================
  // COMMUTATION MODE DE LOGIN (Salarié vs Borne)
  // ==========================================
});
