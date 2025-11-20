<?php
require 'config.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

if (isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($_POST['password'], $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: admin.php"); exit;
    } else { header("Location: install.php"); exit; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: install.php"); exit; }

if (!isset($_SESSION['admin_id'])) { header("Location: install.php"); exit; }

$msg = "";
if (isset($_POST['send_confirm']) && $_POST['confirm_text'] === 'SEND') {
    $publicKey = getSetting($pdo, 'public_key');
    $privateKey = getSetting($pdo, 'private_key');
    $auth = ['VAPID' => ['subject' => 'mailto:admin@app.com', 'publicKey' => $publicKey, 'privateKey' => $privateKey]];
    $webPush = new WebPush($auth);
    
    $target = $_POST['target'];
    $sql = "SELECT * FROM subscribers";
    if ($target === 'ios') $sql .= " WHERE device_type LIKE '%iOS%' OR device_type LIKE '%iPhone%'";
    elseif ($target === 'android') $sql .= " WHERE device_type LIKE '%Android%'";
    
    $subs = $pdo->query($sql)->fetchAll();
    $url = !empty($_POST['url']) ? $_POST['url'] : '/home.html'; 
    $payload = json_encode(['title' => $_POST['title'], 'body' => $_POST['body'], 'url' => $url, 'image' => $_POST['image']]);

    foreach ($subs as $sub) {
        $subscription = Subscription::create(['endpoint' => $sub['endpoint'], 'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']]]);
        $webPush->queueNotification($subscription, $payload);
    }
    $count = 0;
    foreach ($webPush->flush() as $report) { if ($report->isSuccess()) $count++; }
    
    $pdo->prepare("INSERT INTO notifications_log (title, body, url, status) VALUES (?, ?, ?, ?)")->execute([$_POST['title'], $_POST['body'], $url, "Sent: $count"]);
    $msg = "تم الإرسال بنجاح ($count)";
}

