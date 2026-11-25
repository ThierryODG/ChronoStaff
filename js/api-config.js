(() => {
  const apiBaseUrl = 'https://chronostaff.page.gd/api';
  const apiRoutes = Object.freeze({
    time: 'time',
    authLogin: 'auth/login',
    authKioskConnect: 'auth/kiosk-connect',
    authLogout: 'auth/logout',
    legalAccept: 'legal/accept',
    clockPunch: 'clock/punch',
    clockSync: 'clock/sync',
    notificationsVapidPublicKey: 'notifications/vapid-public-key',
    notificationsSubscriptions: 'notifications/subscriptions',
    employeeHistory: 'employee/history',
    employeeJustification: 'employee/justification',
    employeeMonthlyReport: 'employee/monthly-report',
    managerDashboard: 'admin/dashboard/who-is-here',
    managerReports: 'admin/reports',
    managerReportsExportCsv: 'admin/reports/export-csv',
  });
  const webRoutes = Object.freeze({
    managerReportsExportPdf: 'admin/reports/export-pdf',
  });
  const apiRoot = new URL(`${apiBaseUrl.replace(/\/+$/, '')}/`);
  const webRoot = new URL('../', apiRoot);

  function resolveUrl(base, routes, name, query) {
    const route = routes[name];
    if (!route) {
      throw new Error(`Route ChronoStaff inconnue : ${name}`);
    }

    const url = new URL(route, base);
    for (const [key, value] of Object.entries(query || {})) {
      if (value !== undefined && value !== null) {
        url.searchParams.set(key, String(value));
      }
    }
    return url.toString();
  }

  window.ChronoApi = Object.freeze({
    apiBaseUrl,
    url: (name, query) => resolveUrl(apiRoot, apiRoutes, name, query),
    webUrl: (name, query) => resolveUrl(webRoot, webRoutes, name, query),
  });
})();
