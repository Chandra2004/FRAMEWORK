{ pkgs, ... }: {
  channel = "stable-24.05";

  packages = [
    pkgs.php83
    pkgs.php83Extensions.curl
    pkgs.php83Extensions.fileinfo
    pkgs.php83Extensions.mbstring
    pkgs.php83Extensions.openssl
    pkgs.php83Extensions.pdo_mysql
    pkgs.php83Extensions.tokenizer
    pkgs.php83Extensions.xml
    pkgs.php83Packages.composer
    pkgs.nodejs_20
  ];

  services.mysql = {
    enable = true;
    package = pkgs.mariadb;
  };

  env = {
    PHP_PATH = "${pkgs.php83}/bin/php";
    COMPOSER_ALLOW_SUPERUSER = "1";
  };

  idx = {
    extensions = [
      "amirmarmul.laravel-blade-vscode"
      "onecentlin.laravel-blade"
      "shufo.vscode-blade-formatter"
      "bmewburn.vscode-intelephense-client"
      "cweijan.vscode-database-client2"
      "rangav.vscode-thunder-client"
    ];

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
