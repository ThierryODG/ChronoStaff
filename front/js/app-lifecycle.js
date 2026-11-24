// ChronoStaff PWA lifecycle
Object.assign(window.App, {
  init() {
    this.registerServiceWorker();
    try {
      this.bindEvents();
    } catch (err) {
      console.error('Erreur lors de la liaison des événements:', err);
    }

    setTimeout(() => {
      const splash = document.getElementById('splashscreen');
      if (splash) {
        splash.style.opacity = '0';
        setTimeout(() => {
          if (splash.parentNode) splash.remove();
        }, 400);
      }

      if (this.kiosk) {
        // Appareil déjà configuré en mode borne
        this.enterKioskMode();
      } else if (this.token && this.user) {
        this.checkTermsAcceptance();
      } else {
        this.showScreen('screen-login');
      }
    }, 500);

    // Surveillance de la connectivité et synchronisation automatique intelligente
    this.startBackgroundPing();
    window.addEventListener('online', () => {
      this.triggerAutoSync(false);
    });
  },

  // ==========================================
  // Service Worker & Notifications
  // ==========================================

  async registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;
    try {
      await navigator.serviceWorker.register('./sw.js');
    } catch (err) {
      console.error('Impossible d’enregistrer le service worker:', err);
      document.querySelectorAll('.notification-permission-status').forEach((element) => {
        element.textContent = 'Impossible d’activer les rappels sur cet appareil. Rechargez l’application et réessayez.';
      });
    }
  },

  updateNotificationControls() {
    const supported = 'Notification' in window;
    const permission = supported ? Notification.permission : 'unsupported';
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

    let message = 'Aucune autorisation de notification n’a encore été demandée.';
    let buttonText = 'Activer les rappels';
    let disabled = false;

    if (!supported) {
      message = 'Les notifications ne sont pas prises en charge par ce navigateur.';
      buttonText = 'Notifications indisponibles';
      disabled = true;
    } else if (permission === 'granted') {
      message = this.pushSubscriptionRegistered
        ? 'Les rappels serveur sont activés sur cet appareil.'
        : (this.pushSubscriptionError || 'Autorisation accordée. Appuyez pour inscrire cet appareil aux rappels serveur.');
      buttonText = this.pushSubscriptionRegistered ? 'Rappels activés' : 'Configurer les rappels';
      disabled = this.pushSubscriptionRegistered || !this.token;
    } else if (permission === 'denied') {
      message = 'Les notifications sont bloquées. Autorisez-les dans les réglages du navigateur.';
      buttonText = 'À autoriser dans les réglages';
      disabled = true;
    } else if (isIos && !isStandalone) {
      message = 'Sur iPhone ou iPad, ajoutez d’abord ChronoStaff à l’écran d’accueil (iOS 16.4 ou ultérieur).';
      buttonText = 'Ajouter à l’écran d’accueil';
      disabled = true;
    }

    document.querySelectorAll('.notification-permission-status').forEach((element) => {
      element.textContent = message;
    });
    document.querySelectorAll('.btn-enable-notifications').forEach((button) => {
      button.textContent = buttonText;
      button.disabled = disabled;
      button.classList.toggle('opacity-50', disabled);
      button.classList.toggle('cursor-not-allowed', disabled);
    });

  },

  async requestNotificationPermission() {
    if (!('Notification' in window)) {
      this.updateNotificationControls();
      return;
    }

    try {
      if (Notification.permission === 'default') {
        await Notification.requestPermission();
      }

      if (Notification.permission === 'granted') {
        await this.ensurePushSubscription();
      }
      this.updateNotificationControls();
    } catch (error) {
      console.error('Erreur lors de la demande d’autorisation des notifications:', error);
      document.querySelectorAll('.notification-permission-status').forEach((element) => {
        element.textContent = error instanceof Error
          ? error.message
          : 'Impossible d’activer les rappels. Vérifiez les réglages du navigateur.';
      });
    }
  },

  async ensurePushSubscription() {
    if (this.pushSubscriptionRegistered) return;
    if (this.pushSubscriptionSyncPromise) return this.pushSubscriptionSyncPromise;

    this.pushSubscriptionSyncPromise = this.registerPushSubscription();
    try {
      await this.pushSubscriptionSyncPromise;
    } finally {
      this.pushSubscriptionSyncPromise = null;
    }
  },

  async registerPushSubscription() {
    const setStatus = (message) => {
      document.querySelectorAll('.notification-permission-status').forEach((element) => {
        element.textContent = message;
      });
    };

    try {
      if (!this.token) throw new Error('Connectez-vous pour activer les rappels sur cet appareil.');
      if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        throw new Error('Les rappels Push nécessitent HTTPS et un navigateur compatible.');
      }

      setStatus('Inscription de cet appareil aux rappels…');
      const registration = await navigator.serviceWorker.ready;
      const keyResponse = await fetch(`${API_BASE}/notifications/vapid-public-key`, {
        headers: { 'Accept': 'application/json' }
      });
      const keyData = await keyResponse.json();
      if (!keyResponse.ok) throw new Error(keyData.message || 'La configuration des rappels serveur est indisponible.');

      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: this.decodePushPublicKey(keyData.public_key)
        });
      }

      const response = await fetch(`${API_BASE}/notifications/subscriptions`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(subscription.toJSON())
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Impossible d’inscrire cet appareil aux rappels.');

      this.pushSubscriptionRegistered = true;
      this.pushSubscriptionError = null;
      setStatus('Les rappels serveur sont activés sur cet appareil.');
      this.updateNotificationControls();
    } catch (error) {
      console.error('Erreur lors de l’inscription Push:', error);
      this.pushSubscriptionRegistered = false;
      this.pushSubscriptionError = error instanceof Error
        ? `Rappels non activés : ${error.message}`
        : 'Rappels non activés. Vérifiez la connexion et les réglages.';
      setStatus(this.pushSubscriptionError);
    }
  },

  decodePushPublicKey(encodedKey) {
    const padding = '='.repeat((4 - encodedKey.length % 4) % 4);
    const base64 = (encodedKey + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from(raw, (character) => character.charCodeAt(0));
  },

  async removePushSubscription() {
    if (!this.token || !('serviceWorker' in navigator)) return;

    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager?.getSubscription();
      if (!subscription) return;

      const removeRequest = fetch(`${API_BASE}/notifications/subscriptions`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ endpoint: subscription.endpoint })
      });

      await subscription.unsubscribe();
      const response = await removeRequest;
      if (!response.ok) throw new Error('Le serveur n’a pas supprimé l’inscription Push.');
    } catch (error) {
      console.error('Impossible de supprimer l’inscription Push à la déconnexion:', error);
    }
  },

  // ==========================================
  // PING D'ARRIÈRE-PLAN & SYNC AUTO (Optimisé batterie)
  // ==========================================
  startBackgroundPing() {
    const runPing = async () => {
      let nextIntervalMs = 2 * 60 * 60 * 1000; // 2 heures si en ligne et synchronisé
      try {
        const pending = await getOfflinePunches();
        if (pending.length > 0 || !navigator.onLine) {
          nextIntervalMs = 5 * 60 * 1000; // 5 minutes si offline ou pointages en attente
        }

        if (navigator.onLine) {
          const res = await fetch(`${API_BASE}/time`, { headers: { 'Accept': 'application/json' } });
          if (res.ok && pending.length > 0) {
            await this.triggerAutoSync(false);
            const remaining = await getOfflinePunches();
            nextIntervalMs = remaining.length > 0 ? 5 * 60 * 1000 : 2 * 60 * 60 * 1000;
          }
        }
      } catch {
        nextIntervalMs = 5 * 60 * 1000;
      }

      this.backgroundPingTimer = setTimeout(runPing, nextIntervalMs);
    };

    // Premier test 20s après le lancement
    this.backgroundPingTimer = setTimeout(runPing, 20 * 1000);
  },

  // ==========================================
  // ÉVÉNEMENTS
  // ==========================================
});
