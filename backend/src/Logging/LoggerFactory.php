<?php
declare(strict_types=1);

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\UidProcessor;

class LoggerFactory
{
    private static ?Logger $logger = null;

    public static function create(?string $channel = null, ?string $logDir = null): Logger
    {
        if (self::$logger !== null && $channel === null) {
            return self::$logger;
        }

        $channel = $channel ?: 'bangrondb';
        $logDir  = $logDir  ?: dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $logger = new Logger($channel);

        // Daily rotating file — keeps 30 days
        $rotating = new RotatingFileHandler(
            $logDir . '/' . $channel . '.log',
            30,               // max files
            Logger::toMonologLevel($_ENV['LOG_LEVEL'] ?? 'DEBUG'),
            true,             // bubble
            0644
        );
        $rotating->setFilenameFormat('{date}-{filename}', 'Y-m-d');
        $logger->pushHandler($rotating);

        // Plain stream for stderr (useful for docker)
        if (($_ENV['LOG_STDERR'] ?? 'false') === 'true') {
            $logger->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));
        }

        // Processors
        $logger->pushProcessor(new UidProcessor(16));
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(new IntrospectionProcessor(
            Logger::DEBUG,
            ['Flight\\', 'App\\Http\\Routes\\', 'App\\Http\\Middleware\\']
        ));

        if ($channel === 'bangrondb' || $channel === null) {
            self::$logger = $logger;
        }

        return $logger;
    }

    /**
     * Shortcut: get the default channel logger.
     */
    public static function getLogger(): Logger
    {
        return self::create();
    }

    /**
     * Create a dedicated security/audit channel.
     */
    public static function security(): Logger
    {
        return self::create('security');
    }

    /**
     * Create a dedicated auth channel.
     */
    public static function auth(): Logger
    {
        return self::create('auth');
    }
}