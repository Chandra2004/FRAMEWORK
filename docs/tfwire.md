# ⚡ TFWire — The Turbo-Powered Component System (v2.0.0)

TFWire adalah **Livewire alternative** yang dibangun di atas Hotwire Turbo. Sistem komponen interaktif yang memungkinkan developer membangun UI dinamis menggunakan **PHP saja** — tanpa perlu menulis JavaScript.

---

## 📋 Daftar Isi

1. [Instalasi & Setup](#instalasi--setup)
2. [Arsitektur](#arsitektur)
3. [Membuat Komponen](#membuat-komponen)
4. [Lifecycle Hooks](#lifecycle-hooks)
5. [Data Binding (tf-wire:model)](#data-binding)
6. [Actions (tf-wire:click)](#actions)
7. [Validasi](#validasi)
8. [Form Object](#form-object)
9. [Events System](#events-system)
10. [Computed Properties](#computed-properties)
11. [Pagination (WithPagination)](#pagination)
12. [Sorting (WithSorting)](#sorting)
13. [Search (WithSearch)](#search)
14. [File Upload (WithFileUploads)](#file-upload)
15. [State Persistence (WithState)](#state-persistence)
16. [Loading States](#loading-states)
17. [Polling & Auto-Refresh](#polling--auto-refresh)
18. [Lazy Loading](#lazy-loading)
19. [Flash Messages & Notifikasi](#flash-messages--notifikasi)
20. [Modal & Dialog](#modal--dialog)
21. [Redirect](#redirect)
22. [Authorization](#authorization)
23. [Keyboard Shortcuts](#keyboard-shortcuts)
24. [Offline Support](#offline-support)
25. [Optimistic UI](#optimistic-ui)
26. [Prefetch on Hover](#prefetch-on-hover)
27. [TurboStream API](#turbostream-api)
28. [Integrasi Alpine.js](#integrasi-alpinejs)
29. [Portabilitas (Laravel)](#portabilitas)
30. [TFWire Facade (v2.0)](#tfwire-facade)
31. [Plugin System](#plugin-system)
32. [Security & Encryption](#security--encryption)
33. [Testing Komponen](#testing-komponen)
34. [Method Reference](#method-reference)

---

## Instalasi & Setup

### 1. Layout Setup

Tambahkan ke layout master Blade anda (`resources/views/layouts/app.blade.php`):

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', config('app.name'))</title>

    {{-- TFWire CSS --}}
    <link rel="stylesheet" href="{{ asset('css/tf-wire.css') }}">
</head>
<body>
    {{-- Konten --}}
    @yield('content')

    {{-- TFWire Targets (wajib, taruh di dalam body) --}}
    <div id="tf-notifications"></div>
    <div id="tf-modal-container"></div>
    <div id="tf-scripts" style="display:none;"></div>

    {{-- Hotwire Turbo --}}
    <script type="module" src="https://unpkg.com/@hotwired/turbo@8.0.12/dist/turbo.es2017-esm.js"></script>

    {{-- Alpine.js (opsional, untuk interaktivitas ekstra) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- TFWire Engine --}}
    <script src="{{ asset('js/tf-wire.js') }}" defer></script>
</body>
</html>
```

### 2. Register Routes

Di `bootstrap/app.php` atau file bootstrap framework anda:

```php
use TheFramework\App\TFWire\TFWireServiceProvider;

TFWireServiceProvider::register();
```

### 3. File Structure

```
app/App/TFWire/
├── Component.php              ← Base class untuk semua komponen
├── Form.php                   ← Form Object (validasi + model binding)
├── TFWireEngine.php           ← Request processor (jangan diubah)
├── TFWireServiceProvider.php  ← Bootstrapper
├── TurboStream.php            ← Fluent DOM manipulation API
└── Traits/
    ├── WithFileUploads.php    ← Upload file + progress
    ├── WithPagination.php     ← 3 mode pagination
    ├── WithSearch.php         ← Live search + highlight
    ├── WithSorting.php        ← Column sorting
    └── WithState.php          ← Persist state di browser

public/assets/
├── css/tf-wire.css            ← Built-in UI styles
└── js/tf-wire.js              ← Client-side engine
```

---

## Arsitektur

```
Browser                          Server
┌──────────────────┐             ┌──────────────────┐
│  tf-wire.js      │  ──POST──▶  │  TFWireEngine    │
│  (Delegated      │             │  handleRequest() │
│   Events)        │             │                  │
│                  │             │  ┌────────────┐  │
│  tf-wire:click ──┤             │  │ Component  │  │
│  tf-wire:model ──┤             │  │  hydrate() │  │
│  tf-wire:submit──┤             │  │  action()  │  │
│                  │             │  │  render()   │  │
│                  │  ◀─STREAM── │  └────────────┘  │
│  ┌────────────┐  │             │                  │
│  │ Turbo      │  │             │  TurboStream     │
│  │  replace() │  │             │  →replace()      │
│  │  append()  │  │             │  →success()      │
│  └────────────┘  │             │  →redirect()     │
└──────────────────┘             └──────────────────┘

---

## TFWire Facade (v2.0)

Mulai versi 2.0, TFWire memperkenalkan **Facade** sebagai entry point utama untuk interaksi dengan engine:

```php
use TheFramework\App\TFWire\TFWire;

// Cek Versi
echo TFWire::version(); // '2.0.0'

// Register Plugin
TFWire::plugin(\App\Plugins\MyCustomPlugin::class);

// Inisialisasi Testing
$test = TFWire::test(\App\Components\Counter::class);
```

---

## Plugin System

TFWire kini mendukung plugin untuk memperluas fungsionalitas komponen secara global.

### 1. Membuat Plugin

Buat class yang meng-extend `TFWirePlugin`:

```php
namespace App\Plugins;

use TheFramework\App\TFWire\Plugin\TFWirePlugin;

class MyPlugin extends TFWirePlugin {
    public function boot(): void {
        // Logika saat plugin dimuat
    }
}
```

### 2. Built-in Plugin: Rate Limiter

TFWire menyertakan `RateLimiter` plugin bawaan untuk mencegah spamming pada action komponen:

```php
TFWire::plugin(\TheFramework\App\TFWire\Plugin\RateLimiter::class);
```

---

## Security & Encryption

TFWire 2.0 menyertakan lapisan keamanan **State Encryption** untuk mencegah tempering (perubahan paksa) state komponen di sisi client.

- **StateEncryptor**: Mengenkripsi payload state menggunakan `APP_KEY`.
- **Integrity Check**: Memastikan data yang dikirim balik dari browser tidak dimodifikasi.
- **SecurityException**: Dilemparkan otomatis jika payload tidak valid atau corrupt.

Semua proses ini berjalan di balik layar (transparent) pada `TFWireEngine`.

---

## Testing Komponen

Anda dapat mengetes logika komponen secara terisolasi tanpa browser menggunakan Fluent Testing API:

```php
public function test_counter_works()
{
    TFWire::test(Counter::class)
        ->set('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee('1');
}
```

> Selengkapnya lihat di [Dokumentasi Testing](testing.md).
```

**Alur Kerja:**

1. User klik tombol → `tf-wire.js` menangkap event
2. JS mengirim POST ke `/tfwire/handle` dengan state + action
3. `TFWireEngine` meng-hydrate component dari state yang dikirim
4. Menjalankan action yang diminta (method PHP)
5. Merender ulang component → kirim sebagai Turbo Stream
6. Turbo mengganti DOM yang lama dengan yang baru (dengan **morphing**)

---

## Membuat Komponen

### Via Artisan CLI (Rekomendasi)

Gunakan perintah Artisan untuk membuat komponen beserta view-nya secara instan:

```bash
php artisan make:component Counter
```

Untuk komponen di sub-direktori:

```bash
php artisan make:component Admin/StatsCard
```

Perintah ini akan menghasilkan:
- `app/Components/Counter.php`
- `resources/views/component/counter.blade.php`

### Struktur Dasar (Manual)

Buat file PHP di folder `app/Components/` (atau lokasi lain yang autoloaded):

```php
<?php

namespace TheFramework\Components;

use TheFramework\App\TFWire\Component;

class Counter extends Component
{
    public int $count = 0;

    /**
     * Lifecycle: Dipanggil sekali saat pertama kali komponen dibuat
     */
    public function mount(): void
    {
        $this->count = 0;
    }

    /**
     * Return nama view Blade
     */
    protected function view(): string
    {
        return 'components.counter';
    }

    // -- Actions (dipanggil dari frontend) --

    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        $this->count = max(0, $this->count - 1);
    }
}
```

### View Blade

```blade
{{-- resources/views/components/counter.blade.php --}}
<div class="p-6 bg-gray-900 rounded-lg text-center">
    <h2 class="text-3xl font-bold text-white mb-4">{{ $count }}</h2>

    <div class="flex gap-3 justify-center">
        <button tf-wire:click="decrement"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            − Kurangi
        </button>

        <button tf-wire:click="increment"
                class="px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600">
            + Tambah
        </button>
    </div>
</div>
```

### Menampilkan di Halaman

```blade
{{-- resources/views/interface/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
    <h1>Dashboard</h1>

    {{-- Render komponen TFWire --}}
    {!! tfwire(\TheFramework\Components\Counter::class) !!}
@endsection
```

Atau dengan ID kustom dan parameter:

```php
{!! tfwire(Counter::class, 'counter-utama') !!}

// Dengan parameter mount
{!! tfwire(Counter::class, null, ['initialCount' => 10]) !!}
```

---

## Lifecycle Hooks

| Hook | Kapan Dipanggil | Use Case |
|:-----|:----------------|:---------|
| `mount()` | Sekali, saat pertama kali dibuat | Inisialisasi data, query database |
| `hydrate()` | Setiap request, setelah state di-restore | Re-connect ke service/resource |
| `dehydrate()` | Setiap request, sebelum state disimpan | Cleanup, format data |
| `updated($prop, $val)` | Setelah property berubah dari frontend | Auto-save, side effects |
| `rendering()` | Sebelum view di-render | Prepare computed data |
| `rendered(&$html)` | Setelah view di-render | Modify HTML output |
| `beforeAction($action)` | Sebelum action dijalankan | Guard, permission check |
| `afterAction($action)` | Setelah action selesai | Logging, audit trail |
| `authorizeAccess()` | Sebelum render jika `$authorize = true` | Access control |

```php
class UserTable extends Component
{
    public function mount(): void
    {
        $this->users = User::all();
    }

    public function hydrate(): void
    {
        // Re-connect ke cache layer setiap request
    }

    public function updated(string $property, $value): void
    {
        if ($property === 'search') {
            // Reset ke halaman 1 saat search berubah
            $this->page = 1;
        }
    }

    public function beforeAction(string $action): bool
    {
        // Hanya admin yang boleh delete
        if ($action === 'delete' && !auth_user()?->isAdmin()) {
            return false;
        }
        return true;
    }
}
```

---

## Data Binding

### tf-wire:model — Two-Way Binding (Realtime)

```blade
<input type="text"
       tf-wire:model="name"
       placeholder="Ketik nama...">

<p>Halo, {{ $name }}!</p>
```

Setiap kali user mengetik, property `$name` di server otomatis ter-update (debounce 300ms default).

### tf-wire:model.lazy — Binding on Blur

```blade
{{-- Hanya kirim ke server saat input kehilangan focus --}}
<input type="email"
       tf-wire:model.lazy="email"
       placeholder="Email">
```

### tf-wire:model.debounce.500ms — Custom Debounce

```blade
{{-- Tunggu 500ms sejak user berhenti mengetik --}}
<input type="text"
       tf-wire:model.debounce.500ms="search"
       placeholder="Search...">
```

### Checkbox & Radio

```blade
<input type="checkbox" tf-wire:model="active">

<select tf-wire:model="role">
    <option value="user">User</option>
    <option value="admin">Admin</option>
</select>
```

---

## Actions

### tf-wire:click — Panggil Method PHP

```blade
{{-- Panggil method tanpa parameter --}}
<button tf-wire:click="save">Simpan</button>

{{-- Dengan parameter --}}
<button tf-wire:click="delete({{ $user->id }})">Hapus</button>

{{-- Dengan multiple parameter --}}
<button tf-wire:click="setStatus({{ $user->id }}, 'active')">Aktifkan</button>
```

### tf-wire:submit — Form Submit

```blade
<form tf-wire:submit="save">
    <input name="name" placeholder="Nama">
    <input name="email" placeholder="Email">
    <button type="submit">Simpan</button>
</form>
```

```php
class CreateUser extends Component
{
    public string $name = '';
    public string $email = '';

    public function save(): void
    {
        $this->validate();
        User::create(['name' => $this->name, 'email' => $this->email]);
        $this->flashSuccess('User berhasil dibuat!');
        $this->reset('name', 'email');
    }
}
```

### tf-wire:confirm — Konfirmasi Sebelum Action

```blade
<button tf-wire:click="delete({{ $id }})"
        tf-wire:confirm="Yakin ingin menghapus data ini?">
    🗑️ Hapus
</button>
```

---

## Validasi

### Mendefinisikan Rules

```php
class ContactForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $message = '';

    protected array $rules = [
        'name'    => 'required|min:3|max:50',
        'email'   => 'required|email',
        'message' => 'required|min:10',
    ];

    protected array $messages = [
        'name.required'    => 'Nama wajib diisi.',
        'name.min'         => 'Nama minimal 3 huruf.',
        'email.required'   => 'Email wajib diisi.',
        'email.email'      => 'Format email tidak valid.',
        'message.required' => 'Pesan wajib diisi.',
    ];

    public function send(): void
    {
        $data = $this->validate(); // Throw ValidationException jika gagal
        // Kirim email, simpan ke DB, dll.
        $this->flashSuccess('Pesan terkirim!');
    }
}
```

### Rules yang Didukung

| Rule | Contoh | Keterangan |
|:-----|:-------|:-----------|
| `required` | `'name' => 'required'` | Wajib diisi |
| `string` | `'name' => 'string'` | Harus berupa text |
| `email` | `'email' => 'email'` | Format email valid |
| `min:n` | `'name' => 'min:3'` | Minimal n karakter |
| `max:n` | `'name' => 'max:255'` | Maksimal n karakter |
| `between:min,max` | `'age' => 'between:17,65'` | Antara min dan max |
| `in:a,b,c` | `'role' => 'in:user,admin'` | Harus salah satu |
| `not_in:a,b` | `'status' => 'not_in:banned'` | Tidak boleh salah satu |
| `confirmed` | `'password' => 'confirmed'` | Harus cocok dengan `password_confirmation` |
| `regex:pattern` | `'code' => 'regex:/^[A-Z]{3}$/'` | Cocok dengan pola regex |
| `url` | `'website' => 'url'` | URL valid |
| `date` | `'birthday' => 'date'` | Tanggal valid |
| `boolean` | `'active' => 'boolean'` | True/False |
| `array` | `'tags' => 'array'` | Harus array |
| `nullable` | `'bio' => 'nullable\|min:10'` | Boleh kosong |

### Menampilkan Error di View

```blade
<input type="text" tf-wire:model="name"
       class="{{ isset($_errors['name']) ? 'border-red-500' : '' }}">

@if(isset($_errors['name']))
    <p class="text-red-500 text-sm mt-1">{{ $_errors['name'][0] }}</p>
@endif
```

### Real-time Validation (Per Field)

```php
public function updatedEmail(): void
{
    $this->validateOnly('email');
}
```

---

## Form Object

Untuk form yang kompleks, gunakan `Form` class agar komponen tetap bersih:

### Mendefinisikan Form

```php
<?php

namespace TheFramework\Components\Forms;

use TheFramework\App\TFWire\Form;

class UserForm extends Form
{
    public string $name = '';
    public string $email = '';
    public string $role = 'user';
    public string $bio = '';

    protected array $rules = [
        'name'  => 'required|min:3|max:50',
        'email' => 'required|email',
        'role'  => 'required|in:user,admin,editor',
        'bio'   => 'nullable|max:500',
    ];
}
```

### Menggunakan di Komponen

```php
class UserManager extends Component
{
    public UserForm $form;
    public ?int $editingId = null;

    public function mount(): void
    {
        $this->form = new UserForm();
    }

    public function edit(int $id): void
    {
        $user = User::find($id);
        $this->form->fillFromModel($user); // Isi form dari model
        $this->editingId = $id;
    }

    public function save(): void
    {
        $data = $this->form->validate();

        if ($this->editingId) {
            User::find($this->editingId)->update($data);
            $this->flashSuccess('User di-update!');
        } else {
            User::create($data);
            $this->flashSuccess('User dibuat!');
        }

        $this->form->reset();
        $this->editingId = null;
    }

    protected function view(): string { return 'components.user-manager'; }
}
```

### Form Object Methods

| Method | Return | Keterangan |
|:-------|:-------|:-----------|
| `fill(array $data)` | `self` | Isi form dari array |
| `fillFromModel($model)` | `self` | Isi form dari Model object |
| `validate()` | `array` | Validasi + return data bersih |
| `reset()` | `void` | Reset ke nilai awal |
| `isDirty()` | `bool` | Apakah form sudah diubah? |
| `getDirty()` | `array` | Field yang berubah saja |
| `toArray()` | `array` | Semua field sebagai array |
| `hasError($field)` | `bool` | Cek ada error di field? |
| `getError($field)` | `?string` | Ambil pesan error pertama |

---

## Events System

### Emit Event (Global)

```php
// Komponen A: Kirim event
$this->emit('userCreated', ['id' => $user->id]);

// Komponen B: Dengarkan event
protected array $listeners = ['userCreated' => 'refreshList'];

public function refreshList(array $data): void
{
    // Data user baru diterima
    $this->users = User::all();
}
```

### Emit ke Diri Sendiri

```php
$this->emitSelf('dataUpdated');
```

### Emit ke Parent

```php
$this->emitUp('itemSelected', ['id' => $itemId]);
```

### Dispatch Browser Event (Alpine.js)

```php
$this->dispatchBrowserEvent('notification', [
    'title' => 'Berhasil!',
    'message' => 'Data tersimpan.',
]);
```

Di view, tangkap dengan Alpine.js:

```blade
<div x-data @notification.window="alert($event.detail.message)">
    ...
</div>
```

---

## Computed Properties

Properti yang dihitung otomatis dan **di-cache** selama satu render cycle:

```php
class UserProfile extends Component
{
    public string $firstName = 'Chandra';
    public string $lastName = 'Tri A';

    /**
     * Computed: $this->fullName
     * Nama method: get{PropertyName}Property()
     */
    public function getFullNameProperty(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getInitialsProperty(): string
    {
        return strtoupper($this->firstName[0] . $this->lastName[0]);
    }
}
```

```blade
<h1>{{ $_component->fullName }}</h1>
<div class="avatar">{{ $_component->initials }}</div>
```

---

## Pagination

### Menggunakan Trait

```php
use TheFramework\App\TFWire\Traits\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    protected int $perPage = 10;
    protected string $paginationMode = 'pages'; // 'pages' | 'infinite' | 'load-more'

    protected function view(): string { return 'components.user-table'; }
}
```

### 3 Mode Pagination

| Mode | Keterangan |
|:-----|:-----------|
| `pages` | Navigasi halaman klasik dengan tombol angka |
| `infinite` | Auto-load saat scroll ke bawah (Alpine.js x-intersect) |
| `load-more` | Tombol "Load More" manual |

### Render di View

```blade
<table>
    @foreach($users as $user)
        <tr><td>{{ $user->name }}</td></tr>
    @endforeach
</table>

{{-- Render pagination otomatis --}}
{!! $_component->renderPagination($totalUsers) !!}
```

### Pagination Info di Controller

```php
$info = $this->getPaginationInfo($total);
// Returns: current_page, per_page, total, last_page, from, to,
//          has_previous, has_next, pages (with ellipsis)
```

---

## Sorting

```php
use TheFramework\App\TFWire\Traits\WithSorting;

class ProductTable extends Component
{
    use WithSorting;

    protected array $sortable = ['name', 'price', 'created_at'];

    public function getProducts()
    {
        $query = Product::query();
        $query = $this->applySorting($query); // Auto-apply sorting
        return $query->get();
    }
}
```

```blade
<table>
    <thead>
        <tr>
            <th tf-wire:click="sortBy('name')" style="cursor:pointer">
                Nama {!! $_component->sortIcon('name') !!}
            </th>
            <th tf-wire:click="sortBy('price')" style="cursor:pointer">
                Harga {!! $_component->sortIcon('price') !!}
            </th>
        </tr>
    </thead>
</table>
```

---

## Search

```php
use TheFramework\App\TFWire\Traits\WithSearch;

class UserList extends Component
{
    use WithSearch;

    protected array $searchable = ['name', 'email'];
    protected int $searchMinLength = 2;

    public function getUsers()
    {
        $query = User::query();
        $query = $this->applySearch($query); // Auto-apply search
        return $query->get();
    }
}
```

```blade
{{-- Input search dengan debounce --}}
<input type="text"
       tf-wire:model.debounce.300ms="search"
       placeholder="🔍 Cari user...">

@if($_component->isSearching())
    <button tf-wire:click="clearSearch">✕ Reset</button>
@endif

@foreach($users as $user)
    <li>{!! $_component->highlight($user->name) !!}</li>
@endforeach
```

Output highlight: Nama **Chandra** akan ditampilkan dengan `<mark>` di sekitar kata yang dicari.

---

## File Upload

```php
use TheFramework\App\TFWire\Traits\WithFileUploads;

class AvatarUpload extends Component
{
    use WithFileUploads;

    public $photo;

    protected array $uploadRules = [
        'photo' => ['max:2048', 'mimes:jpg,png,webp'],
    ];

    public function savePhoto(): void
    {
        $path = $this->storeUpload('photo', 'uploads/avatars');
        // $path = 'uploads/avatars/abc123.jpg'

        auth_user()->update(['avatar' => $path]);
        $this->flashSuccess('Avatar berhasil diupload!');
    }
}
```

```blade
{{-- Input file --}}
<input type="file" tf-wire:model="photo" accept="image/*">

{{-- Progress bar (otomatis tampil saat upload) --}}
<div tf-wire:upload.progress="photo" style="display:none">
    Mengupload... <span tf-wire:upload.percent></span>%
</div>

{{-- Preview (otomatis tampil setelah pilih file) --}}
<div tf-wire:upload.preview="photo">
    <img src="" alt="Preview" class="w-32 h-32 rounded-full object-cover">
</div>

<button tf-wire:click="savePhoto">💾 Simpan Foto</button>
```

---

## State Persistence

Simpan state komponen di browser agar tetap ada setelah refresh halaman:

```php
use TheFramework\App\TFWire\Traits\WithState;

class DashboardFilter extends Component
{
    use WithState;

    public string $dateRange = '7d';
    public string $category = 'all';

    /** Properties yang dipersist di browser */
    protected array $persist = ['dateRange', 'category'];
    protected string $persistDriver = 'local'; // 'local' | 'session'
}
```

Data `dateRange` dan `category` akan disimpan di `localStorage` dan di-restore otomatis saat halaman dibuka ulang.

---

## Loading States

### Tampilkan Saat Loading

```blade
{{-- Spinner tampil saat ada request --}}
<div tf-wire:loading>
    <svg class="animate-spin h-5 w-5">...</svg> Memproses...
</div>
```

### Sembunyikan Saat Loading

```blade
{{-- Tombol hilang saat loading --}}
<button tf-wire:click="save" tf-wire:loading.remove>
    💾 Simpan
</button>
```

### Tambah Class Saat Loading

```blade
{{-- Tabel jadi buram saat loading --}}
<table tf-wire:loading.class="opacity-30">
    ...
</table>
```

### Target Spesifik

```blade
{{-- Loading hanya muncul untuk action "delete" --}}
<span tf-wire:loading tf-wire:target="delete">Menghapus...</span>

<button tf-wire:click="delete(1)">Hapus</button>
```

---

## Polling & Auto-Refresh

Refresh komponen secara otomatis setiap interval:

```php
class LiveNotifications extends Component
{
    /** Refresh setiap 5 detik */
    protected int $pollInterval = 5000;

    public int $count = 0;

    public function mount(): void
    {
        $this->count = Notification::unread()->count();
    }
}
```

> ⚡ **Smart Polling:** TFWire otomatis berhenti polling saat tab browser tidak aktif untuk menghemat resource (visibility-aware).

---

## Lazy Loading

Render placeholder dulu, muat konten di background:

```php
class HeavyChart extends Component
{
    /** Aktifkan lazy loading */
    protected bool $lazy = true;

    protected function placeholder(): string
    {
        return '<div class="animate-pulse bg-gray-800 h-64 rounded-lg"></div>';
    }

    public function mount(): void
    {
        // Query berat ini hanya dijalankan saat lazy load
        $this->data = Analytics::getHeavyReport();
    }
}
```

---

## Flash Messages & Notifikasi

Flash messages menggunakan **notification.blade.php internal** framework anda (Tailwind + Flowbite):

```php
// Di dalam method komponen
$this->flashSuccess('Data berhasil disimpan!');
$this->flashError('Terjadi kesalahan!');
$this->flashWarning('Perhatian: stok menipis.');
$this->flashInfo('Update tersedia.');
```

Notifikasi akan tampil otomatis di pojok kanan atas, menggunakan desain yang **sama persis** dengan `redirect('/url', 'success', 'message')`.

### Via TurboStream (dari Controller)

```php
// Di Controller
return turbo_stream()
    ->success('User berhasil dihapus!')
    ->remove('user-row-' . $id)
    ->send();
```

---

## Modal & Dialog

### Buka Modal dari Server

```php
// Di dalam method komponen
return turbo_stream()
    ->openModalView('edit-user-modal', 'components.modals.edit-user', [
        'user' => $user
    ]);
```

### Tutup Modal

```php
return turbo_stream()->closeModal('edit-user-modal');
```

### Buka Modal dengan HTML Langsung

```php
return turbo_stream()->openModal('confirm-modal', '
    <h3 class="text-lg font-bold">Yakin?</h3>
    <p>Aksi ini tidak bisa dibatalkan.</p>
    <button tf-wire:click="confirmDelete(' . $id . ')">Hapus</button>
');
```

---

## Redirect

```php
// Redirect menggunakan Turbo-Location (tanpa full reload)
$this->redirect('/dashboard');
```

TFWire memanfaatkan header `Turbo-Location` sehingga redirect dilakukan oleh **Turbo Drive** — seamless dan cepat.

---

## Authorization

```php
class AdminPanel extends Component
{
    /** Aktifkan pengecekan authorization */
    protected bool $authorize = true;

    /**
     * Return false = component tidak di-render
     */
    public function authorizeAccess(): bool
    {
        return auth_user()?->isAdmin() ?? false;
    }
}
```

### Locked Properties

Properti yang tidak bisa diubah dari frontend:

```php
class UserProfile extends Component
{
    public string $name = '';
    public string $email = '';
    public string $role = 'user';

    /** role tidak bisa diubah dari tf-wire:model di frontend */
    protected array $locked = ['role'];
}
```

---

## Keyboard Shortcuts

```blade
{{-- Tekan Enter untuk search --}}
<input type="text"
       tf-wire:model="search"
       tf-wire:keydown.enter="performSearch"
       placeholder="Search...">

{{-- Tekan Escape untuk cancel --}}
<div tf-wire:keydown.escape="cancelEdit">
    ...
</div>
```

Key yang didukung: `enter`, `escape`, `arrow-up`, `arrow-down`, `tab`, dan semua key lainnya.

---

## Offline Support

### Indicator

```blade
{{-- Otomatis tampil saat offline --}}
<div tf-wire:offline>
    ⚠️ Anda sedang offline. Perubahan akan disimpan saat online kembali.
</div>
```

### Offline Queue

Saat user offline, semua action (klik, submit, model change) secara otomatis **masuk antrian**. Ketika koneksi pulih, TFWire otomatis **menjalankan ulang** semua action yang tertunda — tanpa perlu koding tambahan.

---

## Optimistic UI

Update tampilan sebelum server merespon untuk pengalaman yang lebih cepat:

```blade
{{-- Langsung hilangkan row saat diklik (sebelum server confirm) --}}
<tr id="row-{{ $user->id }}"
    tf-wire:optimistic.remove>
    <td>{{ $user->name }}</td>
    <td>
        <button tf-wire:click="delete({{ $user->id }})">Hapus</button>
    </td>
</tr>

{{-- Atau: Tambah class "dimmed" sebelum server merespons --}}
<div tf-wire:optimistic.class="opacity-30">
    ...
</div>
```

> Jika server mengembalikan error, elemen otomatis di-revert.

---

## Prefetch on Hover

Preload response saat mouse hover — klik terasa **instan**:

```blade
<button tf-wire:click="showDetail({{ $id }})"
        tf-wire:prefetch>
    Lihat Detail
</button>
```

Saat user hover, TFWire mengirim request di background. Saat user benar-benar klik, response langsung dipakai dari cache — **zero latency**.

---

## TurboStream API

### Di Controller

```php
// Hapus elemen + tampilkan notifikasi
return turbo_stream()
    ->remove('user-row-' . $id)
    ->success('User berhasil dihapus!')
    ->send();

// Update bagian tertentu dari halaman
return turbo_stream()
    ->updateView('user-count', 'partials.user-count', ['total' => $newTotal])
    ->appendView('user-list', 'partials.user-row', ['user' => $newUser])
    ->send();
```

### Aksi Tersedia

| Method | Keterangan |
|:-------|:-----------|
| `append($target, $html)` | Sisipkan di akhir target |
| `prepend($target, $html)` | Sisipkan di awal target |
| `replace($target, $html)` | Ganti seluruh elemen target |
| `update($target, $html)` | Ganti isi dalam elemen target |
| `remove($target)` | Hapus elemen target |
| `before($target, $html)` | Sisipkan sebelum target |
| `after($target, $html)` | Sisipkan setelah target |
| `appendView($target, $view, $data)` | Append menggunakan Blade view |
| `replaceView($target, $view, $data)` | Replace menggunakan Blade view |
| `success($msg)` / `error($msg)` | Tampilkan notifikasi |
| `openModal($id, $html)` | Buka modal dialog |
| `closeModal($id)` | Tutup modal |
| `redirectTo($url)` | Redirect via Turbo |
| `scrollTo($target)` | Scroll ke elemen |
| `dispatch($event, $data)` | Kirim browser event |

### Conditional Streams

```php
return turbo_stream()
    ->remove('item-' . $id)
    ->when($itemCount === 0, fn($s) => $s->update('item-list', '<p>Data kosong.</p>'))
    ->success('Item dihapus!')
    ->send();
```

---

## Integrasi Alpine.js

TFWire bekerja mulus dengan Alpine.js melalui `dispatchBrowserEvent`:

```php
// PHP — kirim event ke browser
$this->dispatchBrowserEvent('item-saved', ['id' => $item->id]);
```

```blade
{{-- Blade — tangkap event dengan Alpine --}}
<div x-data="{ show: false }"
     @item-saved.window="show = true; setTimeout(() => show = false, 3000)">

    <div x-show="show" x-transition class="text-green-500">
        ✅ Item tersimpan!
    </div>
</div>
```

---

## Portabilitas

### The Framework (Native)

```php
// bootstrap/app.php
TFWireServiceProvider::register();
```

### Laravel

1. Copy folder `app/App/TFWire/` ke project Laravel
2. Copy `public/assets/css/tf-wire.css` dan `public/assets/js/tf-wire.js`
3. Buat ServiceProvider:

```php
// app/Providers/TFWireServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TFWireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        \TheFramework\App\TFWire\TFWireServiceProvider::bootLaravel($this->app);
    }
}
```

4. Daftarkan di `config/app.php`:

```php
'providers' => [
    App\Providers\TFWireServiceProvider::class,
],
```

---

## Method Reference

### Component Methods

| Method | Return | Keterangan |
|:-------|:-------|:-----------|
| `mount()` | `void` | Lifecycle: inisialisasi pertama |
| `view()` | `string` | Return nama Blade view |
| `render()` | `string` | Render komponen ke HTML |
| `validate()` | `array` | Validasi semua rules |
| `validateOnly($field)` | `void` | Validasi satu field |
| `fill($data)` | `void` | Isi properties dari array |
| `reset(...$props)` | `void` | Reset properties ke awal |
| `isDirty()` | `bool` | Ada property yang berubah? |
| `getDirty()` | `array` | Properties yang berubah |
| `emit($event, $data)` | `void` | Kirim event global |
| `emitSelf($event, $data)` | `void` | Kirim event ke diri sendiri |
| `emitUp($event, $data)` | `void` | Kirim event ke parent |
| `dispatchBrowserEvent($e, $d)` | `void` | Kirim event ke browser |
| `flashSuccess($msg)` | `void` | Flash pesan sukses |
| `flashError($msg)` | `void` | Flash pesan error |
| `flashWarning($msg)` | `void` | Flash pesan warning |
| `flashInfo($msg)` | `void` | Flash pesan info |
| `redirect($url)` | `void` | Redirect via Turbo |
| `skipRender()` | `void` | Jangan render ulang |
| `stream()` | `TurboStream` | Buat TurboStream builder |

### Directive Reference (JavaScript)

| Directive | Keterangan |
|:----------|:-----------|
| `tf-wire:click="method"` | Panggil method PHP |
| `tf-wire:click="method(1, 'a')"` | Dengan parameter |
| `tf-wire:submit="method"` | Handle form submit |
| `tf-wire:model="prop"` | Two-way binding (debounce 300ms) |
| `tf-wire:model.lazy="prop"` | Binding on blur |
| `tf-wire:model.debounce.500ms="prop"` | Custom debounce |
| `tf-wire:confirm="Yakin?"` | Dialog konfirmasi |
| `tf-wire:loading` | Tampil saat request |
| `tf-wire:loading.remove` | Hilang saat request |
| `tf-wire:loading.class="cls"` | Tambah class saat request |
| `tf-wire:target="method"` | Loading untuk action spesifik |
| `tf-wire:keydown.enter="method"` | Shortcut keyboard |
| `tf-wire:offline` | Tampil saat offline |
| `tf-wire:prefetch` | Prefetch on hover |
| `tf-wire:optimistic.remove` | Optimistic: hapus elemen |
| `tf-wire:optimistic.class="cls"` | Optimistic: tambah class |
| `tf-wire:upload.progress="field"` | Progress upload |
| `tf-wire:upload.preview="field"` | Preview gambar upload |
| `tf-wire:init="method"` | Auto-call saat pertama render |

---

## 🔗 Related Documentation

- [Blade Engine](blade.md) — Template engine
- [Routing](routing.md) — HTTP routing
- [Controllers](controllers.md) — Request handling
- [Validation](validation.md) — Form validation
- [Helpers](helpers.md) — Global functions

---

<div align="center">

[Back to Documentation](README.md) • [Main README](../README.md)

</div>
