<?php
    namespace {{NAMESPACE}}\Http\Controllers;

    use Spatie\ImageOptimizer\OptimizerChainFactory;
    use {{NAMESPACE}}\App\{Config, Database, View, CacheManager};
    use {{NAMESPACE}}\Helpers\Helper;

    use {{NAMESPACE}}\Models\HomeModel;

    use Exception;
    use Defuse\Crypto\Crypto;

    class HomeController {
        private $homeModel;

        public function __construct() {
            $this->homeModel = new HomeModel();
        }

        public function index() {
            View::render('interface.home', ['status' => $this->checkDatabaseConnection()]);
        }

        public function user() {
            // Ambil flash message
            $notification = Helper::get_flash('notification');

            View::render('interface.user', [
                'userData' => $this->homeModel->getUserData()['users'] ?? [],
                'status' => $this->checkDatabaseConnection(),
                'notification' => $notification,
            ]);
        }

        public function detail(string $id) {
            $userDetail = $this->homeModel->getUserDetail($id);
            if (!$userDetail) return Helper::redirectToNotFound();

            // Ambil flash message
            $notification = Helper::get_flash('notification');

            View::render('interface.detail', [
                'userData' => $this->homeModel->getUserData()['users'] ?? [],
                'user' => $userDetail,
                'notification' => $notification,
            ]);
        }

        public function deleteUser(string $id) {
            try {
                $this->homeModel->deleteUserData($id);
                CacheManager::forget(['all_users', "user_detail:{$id}"]);
                Helper::redirect('/user', 'success', 'User deleted successfully');
            } catch (Exception $e) {
                Helper::redirect('/user', 'error', $e->getMessage());
            }
        }

        public function createUser() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                // Ambil flash message
                $notification = Helper::get_flash('notification');

                return View::render('interface.user', [
                    'userData' => $this->homeModel->getUserData()['users'] ?? [],
                    'status' => $this->checkDatabaseConnection(),
                    'notification' => $notification,
                ]);
            }

            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $profilePicture = $_FILES['profile_picture'] ?? null;

            if (empty($name) || empty($email)) {
                return View::render('interface.user', [
                    'userData' => $this->homeModel->getUserData()['users'] ?? [],
                    'status' => $this->checkDatabaseConnection(),
                    'error' => "Name and email cannot be empty",
                ]);
            }

            $fileName = null;
            if ($profilePicture && $profilePicture['error'] === UPLOAD_ERR_OK) {
                $fileName = $this->processImage($profilePicture);
                if ($fileName instanceof Exception) {
                    return View::render('interface.user', [
                        'userData' => $this->homeModel->getUserData()['users'] ?? [],
                        'status' => $this->checkDatabaseConnection(),
                        'error' => $fileName->getMessage(),
                    ]);
                }
            }

            try {
                $this->homeModel->createUser($name, $email, $fileName);
                CacheManager::forget('all_users');
                Helper::redirect('/user', 'success', 'User created successfully');
            } catch (Exception $e) {
                View::render('interface.user', [
                    'userData' => $this->homeModel->getUserData()['users'] ?? [],
                    'status' => $this->checkDatabaseConnection(),
                    'error' => "Failed to save user data: " . $e->getMessage(),
                ]);
            }
        }

        public function updateUser(string $id) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') return Helper::redirect("/user/{$id}");

            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $profilePicture = $_FILES['profile_picture'] ?? null;
            $deleteProfilePicture = isset($_POST['delete_profile_picture']);

            if (empty($name) || empty($email)) {
                return Helper::redirect("/user/{$id}", 'error', 'Name and Email cannot be empty');
            }

            $userData = $this->homeModel->getUserDetail($id);
            if (!$userData) {
                return Helper::redirect("/user/{$id}", 'error', 'User not found');
            }

            $fileName = $userData['profile_picture'];
            if ($deleteProfilePicture && $fileName) {
                // $uploadDir = dirname(__DIR__, 2) . '/private-uploads/user-pictures/';
                $uploadDir = dirname(__DIR__, 3) . '/private-uploads/user-pictures/';
                $filePath = $uploadDir . $fileName;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $fileName = null;
            }

            if ($profilePicture && $profilePicture['error'] === UPLOAD_ERR_OK) {
                $fileName = $this->processImage($profilePicture, $id);
                if ($fileName instanceof Exception) {
                    return Helper::redirect("/user/{$id}", 'error', $fileName->getMessage());
                }
            }

            try {
                $this->homeModel->updateUserData($id, $name, $email, $fileName);
                Helper::redirect('/user', 'success', 'User updated successfully');
            } catch (Exception $e) {
                Helper::redirect("/user/{$id}", 'error', "Failed to update user data: " . $e->getMessage());
            }
        }

        private function checkDatabaseConnection() {
            try {
                Database::getInstance();
                return "success";
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        private function processImage($file, $userId = null) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return new Exception("Failed to upload profile picture");
            }
        
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions)) {
                return new Exception("Invalid file type");
            }
        
            $maxFileSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxFileSize) {
                return new Exception("File size exceeds 2MB limit");
            }
        
            // Validasi MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        
            $allowedMimeTypes = ['image/jpeg', 'image/pjpeg', 'image/png'];
            if (!in_array($mime, $allowedMimeTypes)) {
                return new Exception("Invalid file type.");
            }
        
            // Validasi header gambar
            $fileContent = file_get_contents($file['tmp_name'], false, null, 0, 256);
            $validHeaders = [
                'jpg' => "\xFF\xD8\xFF",
                'jpeg' => "\xFF\xD8\xFF",
                'png' => "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"
            ];
            if (!isset($validHeaders[$ext]) || strpos($fileContent, $validHeaders[$ext]) !== 0) {
                return new Exception("Invalid image header.");
            }
        
            // Direktori sementara untuk sandboxing
            $tempDir = sys_get_temp_dir() . '/image_upload_' . uniqid() . '/';
            if (!mkdir($tempDir, 0700, true)) {
                return new Exception("Failed to create temporary directory.");
            }
        
            $tempPath = $tempDir . basename($file['tmp_name']);
            if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                rmdir($tempDir);
                return new Exception("Failed to move uploaded file.");
            }
        
            // Validasi metadata menggunakan exiftool (opsional, jika tersedia)
            if (exec('command -v exiftool')) {
                $exif = shell_exec("exiftool -j " . escapeshellarg($tempPath));
                $exifData = json_decode($exif, true);
                if (isset($exifData[0]['FileType']) && !in_array($exifData[0]['FileType'], ['JPEG', 'PNG'])) {
                    unlink($tempPath);
                    rmdir($tempDir);
                    return new Exception("Invalid image metadata.");
                }
            }
        
            // Pindahkan ke direktori permanen menggunakan ROOT_DIR
            $uploadDir = ROOT_DIR . '/private-uploads/user-pictures/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
        
            $fileName = uniqid() . '.webp';
            $filePath = $uploadDir . $fileName;
        
            // Konversi ke WebP
            $image = imagecreatefromstring(file_get_contents($tempPath));
            if (!$image) {
                unlink($tempPath);
                rmdir($tempDir);
                return new Exception("Failed to process the image");
            }
        
            imagewebp($image, $filePath, 70);
            imagedestroy($image);
        
            // Optimasi gambar
            OptimizerChainFactory::create()->optimize($filePath);
        
            // Hapus file sementara
            unlink($tempPath);
            rmdir($tempDir);
        
            // Hapus gambar lama jika ada
            if ($userId) {
                $oldProfilePicture = $this->homeModel->getUserDetail($userId)['profile_picture'] ?? null;
                if ($oldProfilePicture && file_exists($uploadDir . $oldProfilePicture)) {
                    unlink($uploadDir . $oldProfilePicture);
                }
            }
        
            return $fileName;
        }
    }
?>