$totalSubs = $pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
$iosSubs = $pdo->query("SELECT COUNT(*) FROM subscribers WHERE device_type LIKE '%iOS%' OR device_type LIKE '%iPhone%'")->fetchColumn();
$androidSubs = $pdo->query("SELECT COUNT(*) FROM subscribers WHERE device_type LIKE '%Android%'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Push Master</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #050505; --card: #111; --primary: #00d2ff; --text: #eee; --danger: #ff4444; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { background: var(--bg); color: var(--text); font-family: 'Tajawal', sans-serif; margin: 0; padding-bottom: 100px; }
        .top-bar { padding: 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 50; border-bottom: 1px solid #222; }
        .brand { font-weight: 900; font-size: 18px; color: var(--primary); letter-spacing: 1px; }
        .section { display: none; padding: 20px; animation: fadeIn 0.3s ease; }
        .section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .stat-card { background: var(--card); border-radius: 15px; padding: 20px; border: 1px solid #222; text-align: center; }
        .stat-num { font-size: 28px; font-weight: 800; color: #fff; display: block; }
        .stat-label { font-size: 11px; color: #888; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #aaa; font-size: 12px; }
        input, textarea, select { width: 100%; background: #0a0a0a; border: 1px solid #333; padding: 12px; border-radius: 10px; color: white; outline: none; font-family: inherit; }
        input:focus, textarea:focus { border-color: var(--primary); }
        .target-selector { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 15px; }
        .target-option input { display: none; }
        .target-box { background: #0a0a0a; border: 1px solid #333; border-radius: 10px; padding: 10px; text-align: center; cursor: pointer; font-size: 11px; }
        .target-box i { font-size: 18px; display: block; margin-bottom: 5px; }
        .target-option input:checked + .target-box { background: rgba(0,210,255,0.1); border-color: var(--primary); color: var(--primary); }
        .btn-main { width: 100%; background: var(--primary); color: #000; font-weight: 800; border: none; padding: 15px; border-radius: 12px; font-size: 15px; cursor: pointer; margin-top: 10px; }
        .menu-container { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 100; }
        .menu-btn { width: 60px; height: 60px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #000; box-shadow: 0 0 20px rgba(0,210,255,0.4); cursor: pointer; position: relative; z-index: 102; transition: 0.3s; }
        .menu-btn.open { transform: rotate(45deg); background: #fff; }
        .menu-items { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
        .menu-item { position: absolute; top: 5px; left: 5px; width: 50px; height: 50px; background: #222; border-radius: 50%; border: 1px solid #444; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; transition: 0.3s; opacity: 0; cursor: pointer; pointer-events: auto; }
        .menu-container.active .menu-item:nth-child(1) { transform: translate(0, -80px); opacity: 1; }
        .menu-container.active .menu-item:nth-child(2) { transform: translate(70px, -45px); opacity: 1; }
        .menu-container.active .menu-item:nth-child(3) { transform: translate(-70px, -45px); opacity: 1; }
        .menu-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 99; opacity: 0; pointer-events: none; transition: 0.3s; }
        .menu-backdrop.active { opacity: 1; pointer-events: auto; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 200; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-box { background: #1a1a1a; border: 1px solid #333; border-radius: 15px; padding: 25px; width: 100%; max-width: 320px; text-align: center; }
        .config-card { background: #1a1a1a; border-radius: 12px; padding: 15px; margin-bottom: 15px; border: 1px solid #333; }
        .config-title { font-weight: bold; margin-bottom: 10px; color: var(--primary); font-size: 14px; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="brand"><i class="fas fa-bolt"></i> PushPro</div>
        <a href="?logout" style="color:#ff4444;"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <!-- الرئيسية -->
    <div id="dashboard" class="section active">
        <h2 style="margin-bottom:15px; font-size:18px;">نظرة عامة</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-num"><?php echo $totalSubs; ?></span>
                <span class="stat-label">المشتركين</span>
            </div>
            <div class="stat-card">
                <span class="stat-num" style="color:#aaa"><?php echo $iosSubs; ?></span>
                <span class="stat-label">iOS</span>
            </div>
        </div>
        <?php if($msg) echo "<div style='background:#00c851; color:black; padding:10px; border-radius:8px; text-align:center; margin-bottom:15px;'>$msg</div>"; ?>
    </div>

    <!-- الإرسال -->
    <div id="send" class="section">
        <h2 style="margin-bottom:15px; font-size:18px;">حملة جديدة</h2>
        <form id="campaignForm" method="post">
            <div class="target-selector">
                <label class="target-option"><input type="radio" name="target" value="all" checked><div class="target-box"><i class="fas fa-globe"></i>الكل</div></label>
                <label class="target-option"><input type="radio" name="target" value="ios"><div class="target-box"><i class="fab fa-apple"></i>iOS</div></label>
                <label class="target-option"><input type="radio" name="target" value="android"><div class="target-box"><i class="fab fa-android"></i>Android</div></label>
            </div>
            <div class="form-group"><label>العنوان</label><input type="text" name="title" required></div>
            <div class="form-group"><label>الرسالة</label><textarea name="body" rows="3" required></textarea></div>
            <div class="form-group"><label>الرابط</label><input type="url" name="url"></div>
            <div class="form-group"><label>صورة</label><input type="url" name="image"></div>
            <button type="button" class="btn-main" onclick="openConfirm()">إرسال الإشعار <i class="fas fa-paper-plane"></i></button>
            <input type="hidden" name="send_confirm" value="1"><input type="hidden" name="confirm_text" id="realConfirmText">
        </form>
    </div>

    <!-- الإعدادات والربط -->
    <div id="help" class="section">
        <h2 style="margin-bottom:15px; font-size:18px;">ضبط التطبيق</h2>
        
        <div class="config-card">
            <div class="config-title">1. إعدادات Manifest</div>
            <div class="form-group"><label>اسم التطبيق</label><input type="text" id="appName" value="أسرار تقنية"></div>
            <div class="form-group"><label>الاسم المختصر</label><input type="text" id="appShort" value="أسرار"></div>
            <div class="form-group"><label>مسار الأيقونة</label><input type="text" id="appIcon" value="/icon.png"></div>
            <button class="btn-main" style="background:#333; color:#fff; padding:10px;" onclick="downloadManifest()">تنزيل manifest.json</button>
        </div>

        <div class="config-card">
            <div class="config-title">2. إعدادات Service Worker</div>
            <div class="form-group"><label>الصفحة الرئيسية (للكاش)</label><input type="text" id="swUrl" value="/home.html"></div>
            <button class="btn-main" style="background:#333; color:#fff; padding:10px;" onclick="downloadSW()">تنزيل sw.js</button>
        </div>
    </div>

    <div class="menu-backdrop" id="backdrop" onclick="toggleMenu()"></div>
    <div class="menu-container" id="menuContainer">
        <div class="menu-items">
            <div class="menu-item" onclick="goTo('send', this)"><i class="fas fa-paper-plane"></i></div>
            <div class="menu-item active" onclick="goTo('dashboard', this)"><i class="fas fa-home"></i></div>
            <div class="menu-item" onclick="goTo('help', this)"><i class="fas fa-cogs"></i></div>
        </div>
        <div class="menu-btn" onclick="toggleMenu()"><i class="fas fa-bars" id="menuIcon"></i></div>
    </div>

    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <h3 style="color:var(--danger); margin-top:0">تأكيد الإرسال</h3>
            <p style="font-size:13px; color:#aaa">اكتب <b>SEND</b> للتأكيد:</p>
            <input type="text" id="confirmInput" style="text-align:center; letter-spacing:2px; text-transform:uppercase;">
            <div style="display:flex; gap:10px; margin-top:15px;">
                <button onclick="document.getElementById('confirmModal').style.display='none'" style="flex:1; padding:10px; background:#333; border:none; border-radius:8px; color:#fff;">إلغاء</button>
                <button onclick="submitForm()" style="flex:1; padding:10px; background:var(--danger); border:none; border-radius:8px; color:#fff;">إرسال</button>
            </div>
        </div>
    </div>

    <script>
        let isMenuOpen = false;
        function toggleMenu() {
            isMenuOpen = !isMenuOpen;
            const con = document.getElementById('menuContainer');
            const back = document.getElementById('backdrop');
            const btn = document.querySelector('.menu-btn');
            const icon = document.getElementById('menuIcon');
            
            if(isMenuOpen) {
                con.classList.add('active'); back.classList.add('active'); btn.classList.add('open'); icon.className = 'fas fa-times';
            } else {
                con.classList.remove('active'); back.classList.remove('active'); btn.classList.remove('open'); icon.className = 'fas fa-bars';
            }
        }

        function goTo(id, el) {
            document.querySelectorAll('.section').forEach(e => e.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            document.querySelectorAll('.menu-item').forEach(e => e.classList.remove('active'));
            el.classList.add('active');
            toggleMenu();
        }

        function openConfirm() { document.getElementById('confirmModal').style.display = 'flex'; }
        
        function submitForm() {
            if(document.getElementById('confirmInput').value === 'SEND') {
                document.getElementById('realConfirmText').value = 'SEND';
                document.getElementById('campaignForm').submit();
            } else { alert('كلمة التأكيد خطأ'); }
        }

        function downloadFile(name, content) {
            const el = document.createElement('a');
            el.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(content);
            el.download = name;
            document.body.appendChild(el); el.click(); document.body.removeChild(el);
        }

        function downloadManifest() {
            const name = document.getElementById('appName').value;
            const short = document.getElementById('appShort').value;
            const icon = document.getElementById('appIcon').value;
            const json = {
                "name": name, "short_name": short, "start_url": "/home.html", "display": "standalone",
                "background_color": "#000000", "theme_color": "#000000",
                "icons": [{ "src": icon, "sizes": "192x192", "type": "image/png" }, { "src": icon, "sizes": "512x512", "type": "image/png" }]
            };
            downloadFile('manifest.json', JSON.stringify(json, null, 2));
        }

        function downloadSW() {
            const url = document.getElementById('swUrl').value;
            const content = `self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};
    const targetUrl = data.url && data.url.trim() !== '' ? data.url : '${url}';
    event.waitUntil(self.registration.showNotification(data.title||'تنبيه', {
        body: data.body, icon: '/icon.png', data: { url: targetUrl }
    }));
});
self.addEventListener('notificationclick', function(event) {
    event.notification.close(); event.waitUntil(clients.openWindow(event.notification.data.url));
});`;
            downloadFile('sw.js', content);
        }
    </script>
</body>
</html>

