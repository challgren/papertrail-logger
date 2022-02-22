<?php
declare(strict_types=1);

namespace PapertrailLogger;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Core\Configure;
use Cake\Log\Log;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

/**
 * Plugin for PaperTrailLogger
 */
class Plugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        if (
            !Configure::read('debug')
            && Configure::read('papertrail.host', env('PAPERTRAIL_HOST') ?? env('PAPERTRAIL_URL'))
            && Configure::read('papertrail.port', env('PAPERTRAIL_PORT'))
        ) {
            Log::setConfig('default', function () {
                $output = "%level_name%  %message%";
                $formatter = new LineFormatter($output, 'Y-m-d H:i:s.v');

                $log = new Logger(strval(Configure::read('papertrail.channel', 'cakephp')));
                $sysLog = new SyslogUdpHandler(
                    strval(Configure::read('papertrail.host', env('PAPERTRAIL_HOST') ?? env('PAPERTRAIL_URL'))),
                    intval(Configure::read('papertrail.port', env('PAPERTRAIL_PORT'))),
                    LOG_USER,
                    Logger::DEBUG,
                    true,
                    strval(Configure::read('papertrail.ident', 'ident'))
                );
                $sysLog->setFormatter($formatter);
                $log->pushHandler($sysLog);

                return $log;
            });

            if (Configure::read('papertrail.drop', false)) {
                Log::drop('debug');
                Log::drop('error');
            }
        }
    }
}
