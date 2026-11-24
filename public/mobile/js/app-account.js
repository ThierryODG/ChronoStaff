// ChronoStaff PWA account screens
Object.assign(window.App, {
  downloadBadge() {
    if (!this.user) return;
    const canvas = document.createElement('canvas');
    canvas.width = 600;
    canvas.height = 850;
    const ctx = canvas.getContext('2d');

    // Background gradient
    const bgGradient = ctx.createLinearGradient(0, 0, 0, 850);
    bgGradient.addColorStop(0, '#1e3a8a');
    bgGradient.addColorStop(0.28, '#2563eb');
    bgGradient.addColorStop(0.28, '#f8fafc');
    bgGradient.addColorStop(1, '#ffffff');
    ctx.fillStyle = bgGradient;
    ctx.fillRect(0, 0, 600, 850);

    // Card border
    ctx.strokeStyle = '#cbd5e1';
    ctx.lineWidth = 4;
    ctx.strokeRect(10, 10, 580, 830);

    // Header Title
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 32px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('ChronoStaff', 300, 60);

    ctx.fillStyle = '#bfdbfe';
    ctx.font = '16px sans-serif';
    ctx.fillText('BADGE DE POINTAGE PROFESSIONNEL', 300, 95);

    // Company Name banner
    const compName = this.user.company ? this.user.company.name : 'ChronoStaff';
    ctx.fillStyle = '#1d4ed8';
    ctx.font = 'bold 20px sans-serif';
    ctx.fillText(compName.toUpperCase(), 300, 140);

    // Employee Avatar Circle
    ctx.beginPath();
    ctx.arc(300, 240, 55, 0, Math.PI * 2);
    ctx.fillStyle = '#2563eb';
    ctx.fill();
    ctx.lineWidth = 4;
    ctx.strokeStyle = '#ffffff';
    ctx.stroke();

    const initials = (this.user.name || 'AG').split(' ').map(w => w[0]).join('').substr(0, 2).toUpperCase();
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 36px sans-serif';
    ctx.fillText(initials, 300, 252);

    // Employee Name & Department
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 26px sans-serif';
    ctx.fillText(this.user.name || 'Agent', 300, 330);

    ctx.fillStyle = '#64748b';
    ctx.font = '16px sans-serif';
    const dept = this.user.department ? this.user.department.name : 'Général';
    ctx.fillText(`Département : ${dept}`, 300, 360);

    // Draw QR Code
    const qrImg = document.getElementById('account-qrcode-img');
    if (qrImg && qrImg.src) {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = () => {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(180, 390, 240, 240);
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 2;
        ctx.strokeRect(180, 390, 240, 240);
        ctx.drawImage(img, 190, 400, 220, 220);

        // PIN Code
        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 18px sans-serif';
        ctx.fillText('CODE PIN PERSONNEL', 300, 680);

        ctx.fillStyle = '#1d4ed8';
        ctx.font = 'bold 38px monospace';
        ctx.fillText(this.user.pin_code || '----', 300, 725);

        // Footer note
        ctx.fillStyle = '#94a3b8';
        ctx.font = '13px sans-serif';
        ctx.fillText('Présentez ce QR code ou saisissez votre PIN pour badger', 300, 785);

        // Trigger Download
        const link = document.createElement('a');
        link.download = `Badge-${(this.user.name || 'Employe').replace(/\s+/g, '_')}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
      };
      img.src = qrImg.src;
    }
  },

  // ==========================================
  // COMPTE (Salarié)
  // ==========================================

  loadAccountInfo() {
    if (!this.user) return;
    document.getElementById('account-name').innerText = this.user.name || 'Agent';
    document.getElementById('account-email').innerText = this.user.email || 'Code PIN uniquement';
    document.getElementById('account-avatar-initials').innerText = (this.user.name || 'AG').split(' ').map(w => w[0]).join('').substr(0, 2).toUpperCase();
    document.getElementById('account-pin').innerText = this.user.pin_code || '----';
    document.getElementById('account-role').innerText = this.user.role === 'admin' ? 'Administrateur' : (this.user.role === 'manager' ? 'Manager' : 'Employé');
    document.getElementById('account-company').innerText = this.user.company ? this.user.company.name : 'ChronoStaff';

    if (this.user.qr_token) {
      document.getElementById('account-qrcode-img').src =
        `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(this.user.qr_token)}`;
    }
  },

  // ==========================================
  // COMPTE MANAGER (Interface Exclusif sans QR ni PIN)
  // ==========================================

  loadManagerAccountInfo() {
    if (!this.user) return;
    const name = this.user.name || 'Manager';
    const initials = name.split(' ').map(w => w[0]).join('').substr(0, 2).toUpperCase();

    const avatarEl = document.getElementById('mgr-account-avatar');
    if (avatarEl) avatarEl.innerText = initials;

    const nameEl = document.getElementById('mgr-account-fullname');
    if (nameEl) nameEl.innerText = name;

    const emailEl = document.getElementById('mgr-account-email-addr');
    if (emailEl) emailEl.innerText = this.user.email || '—';

    const compEl = document.getElementById('mgr-account-company-name');
    if (compEl) compEl.innerText = this.user.company ? this.user.company.name : 'ChronoStaff';

    const deptEl = document.getElementById('mgr-account-dept-name');
    if (deptEl) deptEl.innerText = this.user.department ? this.user.department.name : 'Direction / Superviseur';

    const dateEl = document.getElementById('mgr-account-today-date');
    if (dateEl) {
      dateEl.innerText = new Date().toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      });
    }
  },

  // ==========================================
  // ONGLETS MANAGER (Dashboard vs Rapports)
  // ==========================================
});
