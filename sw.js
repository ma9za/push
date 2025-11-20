self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};
    const targetUrl = data.url && data.url.trim() !== '' ? data.url : '/home.html';
    event.waitUntil(self.registration.showNotification(data.title||'تنبيه', {
        body: data.body, icon: '/icon.png', data: { url: targetUrl }
    }));
});
self.addEventListener('notificationclick', function(event) {
    event.notification.close(); event.waitUntil(clients.openWindow(event.notification.data.url));
});