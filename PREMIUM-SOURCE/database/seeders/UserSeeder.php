<?php
    namespace Database\Seeders;

    use {{NAMESPACE}}\Models\Seeders\User as UserModel;
    use {{NAMESPACE}}\App\Config;
    use Defuse\Crypto\Crypto;
    use Defuse\Crypto\Key;
    use Faker\Factory;

    class UserSeeder {
        private $encryptionKey;

        public function __construct() {
            Config::loadEnv();

            $keyString = Config::get('ENCRYPTION_KEY');
            if (!$keyString) {
                throw new \Exception('Encryption key not configured.');
            }
            $this->encryptionKey = Key::loadFromAsciiSafeString($keyString);
        }

        private function downloadImage($url, $destination) {
            $ch = curl_init($url);
            $fp = fopen($destination, 'wb');

            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);

            $success = curl_exec($ch);
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($destination);
                throw new \Exception("Failed to download image from $url");
            }
        }

        public function run() {
            $faker = Factory::create();
            $faker->unique(true);

            $uploadDir = dirname(__DIR__, 2) . '/private-uploads/user-pictures/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            for ($i = 0; $i < 10; $i++) {
                $imageUrl = "https://picsum.photos/400/400.webp";
                $uniqueFileName = 'profile_' . uniqid() . '.webp';
                $imagePath = $uploadDir . $uniqueFileName;

                try {
                    $this->downloadImage($imageUrl, $imagePath);
                    if (!file_exists($imagePath)) {
                        throw new \Exception("Image not saved at $imagePath");
                    }

                    UserModel::create([
                        'name' => Crypto::encrypt($faker->name, $this->encryptionKey),
                        'email' => Crypto::encrypt($faker->unique()->safeEmail, $this->encryptionKey),
                        'password' => password_hash('password123', PASSWORD_BCRYPT),
                        'profile_picture' => $uniqueFileName,
                        'is_active' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                } catch (\Exception $e) {
                    echo "Error: " . $e->getMessage() . "\n";
                }
            }

            echo "Seeding completed successfully!\n";
        }
    }
?>
