# Events & Dispatcher

The Framework menyediakan Event dispatcher intuitif (`TheFramework\App\Events\Dispatcher`) yang berfungsi seperti pola desain observer. Events (peristiwa) membantu Anda menjalankan kode yang terpisah (decoupled) dari bagian lain sistem ketika sesuatu yang spesifik terjadi di aplikasi Anda, membuat arsitektur Anda lebih mudah dikembangkan (scalable) dan sangat mirip dengan sistem Event di Laravel.

## Memicu (Dispatching) Events

Sebuah event dapat dengan cepat dipicu menggunakan helper global `event()`:

```php
event('user.registered', $user);
```

Atau Anda bisa menggunakan Dispatcher secara langsung:

```php
use TheFramework\App\Events\Dispatcher;

Dispatcher::dispatch('user.registered', $user);
```

### Menggunakan Objek sebagai Event

Seperti framework PHP modern, Anda juga dapat memicu sebuah class objek, membuat properti dengan mudah tersedia dan jelas ke listeners Anda:

```php
use App\Events\UserRegistered;

event(new UserRegistered($user));
```

## Mendengarkan (Listening) Events

Listeners (pendengar) dapat menangkap event yang dipicu ini dan melakukan suatu tindakan (seperti mengirim email selamat datang, menulis log, dll). Anda biasanya mendaftarkan event listeners secara ekstensif di sebuah Service Provider.

```php
use TheFramework\App\Events\Dispatcher;

// Mendengarkan event yang terdaftar sebagai string
Dispatcher::listen('user.registered', function($user) {
    // Kirim email selamat datang...
    Mail::send($user->email, 'Welcome to The Framework');
});

// Menggunakan class event
Dispatcher::listen(UserRegistered::class, function($event) {
    Mail::send($event->user->email, 'Welcome!');
});
```

Events sangat kuat saat Anda memerlukan eksekusi khusus untuk logika database, unggahan file, tugas autentikasi, dan pelacakan perilaku pengguna tanpa membuat controller atau Model inti menjadi membengkak.
