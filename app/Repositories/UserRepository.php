<?php

namespace TheFramework\Repositories;

use TheFramework\Models\User;

/**
 * UserRepository — Data Access Layer
 *
 * Abstraksi query untuk model User.
 * Controller TIDAK boleh akses database langsung.
 * Alurnya: Controller → Service → Repository → Model.
 *
 * @package TheFramework\Repositories
 * @version 5.0.3
 */
class UserRepository
{
    protected User $model;

    public function __construct()
    {
        $this->model = new User();
    }

    /**
     * Ambil semua user (diurutkan terbaru).
     */
    public function getAll(): array
    {
        return $this->model->query()
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    /**
     * Cari user berdasarkan UID.
     */
    public function findByUid(string $uid): ?array
    {
        $result = $this->model->find($uid);
        return $result ?: null;
    }

    /**
     * Cari user berdasarkan email.
     */
    public function findByEmail(string $email): ?array
    {
        $result = $this->model->query()->where('email', '=', $email)->first();
        return $result ?: null;
    }

    /**
     * Cari user berdasarkan nama.
     */
    public function findByName(string $name): ?array
    {
        $result = $this->model->query()->where('name', '=', $name)->first();
        return $result ?: null;
    }

    /**
     * Cek apakah nama sudah dipakai (exclude UID tertentu).
     */
    public function isNameTaken(string $name, ?string $excludeUid = null): bool
    {
        $query = $this->model->query()->where('name', '=', $name);
        if ($excludeUid) {
            $query->where('uid', '!=', $excludeUid);
        }
        return (bool) $query->first();
    }

    /**
     * Cek apakah email sudah dipakai (exclude UID tertentu).
     */
    public function isEmailTaken(string $email, ?string $excludeUid = null): bool
    {
        $query = $this->model->query()->where('email', '=', $email);
        if ($excludeUid) {
            $query->where('uid', '!=', $excludeUid);
        }
        return (bool) $query->first();
    }

    /**
     * Buat user baru.
     */
    public function create(array $data): bool
    {
        return $this->model->create($data);
    }

    /**
     * Update user berdasarkan UID.
     */
    public function update(array $data, string $uid): bool
    {
        return $this->model->update($data, $uid);
    }

    /**
     * Hapus user berdasarkan UID.
     */
    public function delete(string $uid): bool
    {
        return $this->model->delete($uid);
    }

    /**
     * Hitung total user.
     */
    public function count(): int
    {
        $result = $this->model->query()->select('COUNT(*) as total')->first();
        return (int) ($result['total'] ?? 0);
    }
}
