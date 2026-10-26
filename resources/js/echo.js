import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Tanpa kunci Pusher (mis. lingkungan dev), `new Echo()` melempar galat yang membatalkan seluruh app.js,
// sehingga Alpine dan booking.js ikut tidak berjalan. Echo hanya dibuat bila kunci tersedia.
const key = import.meta.env.VITE_PUSHER_APP_KEY;

if (key) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true
    });
}
