# PushPro: Self-Hosted PWA & Web Push Library

A privacy-focused, lightweight library to transform any website into a Progressive Web App with full push notification capabilities. Runs entirely on your own server using PHP and SQLite.

## Features

*   **Zero Dependencies:** No external services like OneSignal or Firebase required.
*   **Full Ownership:** You own your subscriber data (stored locally in SQLite).
*   **Instant PWA:** Adds "Install App" functionality to your site.
*   **Admin Dashboard:** Modern dark-mode interface to manage campaigns.
*   **Smart Caching:** Network-first strategies for offline support.

## Prerequisites

*   PHP 8.1 or higher
*   Composer installed
*   SSL Certificate (HTTPS is required for Service Workers)
*   SQLite extension enabled

## Installation

### 1. Setup Files

Upload the `push` folder to your server. Then, move the `sw.js`, `manifest.json`, and `icon.png` files to your website's root directory (next to your `index.html` or `index.php`).

Your structure must look like this:

```
/public_html
├── index.html
├── sw.js
├── manifest.json
├── icon.png
└── push/
    ├── admin.php
    ├── api.php
    ├── composer.json
    └── ...
```

### 2. Install Dependencies

Navigate to the `push` directory via terminal and run Composer to install the required encryption libraries.

```bash
cd push
composer install
```

### 3. Set Permissions

Ensure the server can write to the `push` directory to create the database.

```bash
chmod 755 push
```

### 4. Initialize System

Open your browser and visit the installation page to create your admin account and generate VAPID keys.

`https://your-domain.com/push/install.php`

## Integration

Add the following lines to the `<head>` section of your main website file (e.g., `index.html`).

```html
<link rel="manifest" href="/manifest.json">
<script src="/push/client.js" defer></script>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
</script>
```

## Configuration

### App Identity

Edit `manifest.json` in your root directory to change the app name and theme color.

```json
{
  "name": "My App",
  "short_name": "App",
  "start_url": "/index.html",
  "display": "standalone",
  "background_color": "#000000",
  "theme_color": "#000000",
  "icons": [
    {
      "src": "/icon.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icon.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

### Updating Content

To force an update for all users (cache busting), change the `CACHE_VERSION` constant at the top of `sw.js`.

```javascript
const CACHE_VERSION = 'v1.1';
```

## Usage

*   **Subscribe:** Visit your website. The subscription prompt will appear automatically.
*   **Send Notifications:**
    *   Go to `https://your-domain.com/push/`
    *   Log in with your credentials.
    *   Compose your message.
    *   Type `SEND` to confirm and dispatch.

## Troubleshooting

*   **Notification not appearing?**
    Ensure you are using HTTPS. Service workers do not function on HTTP.
*   **Database error?**
    Check directory permissions. The `push` folder must be writable by the PHP process.
*   **White screen?**
    Make sure you ran `composer install` inside the `push` folder.
