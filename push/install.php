<?php
require 'config.php';
use Minishlink\WebPush\VAPID;

// التحقق من حالة التثبيت
$isInstalled = ($pdo->query("SELECT count(*) FROM admins")->fetchColumn() > 0);
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    if ($isInstalled) {
        // تسجيل الدخول
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$user]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($pass, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: admin.php"); exit;
        } else {
            $msg = "خطأ في البيانات";
        }
    } else {
        // التثبيت لأول مرة
        if ($user && $pass) {
            $keys = VAPID::createVapidKeys();
            $pdo->prepare("INSERT OR REPLACE INTO settings (key_name, key_value) VALUES (?, ?)")->execute(['public_key', $keys['publicKey']]);
            $pdo->prepare("INSERT OR REPLACE INTO settings (key_name, key_value) VALUES (?, ?)")->execute(['private_key', $keys['privateKey']]);
            
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)")->execute([$user, $hash]);
            
            $_SESSION['admin_id'] = $pdo->lastInsertId();
            header("Location: admin.php"); exit;
        } else {
            $msg = "أكمل الحقول المطلوبة";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $isInstalled ? 'دخول النظام' : 'تثبيت النظام'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #0a0a0a;
            --card-bg: #161616;
            --primary: #00d2ff;
            --primary-hover: #00b8e0;
            --text: #ffffff;
            --text-muted: #888888;
            --border: #333333;
            --error-bg: rgba(255, 68, 68, 0.1);
            --error-text: #ff4444;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(circle at top, #1a1a2e 0%, #000000 60%);
        }

        .login-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 380px;
            padding: 40px 30px;
            border-radius: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            text-align: center;
            margin: 20px;
            position: relative;
            overflow: hidden;
        }

        /* تأثير الإضاءة العلوية */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .brand-icon {
            width: 70px;
            height: 70px;
            background: rgba(0, 210, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary);
            font-size: 28px;
            box-shadow: 0 0 30px rgba(0, 210, 255, 0.15);
        }

        h2 { margin: 0 0 10px; font-weight: 800; letter-spacing: 0.5px; font-size: 24px; }
        p { color: var(--text-muted); font-size: 14px; margin: 0 0 30px; }

        .input-wrapper {
            position: relative;
            margin-bottom: 15px;
        }

        .input-wrapper i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: 0.3s;
            z-index: 2;
        }

        input {
            width: 100%;
            padding: 16px 50px 16px 15px; /* مساحة للأيقونة */
            background: #0d0d0d;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: 0.3s;
        }

        input:focus {
            border-color: var(--primary);
            background: #111;
            box-shadow: 0 0 0 4px rgba(0, 210, 255, 0.1);
        }

        input:focus + i {
            color: var(--primary);
        }

        /* إصلاح الخلفية الصفراء للاقتراح التلقائي في كروم */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #0d0d0d inset !important;
            -webkit-text-fill-color: white !important;
            caret-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        button {
            width: 100%;
            padding: 16px;
            margin-top: 10px;
            background: var(--primary);
            color: #000;
            font-weight: 800;
            font-size: 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 210, 255, 0.2);
        }

        button:active { transform: scale(0.98); }

        .error-msg {
            background: var(--error-bg);
            color: var(--error-text);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 68, 68, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .footer-text {
            margin-top: 25px;
            font-size: 12px;
            color: #555;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- الشعار (أيقونة بدلاً من صورة) -->
        <div class="brand-icon">
            <i class="fas fa-bolt"></i>
        </div>

        <h2>PushPro System</h2>
        <p><?php echo $isInstalled ? 'أهلاً بك مجدداً، سجل دخولك للمتابعة' : 'إعداد وتثبيت النظام لأول مرة'; ?></p>

        <?php if($msg): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="input-wrapper">
                <input type="text" name="username" placeholder="اسم المستخدم" required autocomplete="username">
                <i class="fas fa-user"></i>
            </div>

            <div class="input-wrapper">
                <input type="password" name="password" placeholder="كلمة المرور" required autocomplete="current-password">
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit">
                <?php if($isInstalled): ?>
                    <span>دخول آمن</span> <i class="fas fa-arrow-left"></i>
                <?php else: ?>
                    <span>تثبيت وبدء التشغيل</span> <i class="fas fa-rocket"></i>
                <?php endif; ?>
            </button>
        </form>

        <div class="footer-text">
            <i class="fas fa-shield-alt"></i> نظام محمي ومشفر بالكامل
        </div>
    </div>

</body>
</html>


