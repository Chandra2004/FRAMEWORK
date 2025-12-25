<?php

namespace TheFramework\App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SlackWebhookHandler;

class Logging
{
    private static $logger;

    public static function getLogger()
    {
        if (!self::$logger) {
            self::$logger = new Logger('app');
            self::$logger->pushHandler(new StreamHandler(__DIR__ . '/../../storage/logs/app.log', Logger::DEBUG));

            if ($webhook = Config::get('SLACK_WEBHOOK_URL')) {
                self::$logger->pushHandler(new SlackWebhookHandler($webhook, Logger::ERROR));
            }
        }
        return self::$logger;
    }
}
