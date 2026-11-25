// ChronoStaff PWA punch flow
Object.assign(window.App, {
  handleInlinePinKey(val) {
    if (this.pinLocked) return;
    if (this.pinEntered.length < 4) {
      this.pinEntered += val;
      this.updateInlinePinDisplay();
      if (this.pinEntered.length === 4) {
        // Bloquer immédiatement le clavier pour éviter les doubles clics
        this.lockPinKeypad(true);
        setTimeout(() => this.submitInlinePin(), 120);
      }
    }
  },

  handleInlinePinDelete() {
    if (this.pinLocked) return;
    this.pinEntered = this.pinEntered.slice(0, -1);
    this.updateInlinePinDisplay();
  },

  lockPinKeypad(locked) {
    this.pinLocked = locked;
    const keys = document.querySelectorAll('.pin-num-key, #btn-inline-pin-delete, #btn-inline-pin-submit, #btn-open-qr');
    keys.forEach(k => {
      k.disabled = locked;
      k.style.opacity = locked ? '0.4' : '1';
    });

    const statusLabel = document.getElementById('pin-status-label');
    if (statusLabel) {
      statusLabel.innerHTML = locked
        ? '<i class="fa-solid fa-spinner fa-spin mr-1.5 text-blue-600"></i><span class="text-blue-600 font-semibold">Vérification...</span>'
        : '';
    }
  },

  updateInlinePinDisplay() {
    const dots = document.querySelectorAll('.pin-dot');
    dots.forEach((dot, index) => {
      if (index < this.pinEntered.length) {
        dot.classList.add('bg-blue-600', 'border-blue-600', 'scale-110');
        dot.classList.remove('border-slate-300');
      } else {
        dot.classList.remove('bg-blue-600', 'border-blue-600', 'scale-110');
        dot.classList.add('border-slate-300');
      }
    });

    const btnSubmit = document.getElementById('btn-inline-pin-submit');
    if (btnSubmit) {
      btnSubmit.disabled = this.pinEntered.length !== 4 || this.pinLocked;
      btnSubmit.classList.toggle('opacity-40', this.pinEntered.length !== 4);
      btnSubmit.classList.toggle('opacity-100', this.pinEntered.length === 4);
    }
  },

  submitInlinePin() {
    if (this.pinEntered.length === 4) {
      const pin = this.pinEntered;
      this.executePunch({ pin_code: pin });
    }
  },

  // ==========================================
  // SCAN QR (Caméra avec bascule)
  // ==========================================

  async detectCameras() {
    try {
      this.availableCameras = await Html5Qrcode.getCameras();
      return this.availableCameras;
    } catch (error) {
      console.warn('Impossible de détecter les caméras:', error);
      this.availableCameras = [];
      return [];
    }
  },

  async startScanFlow() {
    document.getElementById('modal-scanner').classList.remove('hidden');
    document.getElementById('camera-error').classList.add('hidden');
    this.availableCameras = [];
    this.currentCameraIndex = 0;

    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
      this.showCameraError('La caméra nécessite une adresse HTTPS sécurisée. Ouvrez ChronoStaff avec son adresse HTTPS, puis réessayez.');
      return;
    }

    if (typeof Html5Qrcode === 'undefined') {
      this.showCameraError('Le scanner QR n’a pas pu être chargé. Vérifiez votre connexion et rechargez l’application.');
      return;
    }

    await this.startCamera();
  },

  async startCamera() {
    if (this.html5QrCode) {
      await this.cleanupCamera();
    }

    try {
      this.html5QrCode = new Html5Qrcode('qr-reader');
      const selectedCamera = this.availableCameras[this.currentCameraIndex];
      const cameraConfig = selectedCamera
        ? { deviceId: { exact: selectedCamera.id } }
        : { facingMode: 'environment' };

      await this.html5QrCode.start(
        cameraConfig,
        { fps: 10, qrbox: { width: 240, height: 240 } },
        (decodedText) => {
          this.stopScanner();
          this.executePunch({ qr_token: decodedText });
        },
        () => {}
      );
      this.isScanning = true;
      const cameraStatus = document.getElementById('camera-permission-status');
      if (cameraStatus) cameraStatus.textContent = 'Caméra autorisée sur cet appareil.';

      await this.detectCameras();
      const switchBtn = document.getElementById('btn-switch-camera');
      switchBtn.classList.toggle('hidden', this.availableCameras.length <= 1);
    } catch (err) {
      console.warn('Caméra non disponible:', err);
      const message = this.cameraErrorMessage(err);
      await this.cleanupCamera();
      this.showCameraError(message);
    }
  },

  cameraErrorMessage(error) {
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
      return 'La caméra nécessite une adresse HTTPS sécurisée. Ouvrez ChronoStaff avec son adresse HTTPS, puis réessayez.';
    }

    switch (error?.name) {
      case 'NotAllowedError':
      case 'PermissionDeniedError':
      case 'SecurityError':
        return 'L’accès à la caméra est bloqué. Autorisez la caméra pour ChronoStaff dans les réglages du navigateur, puis réessayez.';
      case 'NotFoundError':
      case 'DevicesNotFoundError':
        return 'Aucune caméra compatible n’a été détectée sur cet appareil.';
      case 'NotReadableError':
      case 'TrackStartError':
        return 'La caméra est déjà utilisée par une autre application. Fermez-la puis réessayez.';
      default:
        return 'Impossible de démarrer la caméra. Vérifiez son autorisation et réessayez.';
    }
  },

  showCameraError(message) {
    const errorEl = document.getElementById('camera-error');
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
    const cameraStatus = document.getElementById('camera-permission-status');
    if (cameraStatus) cameraStatus.textContent = message;
  },

  requestLocationPermission() {
    const status = document.getElementById('location-permission-status');
    if (!window.isSecureContext) {
      status.textContent = 'La localisation nécessite une adresse HTTPS sécurisée.';
      return;
    }

    if (!navigator.geolocation) {
      status.textContent = 'La localisation n’est pas prise en charge par ce navigateur.';
      return;
    }

    status.textContent = 'Demande d’autorisation de localisation…';
    navigator.geolocation.getCurrentPosition(
      () => {
        status.textContent = 'La localisation est autorisée sur cet appareil. Aucune position de test n’a été enregistrée.';
      },
      (error) => {
        if (error.code === 1) {
          status.textContent = 'La localisation est bloquée. Autorisez-la pour ChronoStaff dans les réglages du navigateur.';
        } else if (error.code === 2) {
          status.textContent = 'La position est indisponible. Vérifiez les réglages de localisation de l’appareil.';
        } else {
          status.textContent = 'La demande de localisation a expiré. Réessayez.';
        }
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  },

  async switchCamera() {
    if (this.availableCameras.length <= 1) return;
    this.currentCameraIndex = (this.currentCameraIndex + 1) % this.availableCameras.length;
    await this.startCamera();
  },

  async cleanupCamera() {
    try {
      const videos = document.querySelectorAll('#qr-reader video');
      videos.forEach(v => {
        if (v.srcObject) {
          v.srcObject.getTracks().forEach(t => t.stop());
          v.srcObject = null;
        }
      });
      const container = document.getElementById('qr-reader');
      if (container) container.innerHTML = '';
    } catch {}

    if (this.html5QrCode) {
      try {
        if (this.html5QrCode.isScanning) await this.html5QrCode.stop();
        await this.html5QrCode.clear();
      } catch {}
      this.html5QrCode = null;
    }
    this.isScanning = false;
  },

  async stopScanner() {
    document.getElementById('modal-scanner').classList.add('hidden');
    await this.cleanupCamera();
  },

  // ==========================================
  // EXÉCUTION DU POINTAGE (Arrivée vs Départ & Cooldown)
  // ==========================================

  async executePunch(punchPayload) {
    const offlineId = 'cs-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    punchPayload.offline_id = offlineId;
    punchPayload.punched_at = new Date().toISOString();

    const position = await this.getCurrentPosition();
    if (position) {
      punchPayload.latitude = position.latitude;
      punchPayload.longitude = position.longitude;
    }

    let networkSuccess = false;

    if (navigator.onLine) {
      try {
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (this.token) headers['Authorization'] = `Bearer ${this.token}`;

        const res = await fetch(ChronoApi.url('clockPunch'), {
          method: 'POST',
          headers,
          body: JSON.stringify(punchPayload)
        });

        const data = await res.json();

        // 1. Pointage accepté par le serveur
        if (res.ok && data.success) {
          networkSuccess = true;
          const isDeparture = (data.action === 'out');
          localStorage.setItem('cs_last_punch_type', data.action);

          if ('vibrate' in navigator) {
            navigator.vibrate(isDeparture ? [120, 60, 120] : [80, 40, 80]);
          }

          this.showPunchResult({
            type: isDeparture ? 'out' : 'in',
            title: isDeparture ? 'DÉPART VALIDÉ 👋' : 'ARRIVÉE VALIDÉE ✓',
            message: data.message,
            time: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
            is_offline: false
          });

          this.resetPinPad();
          return;
        }

        // 2. Erreur applicative (PIN faux, délai cooldown non respecté, etc.)
        // NE PAS stocker en offline ! C'est un refus explicite du serveur.
        networkSuccess = true;
        if ('vibrate' in navigator) navigator.vibrate([200, 100, 200]);

        if (res.status === 404) {
          // PIN ou QR incorrect
          this.showPunchResult({
            type: 'error',
            title: 'CODE PIN INCORRECT',
            message: data.message || 'Code PIN non reconnu. Veuillez vérifier vos 4 chiffres.',
            time: '',
            is_offline: false
          });
        } else if (res.status === 422 && data.cooldown) {
          // Délai anti-double pointage non écoulé
          this.showPunchResult({
            type: 'warning',
            title: 'DÉJÀ ENREGISTRÉ',
            message: data.message || 'Délai d\'attente non écoulé avant le départ.',
            time: '',
            is_offline: false
          });
        } else {
          // Autre erreur (ex: inactif)
          this.showPunchResult({
            type: 'error',
            title: 'POINTAGE REFUSÉ',
            message: data.message || 'Action non autorisée.',
            time: '',
            is_offline: false
          });
        }

        this.resetPinPad();
        return;
      } catch (err) {
        // Erreur réseau réelle (fetch failed / hors-ligne)
        console.warn('Mode réseau indisponible, bascule hors-ligne...');
      }
    }

    // 3. Mode Hors-Ligne (uniquement en cas d'absence réelle de réseau)
    try {
      const lastType = localStorage.getItem('cs_last_punch_type') || 'out';
      const isDeparture = (lastType === 'in');
      const punchType = isDeparture ? 'out' : 'in';
      punchPayload.type = punchType;
      localStorage.setItem('cs_last_punch_type', punchType);

      await saveOfflinePunch(punchPayload);
      if ('vibrate' in navigator) navigator.vibrate([100, 50, 100]);
      this.showPunchResult({
        type: 'offline',
        title: isDeparture ? 'DÉPART (HORS-LIGNE) 👋' : 'ARRIVÉE (HORS-LIGNE) ✓',
        message: 'Pointage mémorisé sur cet appareil. Synchronisation automatique dès le retour du réseau.',
        time: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
        is_offline: true
      });
    } catch {
      this.showPunchResult({
        type: 'error',
        title: 'ERREUR LOCALE',
        message: 'Impossible d\'enregistrer le pointage hors-ligne.',
        time: '',
        is_offline: true
      });
    }

    this.resetPinPad();
  },

  resetPinPad() {
    this.pinEntered = '';
    this.updateInlinePinDisplay();
    this.lockPinKeypad(false);
  },

  getCurrentPosition() {
    return new Promise((resolve) => {
      if (!navigator.geolocation) return resolve(null);
      navigator.geolocation.getCurrentPosition(
        (pos) => resolve({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }),
        () => resolve(null),
        { enableHighAccuracy: true, timeout: 4000, maximumAge: 10000 }
      );
    });
  },

  // ==========================================
  // AFFICHAGE OVERLAY RÉSULTAT
  // ==========================================

  showPunchResult({ type, title, message, time, is_offline }) {
    const overlay = document.getElementById('overlay-result');
    const box = document.getElementById('result-box');
    const icon = document.getElementById('result-icon');
    const titleEl = document.getElementById('result-title');
    const msgEl = document.getElementById('result-msg');
    const timeEl = document.getElementById('result-time');
    const badgeEl = document.getElementById('result-offline-badge');

    // Réinitialiser classes
    box.className = 'w-full max-w-sm rounded-3xl p-8 text-center text-white shadow-2xl animate-slide-up transition-all duration-300 ';

    if (type === 'in') {
      // ARRIVÉE : Vert émeraude
      box.classList.add('bg-gradient-to-b', 'from-emerald-600', 'to-emerald-700');
      icon.className = 'fa-solid fa-circle-check text-5xl mb-4 text-white';
    } else if (type === 'out') {
      // DÉPART : Rouge / Rose vif
      box.classList.add('bg-gradient-to-b', 'from-rose-600', 'to-rose-700');
      icon.className = 'fa-solid fa-right-from-bracket text-5xl mb-4 text-white';
    } else if (type === 'warning') {
      // COOLDOWN / AVERTISSEMENT : Ambre / Orange
      box.classList.add('bg-gradient-to-b', 'from-amber-500', 'to-amber-600');
      icon.className = 'fa-solid fa-hourglass-half text-5xl mb-4 text-white';
    } else if (type === 'offline') {
      // HORS LIGNE : Orange / Ambre éclatant
      box.classList.add('bg-gradient-to-b', 'from-amber-500', 'to-orange-600');
      icon.className = 'fa-solid fa-cloud-arrow-up text-5xl mb-4 text-white';
    } else {
      // ERREUR / PIN FAUX : Rouge bordeaux
      box.classList.add('bg-gradient-to-b', 'from-red-600', 'to-red-800');
      icon.className = 'fa-solid fa-circle-xmark text-5xl mb-4 text-white';
    }

    titleEl.innerText = title;
    msgEl.innerText = message;
    timeEl.innerText = time || '';
    timeEl.classList.toggle('hidden', !time);
    badgeEl.classList.toggle('hidden', !is_offline);

    overlay.classList.remove('hidden');

    if (this.overlayTimer) clearTimeout(this.overlayTimer);
    this.overlayTimer = setTimeout(() => {
      overlay.classList.add('hidden');
    }, 4200);
  },

  // ==========================================
  // SYNCHRONISATION
  // ==========================================

  async triggerAutoSync(isManual = false) {
    if (!navigator.onLine) {
      if (isManual) alert('Hors-ligne. Connexion Internet requise.');
      return;
    }
    const pending = await getOfflinePunches();
    if (pending.length === 0) {
      if (isManual) alert('Tous vos pointages sont déjà synchronisés !');
      return;
    }

    try {
      const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
      if (this.token) headers['Authorization'] = `Bearer ${this.token}`;

      const res = await fetch(ChronoApi.url('clockSync'), {
        method: 'POST',
        headers,
        body: JSON.stringify({ punches: pending })
      });
      const data = await res.json();
      if (res.ok && data.success) {
        for (const item of pending) await removeOfflinePunch(item.offline_id);
        if (isManual) alert(`Synchronisation réussie : ${data.summary?.synced_count || pending.length} pointage(s) !`);
        const histScreen = document.getElementById('screen-history');
        if (histScreen && !histScreen.classList.contains('hidden')) this.loadHistory();
      }
    } catch (err) {
      console.warn('Sync en échec:', err);
    }
  },

  // ==========================================
  // HISTORIQUE (Salarié)
  // ==========================================
});
