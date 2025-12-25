<?php

namespace TheFramework\Models;

use TheFramework\App\Database;
use TheFramework\App\Model;
use Throwable;

class HomeModel extends Model
{
    private $database;
    protected $table = 'users';
    protected $primaryKey = 'uid';

    public function UserAtomic(array $data, string $uid = '', string $type = 'create')
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            $result = null;
            if ($type == 'create') {
                if ($this->query()->where('name', '=', $data['name'])->first()) {
                    $db->rollBack();
                    return 'name_exist';
                }

                if ($this->query()->where('email', '=', $data['email'])->first()) {
                    $db->rollBack();
                    return 'email_exist';
                }

                $result = $this->insert($data);
            } else if ($type == 'update') {
                if ($this->query()->where('name', '=', $data['name'])->where('uid', '!=', $uid)->first()) {
                    $db->rollBack();
                    return 'name_exist';
                }

                if ($this->query()->where('email', '=', $data['email'])->where('uid', '!=', $uid)->first()) {
                    $db->rollBack();
                    return 'email_exist';
                }

                $result = $this->update($data, $uid);
            }

            if (!$result) {
                $db->rollBack();
                return false;
            }

            $db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($db->isConnected()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function Status()
    {
        $this->database = Database::getInstance();

        return $this->database->testConnection() ? 'success' : 'failed';
    }

    public function GetAllUsers()
    {
        return $this->query()
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    public function InformationUser($uid)
    {
        return $this->find($uid);
    }

    public function DeleteUser($uid)
    {
        return $this->delete($uid);
    }
}
