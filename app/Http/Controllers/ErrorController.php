<?php
    namespace {{NAMESPACE}}\Http\Controllers;

    use {{NAMESPACE}}\App\Config;
    use {{NAMESPACE}}\App\View;

    use {{NAMESPACE}}\Models\HomeModel;

    class ErrorController {
        private $homeModel;

        public function __construct() {
            $this->homeModel = new HomeModel();
        }

        function error404() {
            http_response_code(404);
            $model = [];

            View::render('error.404', $model);
        }
        
        function error500() {
            http_response_code(500);
            $model = [];

            View::render('error.500', $model);
        }

        function payment() {
            $model = [];

            View::render('error.payment', $model);
        }

        function maintenance() {
            $model = [];

            View::render('error.maintenance', $model);
        }
    }
?>