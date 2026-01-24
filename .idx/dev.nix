{ pkgs, ... }: {
  channel = "stable-24.05";

  packages = [
    pkgs.php83
    pkgs.php83Extensions.curl
    pkgs.php83Extensions.fileinfo
    pkgs.php83Extensions.mbstring
    pkgs.php83Extensions.openssl
    pkgs.php83Extensions.pdo
    pkgs.php83Extensions.pdo_mysql
    pkgs.php83Extensions.tokenizer
    pkgs.php83Extensions.xml
    pkgs.php83Extensions.intl
    pkgs.php83Extensions.zip
    pkgs.php83Extensions.bcmath
    pkgs.php83Packages.composer
    pkgs.nodejs_20
    pkgs.zip
    pkgs.unzip
    pkgs.git
  ];

  services.mysql = {
    enable = true;
    package = pkgs.mariadb;
  };

  env = {
    PHP_PATH = "${pkgs.php83}/bin/php";
    # Mengizinkan composer jalan sebagai root di container
    COMPOSER_ALLOW_SUPERUSER = "1";
    # Konfigurasi Database Default untuk IDX
    DB_CONNECTION = "mysql";
    DB_HOST = "127.0.0.1";
    DB_PORT = "3306";
    DB_DATABASE = "the_framework_db";
    DB_USERNAME = "root";
    DB_PASSWORD = "";
  };

  idx = {
    extensions = [
      # PHP & Framework
      "bmewburn.vscode-intelephense-client"
      "amirmarmul.laravel-blade-vscode"
      "onecentlin.laravel-blade"
      "shufo.vscode-blade-formatter"
      
      # Tools
      "mikestead.dotenv"
      "cweijan.vscode-database-client2"
      "rangav.vscode-thunder-client"
      "eamodio.gitlens"
      "formulahendry.code-runner"
      "editorconfig.editorconfig"
    ];

    # Lifecycle Hooks: Otomatisasi Setup
    workspace = {
      onCreate = {
        # Setup Environment Awal
        setup-env = ''
          cp .env.example .env
          php artisan setup
          composer install
        '';
        
        # Setup Database (Tunggu mysql ready lalu buat DB)
        setup-db = ''
          # Tunggu service MySQL naik
          while ! mysqladmin ping -h"localhost" --silent; do
            sleep 1
          done
          
          # Buat database jika belum ada
          mysql -u root -e "CREATE DATABASE IF NOT EXISTS the_framework_db;"
          
          # Migrasi
          php artisan migrate --force
        '';
      };
      
      onStart = {
        # Pastikan server jalan
        start-server = "php artisan serve";
      };
    };

    previews = {
      enable = true;
      previews = {
        web = {
          command = ["php" "artisan" "serve" "--host=0.0.0.0" "--port=$PORT"];
          manager = "web";
        };
      };
    };
  };
}
