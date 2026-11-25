# ChronoStaff PWA frontend

This folder is the standalone static frontend for the ChronoStaff mobile PWA.
It contains no Laravel code, database, or administrator backend. The original
copy under `back/public/mobile` is intentionally left in place until the new
deployment has been tested.

## Configure the backend

Edit `js/api-config.js` and set `apiBaseUrl` to the Laravel API root, including
`/api`, for example:

```js
const apiBaseUrl = 'https://api.example.com/api';
```

The PWA API paths are centralized in this file. The PDF report URL is derived
from the same backend host. Never put passwords, API secrets, or private keys in
frontend files; anyone can inspect files downloaded by a browser.

If the PWA and backend use different origins, the Laravel API must allow the
PWA's exact origin through CORS. CORS does not bypass hosting-provider browser
checks: API calls must return Laravel JSON directly, not an HTML verification
page.

## Deploy

Upload the **contents** of this folder to the static host's document root (or
preserve the folder as a subdirectory). Keep the relative layout intact:
`index.html`, `manifest.json`, `sw.js`, `css/`, `js/`, and `icons/`.

The host must serve the site over HTTPS, serve `manifest.json` as JSON and
`sw.js` as JavaScript, and must not replace either file with an HTML challenge
page. Verify the manifest URL directly in a browser before running a PWA audit.

After confirming the static PWA loads, test `https://api.example.com/api/time`
and confirm it returns JSON. Then test login and the other protected API calls.
Do not remove the existing `back/public/mobile` copy until those tests pass.

## Installation and permissions

On Android, open the HTTPS PWA in Chrome and use the in-app install button when
the browser offers it, or Chrome's menu to install/add the app. On iPhone or
iPad, use Safari's **Share > Add to Home Screen** flow.

Browsers do not allow a PWA to force every permission prompt at first launch.
Notifications, camera, and location must be requested in context after the
user starts the related feature. Push notifications require a supported
browser, HTTPS, and a working backend push configuration.
