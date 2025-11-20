(function() {
    const CONFIG = {
        apiPath: '/push/api.php',
        swPath: '/sw.js'
    };

    // 1. التأكد من تحميل الأيقونات
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(faLink);
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    }

    // دالة عرض شاشة الترحيب والطلب (المدمجة)
    function showEntryScreen(publicKey) {
        // إذا كان المستخدم قد اختار التخطي سابقاً في هذه الجلسة، لا تظهر الشاشة
        if (sessionStorage.getItem('push_skipped')) return;

        // إخفاء التمرير
        document.body.style.overflow = 'hidden';

        const style = document.createElement('style');
        style.innerHTML = `
            #entry-screen {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: #050505; /* خلفية سوداء تغطي الموقع */
                z-index: 999999; display: flex; flex-direction: column;
                align-items: center; justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: white; transition: opacity 0.6s ease;
            }
            .entry-logo {
                font-size: 60px; color: #00d2ff; margin-bottom: 20px;
                filter: drop-shadow(0 0 20px rgba(0, 210, 255, 0.4));
                animation: float 3s ease-in-out infinite;
            }
            @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
            
            .entry-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; letter-spacing: 1px; }
            .entry-desc { font-size: 14px; color: #888; text-align: center; max-width: 300px; margin-bottom: 40px; line-height: 1.6; }
            
            .entry-btn {
                background: linear-gradient(45deg, #00d2ff, #007aff);
                color: white; border: none; padding: 15px 40px;
                border-radius: 50px; font-size: 16px; font-weight: bold;
                cursor: pointer; box-shadow: 0 10px 30px rgba(0, 210, 255, 0.2);
                transition: transform 0.2s; width: 80%; max-width: 300px;
                display: flex; align-items: center; justify-content: center; gap: 10px;
            }
            .entry-btn:active { transform: scale(0.95); }
            
            .skip-btn {
                background: transparent; border: none; color: #555;
                margin-top: 20px; font-size: 13px; cursor: pointer;
                text-decoration: underline;
            }
        `;
        document.head.appendChild(style);

        const screen = document.createElement('div');
        screen.id = 'entry-screen';
        screen.innerHTML = `
            <div class="entry-logo"><i class="fas fa-fingerprint"></i></div>
            <div class="entry-title">أسرار تقنية</div>
            <div class="entry-desc">تفعيل الإشعارات يضمن وصولك لأحدث الأدوات والمقالات التقنية فور صدورها.</div>
            
            <button class="entry-btn" id="entry-allow">
                <span>تفعيل والدخول</span> <i class="fas fa-arrow-left"></i>
            </button>
            <button class="skip-btn" id="entry-skip">الدخول بدون إشعارات</button>
        `;
        document.body.appendChild(screen);

        const btnAllow = document.getElementById('entry-allow');
        const btnSkip = document.getElementById('entry-skip');

        // دالة إغلاق الشاشة ودخول الموقع
        const enterApp = () => {
            screen.style.opacity = '0';
            document.body.style.overflow = 'auto'; // إعادة التمرير
            setTimeout(() => screen.remove(), 600);
        };

        btnSkip.onclick = () => {
            sessionStorage.setItem('push_skipped', 'true');
            enterApp();
        };

        btnAllow.onclick = async () => {
            btnAllow.style.opacity = '0.8';
            btnAllow.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> جاري التفعيل...';
            
            try {
                const register = await navigator.serviceWorker.register(CONFIG.swPath);
                const permission = await Notification.requestPermission();
                
                if (permission === 'granted') {
                    const subscription = await register.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(publicKey)
                    });

                    // إرسال صامت
                    fetch(CONFIG.apiPath, {
                        method: 'POST',
                        body: JSON.stringify(subscription),
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    btnAllow.style.background = '#00ff88';
                    btnAllow.innerHTML = 'تم التفعيل <i class="fas fa-check"></i>';
                    setTimeout(enterApp, 800);
                } else {
                    alert('يجب السماح بالإشعارات من إعدادات المتصفح.');
                    btnAllow.innerHTML = 'إعادة المحاولة';
                    btnAllow.style.opacity = '1';
                }
            } catch (err) {
                console.error(err);
                // في حال الخطأ ندخله للتطبيق عشان ما يعلق
                enterApp(); 
            }
        };
    }

    async function init() {
        if (!('serviceWorker' in navigator)) return;
        
        // إذا كان مشتركاً بالفعل -> لا تظهر الشاشة (دخول مباشر)
        if (Notification.permission === 'granted') return;

        try {
            const response = await fetch(CONFIG.apiPath + '?action=get_key');
            const data = await response.json();
            
            if (data.publicKey) {
                showEntryScreen(data.publicKey);
            }
        } catch (e) { console.error(e); }
    }

    // تشغيل فوري
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();


