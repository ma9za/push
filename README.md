🚀 PushPro: Self-Hosted Web Push & PWA Library
A lightweight, plug-and-play PHP library to transform any static or dynamic website into a Progressive Web App (PWA) with full Push Notification capabilities.
No monthly fees. No external services. 100% Data Ownership.
✨ Features
📱 Instant PWA: Converts your site into an installable app (Android & iOS).
🔔 Push Notifications: Send notifications to all users or specific devices (iOS/Android).
📊 Dashboard: Built-in Admin Panel to manage subscribers and send campaigns.
🛡️ Self-Hosted: Runs entirely on your server (SQLite database).
⚡ Smart Caching: Offline support and fast loading speeds.
🎨 Modern UI: Dark mode, glassmorphism design, and responsive widgets.
📂 Repository Structure
Your project should look like this on your server:
/public_html              <-- Your Website Root
│
├── index.html            # Your main website file
├── manifest.json         # App metadata (Name, Icons)
├── sw.js                 # Service Worker (Must be in root)
├── icon.png              # App Icon (512x512 recommended)
│
└── push/                 # The PushPro Library
    ├── admin.php         # Admin Dashboard
    ├── api.php           # API Endpoint
    ├── client.js         # Frontend Integration Script
    ├── config.php        # Database Configuration
    ├── install.php       # Installer Script
    ├── composer.json     # Dependencies
    └── vendor/           # (Generated via composer install)


🛠️ Installation Guide
Step 1: Deploy Files
Upload the push folder to your website's root directory.
Upload sw.js, manifest.json, and icon.png to the root directory (next to your index.html or index.php).
Step 2: Install Dependencies
(Skip this if you uploaded the vendor folder manually) Navigate to the push directory in your terminal and run:
cd push
composer install


Step 3: Server Permissions
Ensure the server has write permissions to the push folder to create the database:
chmod -R 755 push


Step 4: Run Installer
Open your browser and navigate to: https://yoursite.com/push/install.php
Create an Admin Username and Password.
Click Install.
The system will generate VAPID Keys and create the database.sqlite file automatically.
🔗 Integration (Frontend)
To connect your website to the PushPro system, simply add the following lines to the <head> section of your index.html (or header template):
<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- PushPro Client Script -->
<script src="/push/client.js" defer></script>

<!-- Service Worker Registration -->
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW Registered!', reg))
            .catch(err => console.error('SW Failed:', err));
    }
</script>


⚙️ Configuration
manifest.json
Edit this file to change your app's name and theme colors:
{
  "name": "My Awesome App",
  "short_name": "MyApp",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#000000",
  "theme_color": "#000000",
  "icons": [
    {
      "src": "/icon.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}


sw.js (Cache Updates)
To force an update for your users (e.g., after changing CSS or JS), update the version number at the top of sw.js:
const CACHE_VERSION = 'v1.1'; // Change to v1.2 to force update


🚀 How to Use
Visit your website: You should see the "Install App" prompt or the Notification permission modal.
Subscribe: Click "Allow" to subscribe to notifications.
Send Notifications:
Go to https://yoursite.com/push/admin.php
Login with your credentials.
Write a title, message, and optional URL/Image.
Type "SEND" to confirm and blast the notification!
⚠️ Requirements
SSL Certificate (HTTPS): Required for Service Workers and Push API.
PHP 8.1+: Required for the backend logic.
SQLite Extension: Enabled in PHP (standard on most hosts).
🤝 Contributing
Feel free to fork this repository and submit pull requests.
Developed with ❤️

