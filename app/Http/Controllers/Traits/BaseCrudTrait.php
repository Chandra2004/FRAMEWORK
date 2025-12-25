<?php

namespace TheFramework\Http\Controllers\Traits;

use Exception;
use TheFramework\App\View;
use TheFramework\Helpers\Helper;
use TheFramework\Models\HomeModel;

trait BaseCrudTrait
{
    /**
     * Get model instance (harus di-override di controller)
     */
    abstract protected function getModel();

    /**
     * Get request instance (harus di-override di controller)
     */
    abstract protected function getRequest();

    /**
     * Get route path untuk redirect (harus di-override di controller)
     */
    abstract protected function getRoutePath(): string;

    /**
     * Get view path prefix (mis. 'users' untuk 'users.index')
     */
    abstract protected function getViewPath(): string;

    /**
     * Get primary key field name (default: 'id')
     */
    protected function getPrimaryKey(): string
    {
        return 'id';
    }

    /**
     * Index - List semua data
     */
    public function index()
    {
        $notification = Helper::get_flash('notification');
        $model = $this->getModel();
        
        $data = $model->all();
        
        return View::render($this->getViewPath() . '.index', [
            'title' => $this->getViewTitle('List'),
            'notification' => $notification,
            'items' => $data,
            'errors' => Helper::validation_errors(),
            'old' => Helper::session_get('old_input', [])
        ]);
    }

    /**
     * Create - Form create
     */
    public function create()
    {
        $notification = Helper::get_flash('notification');
        
        return View::render($this->getViewPath() . '.create', [
            'title' => $this->getViewTitle('Create'),
            'notification' => $notification,
            'errors' => Helper::validation_errors(),
            'old' => Helper::session_get('old_input', [])
        ]);
    }

    /**
     * Store - Simpan data baru
     */
    public function store()
    {
        if (!Helper::is_post() || !Helper::is_csrf()) {
            return Helper::redirect($this->getRoutePath(), 'error', 'Invalid request', 5);
        }

        try {
            $request = $this->getRequest();
            $data = $request->validated();
            
            // Tambahkan primary key jika perlu (mis. UUID)
            if ($this->getPrimaryKey() === 'uid' && !isset($data['uid'])) {
                $data['uid'] = Helper::uuid();
            }

            $model = $this->getModel();
            $result = $model->insert($data);

            if (!$result) {
                return Helper::redirect($this->getRoutePath(), 'error', 'Gagal menyimpan data', 5);
            }

            return Helper::redirect($this->getRoutePath(), 'success', 'Data berhasil dibuat', 5);
        } catch (Exception $e) {
            return Helper::redirect($this->getRoutePath(), 'error', 'Terjadi kesalahan: ' . $e->getMessage(), 5);
        }
    }

    /**
     * Show - Detail data
     */
    public function show($id)
    {
        $notification = Helper::get_flash('notification');
        $model = $this->getModel();
        $item = $model->find($id);

        if (!$item) {
            return Helper::redirect($this->getRoutePath(), 'error', 'Data tidak ditemukan', 5);
        }

        return View::render($this->getViewPath() . '.show', [
            'title' => $this->getViewTitle('Detail'),
            'notification' => $notification,
            'item' => $item
        ]);
    }

    /**
     * Edit - Form edit
     */
    public function edit($id)
    {
        $notification = Helper::get_flash('notification');
        $model = $this->getModel();
        $item = $model->find($id);

        if (!$item) {
            return Helper::redirect($this->getRoutePath(), 'error', 'Data tidak ditemukan', 5);
        }

        // Merge dengan old input jika ada (untuk form yang gagal validasi)
        $old = Helper::session_get('old_input', []);
        if (!empty($old)) {
            $item = array_merge($item, $old);
        }

        return View::render($this->getViewPath() . '.edit', [
            'title' => $this->getViewTitle('Edit'),
            'notification' => $notification,
            'item' => $item,
            'errors' => Helper::validation_errors(),
            'old' => $old
        ]);
    }

    /**
     * Update - Update data
     */
    public function update($id)
    {
        if (!Helper::is_post() || !Helper::is_csrf()) {
            return Helper::redirect($this->getRoutePath(), 'error', 'Invalid request', 5);
        }

        try {
            $request = $this->getRequest();
            $data = $request->validated();
            
            $model = $this->getModel();
            $result = $model->update($data, $id);

            if (!$result) {
                return Helper::redirect($this->getRoutePath() . '/' . $id . '/edit', 'error', 'Gagal update data', 5);
            }

            return Helper::redirect($this->getRoutePath() . '/' . $id, 'success', 'Data berhasil diupdate', 5);
        } catch (Exception $e) {
            return Helper::redirect($this->getRoutePath() . '/' . $id . '/edit', 'error', 'Terjadi kesalahan: ' . $e->getMessage(), 5);
        }
    }

    /**
     * Destroy - Hapus data
     */
    public function destroy($id)
    {
        if (!Helper::is_post() || !Helper::is_csrf()) {
            return Helper::redirect($this->getRoutePath(), 'error', 'Invalid request', 5);
        }

        try {
            $model = $this->getModel();
            $result = $model->delete($id);

            if (!$result) {
                return Helper::redirect($this->getRoutePath(), 'error', 'Gagal menghapus data', 5);
            }

            return Helper::redirect($this->getRoutePath(), 'success', 'Data berhasil dihapus', 5);
        } catch (Exception $e) {
            return Helper::redirect($this->getRoutePath(), 'error', 'Terjadi kesalahan: ' . $e->getMessage(), 5);
        }
    }

    /**
     * Helper untuk generate view title
     */
    protected function getViewTitle(string $action): string
    {
        $resource = ucfirst(str_replace(['-', '_'], ' ', $this->getViewPath()));
        return "{$action} {$resource}";
    }
}

