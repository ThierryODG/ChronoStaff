// ChronoStaff PWA authentication
Object.assign(window.App, {
  switchLoginMode(mode) {
    this.loginMode = mode;
    const tabAgent = document.getElementById('tab-login-agent');
    const tabKiosk = document.getElementById('tab-login-kiosk');
    const formAgent = document.getElementById('form-login');
    const formKiosk = document.getElementById('form-kiosk-login');
    const errBox = document.getElementById('login-error');
    errBox.classList.add('hidden');

    if (mode === 'agent') {
      tabAgent.className = 'flex-1 py-2 text-xs font-bold rounded-xl bg-white text-blue-600 shadow-xs transition';
      tabKiosk.className = 'flex-1 py-2 text-xs font-bold rounded-xl text-slate-500 hover:text-slate-800 transition';
      formAgent.classList.remove('hidden');
      formKiosk.classList.add('hidden');
    } else {
      tabKiosk.className = 'flex-1 py-2 text-xs font-bold rounded-xl bg-white text-violet-600 shadow-xs transition';
      tabAgent.className = 'flex-1 py-2 text-xs font-bold rounded-xl text-slate-500 hover:text-slate-800 transition';
      formKiosk.classList.remove('hidden');
      formAgent.classList.add('hidden');
    }
  },

  // ==========================================
  // AUTHENTIFICATION SALARIÉ
  // ==========================================

  async handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const errorEl = document.getElementById('login-error');
    const btn = document.getElementById('btn-login-submit');

    errorEl.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Connexion...';

    try {
      const res = await fetch(`${API_BASE}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email, password, device_name: 'PWA-Mobile' })
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Identifiants invalides');

      this.token = data.access_token;
      this.user = data.user;
      this.kiosk = null;
      localStorage.setItem('cs_token', this.token);
      localStorage.setItem('cs_user', JSON.stringify(this.user));
      localStorage.removeItem('cs_kiosk');

      this.checkTermsAcceptance();
    } catch (err) {
      errorEl.innerText = err.message;
      errorEl.classList.remove('hidden');
    } finally {
      btn.disabled = false;
      btn.innerHTML = 'Se connecter';
    }
  },

  // ==========================================
  // AUTHENTIFICATION BORNE (Kiosk Connect)
  // ==========================================

  async handleKioskLogin(e) {
    e.preventDefault();
    const deviceCode = document.getElementById('kiosk-device-code').value.trim();
    const accessKey = document.getElementById('kiosk-access-key').value.trim();
    const errorEl = document.getElementById('login-error');
    const btn = document.getElementById('btn-kiosk-submit');

    errorEl.classList.add('hidden');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Activation de la borne...';

    try {
      const res = await fetch(`${API_BASE}/auth/kiosk-connect`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ device_code: deviceCode, access_key: accessKey })
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Identifiants de borne incorrects');

      this.kiosk = data.kiosk;
      this.token = data.access_token || null;
      this.user = null;
      localStorage.setItem('cs_kiosk', JSON.stringify(this.kiosk));
      if (this.token) localStorage.setItem('cs_token', this.token);
      localStorage.removeItem('cs_user');

      this.enterKioskMode();
    } catch (err) {
      errorEl.innerText = err.message;
      errorEl.classList.remove('hidden');
    } finally {
      btn.disabled = false;
      btn.innerHTML = 'Activer cette Borne';
    }
  },

  enterKioskMode() {
    this.showScreen('screen-punch');
    this.updateNotificationControls();
  },

  async handleKioskLogout() {
    if (confirm('Voulez-vous désactiver cette borne sur cet appareil ?')) {
      await this.removePushSubscription();
      this.kiosk = null;
      this.token = null;
      this.pushSubscriptionRegistered = false;
      this.pushSubscriptionError = null;
      localStorage.removeItem('cs_kiosk');
      localStorage.removeItem('cs_token');
      this.showScreen('screen-login');
      this.switchLoginMode('kiosk');
    }
  },

  checkTermsAcceptance() {
    const localAcceptance = this.user
      ? localStorage.getItem(`cs_terms_accepted_${this.user.id}`)
      : null;

    if (this.user && (this.user.needs_terms_acceptance || localAcceptance !== TERMS_VERSION)) {
      this.showScreen('screen-legal');
    } else if (this.user && (this.user.role === 'manager' || this.user.role === 'admin')) {
      this.showScreen('screen-manager');
      this.loadManagerData();
    } else {
      this.showScreen('screen-punch');
    }
  },

  async acceptTerms() {
    const button = document.getElementById('btn-accept-terms');
    const errorEl = document.getElementById('legal-error');
    button.disabled = true;
    errorEl.classList.add('hidden');

    try {
      const response = await fetch(`${API_BASE}/legal/accept`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' }
      });

      if (!response.ok) {
        throw new Error('Impossible d’enregistrer votre acceptation. Vérifiez votre connexion et réessayez.');
      }

      this.user.needs_terms_acceptance = false;
      localStorage.setItem(`cs_terms_accepted_${this.user.id}`, TERMS_VERSION);
      localStorage.setItem('cs_user', JSON.stringify(this.user));
      this.showScreen('screen-punch');
    } catch (error) {
      errorEl.innerText = error instanceof Error
        ? error.message
        : 'Une erreur est survenue. Vérifiez votre connexion et réessayez.';
      errorEl.classList.remove('hidden');
      button.disabled = !document.getElementById('check-terms').checked;
    } finally {
      button.innerText = 'Continuer';
    }
  },

  async handleLogout() {
    if (confirm('Voulez-vous vous déconnecter de ChronoStaff ?')) {
      await this.removePushSubscription();
      if (this.token && navigator.onLine) {
        fetch(`${API_BASE}/auth/logout`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${this.token}`, 'Accept': 'application/json' }
        }).catch(() => {});
      }
      this.token = null;
      this.user = null;
      this.kiosk = null;
      this.pushSubscriptionRegistered = false;
      this.pushSubscriptionError = null;
      localStorage.removeItem('cs_token');
      localStorage.removeItem('cs_user');
      localStorage.removeItem('cs_kiosk');
      this.showScreen('screen-login');
      this.switchLoginMode('agent');
    }
  },

  // ==========================================
  // PAVÉ PIN (Écran Pointage Principal)
  // ==========================================
});
