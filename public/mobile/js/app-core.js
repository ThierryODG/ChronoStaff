/**
 * ChronoStaff Mobile PWA — Core Application Logic v2.5
 */

// ==========================================
// CONFIG API
// ==========================================
const API_BASE = `${window.location.origin}/api`;

// ==========================================
// 1. IndexedDB — Mode Offline
// ==========================================
const DB_NAME = 'ChronoStaffOfflineDB';
const DB_VERSION = 1;
const STORE_NAME = 'pending_punches';
// Increment when the legal text changes to require acceptance again on each device.
const TERMS_VERSION = '1';

function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { keyPath: 'offline_id' });
      }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function saveOfflinePunch(punchData) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE_NAME, 'readwrite');
    tx.objectStore(STORE_NAME).put(punchData);
    tx.oncomplete = () => resolve(true);
    tx.onerror = () => reject(tx.error);
  });
}

async function getOfflinePunches() {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE_NAME, 'readonly');
    const request = tx.objectStore(STORE_NAME).getAll();
    request.onsuccess = () => resolve(request.result || []);
    request.onerror = () => reject(request.error);
  });
}

async function removeOfflinePunch(offlineId) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE_NAME, 'readwrite');
    tx.objectStore(STORE_NAME).delete(offlineId);
    tx.oncomplete = () => resolve(true);
    tx.onerror = () => reject(tx.error);
  });
}

// ==========================================
// 2. État Global
// ==========================================
window.App = {
  token: localStorage.getItem('cs_token') || null,
  user: JSON.parse(localStorage.getItem('cs_user') || 'null'),
  kiosk: JSON.parse(localStorage.getItem('cs_kiosk') || 'null'),
  loginMode: 'agent', // 'agent' ou 'kiosk'
  html5QrCode: null,
  isScanning: false,
  pinEntered: '',
  pinLocked: false,
  availableCameras: [],
  currentCameraIndex: 0,
  managerTab: 'dashboard',
  overlayTimer: null,
  backgroundPingTimer: null,
  pushSubscriptionRegistered: false,
  pushSubscriptionSyncPromise: null,
  pushSubscriptionError: null
};
