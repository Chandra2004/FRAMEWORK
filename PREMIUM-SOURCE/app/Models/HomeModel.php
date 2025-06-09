<?php
    namespace {{NAMESPACE}}\Models;

    use {{NAMESPACE}}\App\CacheManager;
    use {{NAMESPACE}}\App\Database;
    use {{NAMESPACE}}\App\Config;
    use {{NAMESPACE}}\App\Logging;
    use Defuse\Crypto\Crypto;
    use Defuse\Crypto\Key;

    class HomeModel extends Database {
        private $db;
        private $encryptionKey;

        public function __construct()
        {
            $this->db = Database::getInstance();
            // Muat kunci enkripsi dari .env
            $keyString = Config::get('ENCRYPTION_KEY');
            if (!$keyString) {
                throw new \Exception('Encryption key not configured in .env');
            }
            $this->encryptionKey = Key::loadFromAsciiSafeString($keyString);
        }

        // Tambahkan getter untuk encryptionKey
        public function getEncryptionKey()
        {
            return $this->encryptionKey;
        }

        public function getUserData() {
            return CacheManager::remember(
                'all_users', 
                60, 
                function() {
                    $this->db->query("SELECT * FROM users");
                    $users = $this->db->resultSet();
                    // Dekripsi data untuk setiap pengguna
                    foreach ($users as &$user) {
                        $user['name'] = $this->decryptData($user['name']);
                        $user['email'] = $this->decryptData($user['email']);
                    }
                    return ['users' => $users];
                }
            );
        }

        public function getUserDetail($id) {
            return CacheManager::remember(
                "user_detail:{$id}", 
                60, 
                function() use ($id) { 
                    $this->db->query("SELECT * FROM users WHERE id = :id");
                    $this->db->bind('id', $id);
                    $data = $this->db->single();
                    if ($data) {
                        // Dekripsi data sensitif
                        $data['name'] = $this->decryptData($data['name']);
                        $data['email'] = $this->decryptData($data['email']);
                    }
                    return $data;
                }
            );
        }

        public function createUser($name, $email, $profilePicture = null) {
            // Enkripsi data sensitif
            $encryptedName = Crypto::encrypt($name, $this->encryptionKey);
            $encryptedEmail = Crypto::encrypt($email, $this->encryptionKey);

            $this->db->query("INSERT INTO users (name, email, profile_picture) VALUES (:name, :email, :profile_picture)");
            $this->db->bind(':name', $encryptedName);
            $this->db->bind(':email', $encryptedEmail);
            $this->db->bind(':profile_picture', $profilePicture);

            $result = $this->db->execute();
            if ($result) {
                // Hapus cache untuk memastikan data baru dimuat
                CacheManager::forget('all_users');
            }
            return $result;
        }

        public function deleteUserData($id) {
            $this->db->query("SELECT profile_picture FROM users WHERE id = :id");
            $this->db->bind(':id', $id);
            $user = $this->db->single();
            
            if ($user && !empty($user['profile_picture'])) {
                $filePath = dirname(__DIR__, 2) . '/private-uploads/user-pictures/' . $user['profile_picture'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->db->query("DELETE FROM users WHERE id = :id");
            $this->db->bind(':id', $id);
            $result = $this->db->execute();

            if ($result) {
                // Hapus cache terkait
                CacheManager::forget(['all_users', "user_detail:{$id}"]);
            }
            return $result;
        }

        public function updateUserData($id, $name, $email, $profilePicture = null) {
            // Enkripsi data sensitif
            $encryptedName = Crypto::encrypt($name, $this->encryptionKey);
            $encryptedEmail = Crypto::encrypt($email, $this->encryptionKey);

            $query = "UPDATE users SET name = :name, email = :email" .
                    ($profilePicture ? ", profile_picture = :profile_picture" : "") .
                    " WHERE id = :id";
            $this->db->query($query);
            $this->db->bind(':name', $encryptedName);
            $this->db->bind(':email', $encryptedEmail);
            if ($profilePicture) {
                $this->db->bind(':profile_picture', $profilePicture);
            }
            $this->db->bind(':id', $id);
            $result = $this->db->execute();

            if ($result) {
                // Hapus cache terkait
                CacheManager::forget(['all_users', "user_detail:{$id}"]);
            }
            return $result;
        }

        private function decryptData($encryptedData) {
            try {
                return Crypto::decrypt($encryptedData, $this->encryptionKey);
            } catch (\Exception $e) {
                Logging::getLogger()->error('Decryption Error: ' . $e->getMessage());
                return null;
            }
        }
    }
?>