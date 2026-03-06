<?php

namespace TheFramework\Repositories;

use Exception;
use TheFramework\App\Database\Database;
use TheFramework\Models\User;

class UserRepository
{
    protected User $model;
    protected Database $db;

    public function __construct()
    {
        $this->model = new User();
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        return $this->model
            ->query()
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    public function getInformation(string $uid)
    {
        return $this->model->where('uid', '=', $uid)->first();
    }

    public function findByEmail(string $email, ?string $uid = null)
    {
        $query = $this->model->query()->where('email', '=', $email);
        if ($uid) {
            $query->where('uid', '!=', $uid);
        }
        return $query->first();
    }

    public function findByName(string $name, ?string $uid = null)
    {
        $query = $this->model->query()->where('name', '=', $name);
        if ($uid) {
            $query->where('uid', '!=', $uid);
        }
        return $query->first();
    }

    public function createRepo(array $data)
    {
        return $this->db->transaction(function () use ($data) {
            return clone $this->model->create($data);
        });
    }

    public function updateRepo(array $data, string $uid)
    {
        return $this->db->transaction(function () use ($data, $uid) {
            $user = $this->getInformation($uid);
            if (!$user) {
                throw new Exception('User not found');
            }
            $user->fill($data);
            $user->save();
            return clone $user;
        });
    }

    public function deleteRepo(string $uid)
    {
        return $this->db->transaction(function () use ($uid) {
            $user = $this->getInformation($uid);
            if (!$user) {
                throw new Exception('User not found');
            }
            return $user->delete();
        });
    }
}
