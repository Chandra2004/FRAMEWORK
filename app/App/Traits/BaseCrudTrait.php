<?php

namespace TheFramework\App\Traits;

use Exception;
use TheFramework\Helpers\Helper;


/**
 * BaseCrudTrait - Framework Internal Utility
 * Memberikan kemampuan CRUD otomatis pada Controller.
 * Developer cukup 'use' trait ini di controller mereka.
 */
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
        $model = $this->getModel();
        $data = $model->all();

        return view($this->getViewPath() . '.index', [
            'title' => $this->getViewTitle('List'),
            'notification' => flash('notification'),
            'items' => $data,
            'errors' => error(),
            'old' => old()
        ]);
    }

    /**
     * Create - Form create
     */
    public function create()
    {
        return view($this->getViewPath() . '.create', [
            'title' => $this->getViewTitle('Create'),
            'notification' => flash('notification'),
            'errors' => error(),
            'old' => old()
        ]);
    }

    /**
     * Store - Simpan data baru
     */
    public function store()
    {
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
                return redirect($this->getRoutePath(), 'error', 'Gagal menyimpan data');
            }

            return redirect($this->getRoutePath(), 'success', 'Data berhasil dibuat');
        } catch (Exception $e) {
            return redirect($this->getRoutePath(), 'error', 'Terjadi kesalahan: ' . $e->getMessage());
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
            return redirect($this->getRoutePath(), 'error', 'Data tidak ditemukan');
        }

        return view($this->getViewPath() . '.show', [
            'title' => $this->getViewTitle('Detail'),
            'notification' => flash('notification'),
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
            return redirect($this->getRoutePath(), 'error', 'Data tidak ditemukan');
        }

        return view($this->getViewPath() . '.edit', [
            'title' => $this->getViewTitle('Edit'),
            'notification' => flash('notification'),
            'item' => $item,
            'errors' => error(),
            'old' => old()
        ]);
    }

    /**
     * Update - Update data
     */
    public function update($id)
    {
        try {
            $request = $this->getRequest();
            $data = $request->validated();

            $model = $this->getModel();
            $result = $model->update($data, $id);

            if (!$result) {
                return redirect($this->getRoutePath() . '/' . $id . '/edit', 'error', 'Gagal update data');
            }

            return redirect($this->getRoutePath() . '/' . $id, 'success', 'Data berhasil diupdate');
        } catch (Exception $e) {
            return redirect($this->getRoutePath() . '/' . $id . '/edit', 'error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Destroy - Hapus data
     */
    public function destroy($id)
    {
        try {
            $model = $this->getModel();
            $result = $model->delete($id);

            if (!$result) {
                return redirect($this->getRoutePath(), 'error', 'Gagal menghapus data');
            }

            return redirect($this->getRoutePath(), 'success', 'Data berhasil dihapus');
        } catch (Exception $e) {
            return redirect($this->getRoutePath(), 'error', 'Terjadi kesalahan: ' . $e->getMessage());
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
