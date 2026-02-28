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
        try {
            $this->db->beginTransaction();
            $createUser = $this->model->create($data);
            $this->db->commit();
            return $createUser;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function updateRepo(array $data, string $uid)
    {
        try {
            $this->db->beginTransaction();
            $updateUser = $this->model->where('uid', '=', $uid)->update($data);
            $this->db->commit();
            return $updateUser;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function deleteRepo(string $uid)
    {
        try {
            $this->db->beginTransaction();
            $deleteUser = $this->model->where('uid', '=', $uid)->delete();
            $this->db->commit();
            return $deleteUser;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
