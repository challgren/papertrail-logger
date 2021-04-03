<?php
declare(strict_types=1);

namespace PapertrailLogger;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
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
            && Configure::read('papertrail.host', getenv('PAPERTRAIL_HOST'))
            && Configure::read('papertrail.port', getenv('PAPERTRAIL_PORT'))
        ) {
            Log::setConfig('default', function () {
                $output = "%level_name%  %message%";
                $formatter = new LineFormatter($output, 'Y-m-d H:i:s.v');

                $log = new Logger(strval(Configure::read('papertrail.channel', 'cakephp')));
                $sysLog = new SyslogUdpHandler(
                    strval(Configure::read('papertrail.host', getenv('PAPERTRAIL_HOST'))),
                    intval(Configure::read('papertrail.port', getenv('PAPERTRAIL_PORT'))),
                    LOG_USER,
                    Logger::DEBUG,
                    true,
                    strval(Configure::read('papertrail.channel', 'ident'))
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

    /**
     * Add routes for the plugin.
     *
     * If your plugin has many routes and you would like to isolate them into a separate file,
     * you can create `$plugin/config/routes.php` and delete this method.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'PaperTrailLogger',
            ['path' => '/paper-trail-logger'],
            function (RouteBuilder $builder) {
                // Add custom routes here

                $builder->fallbacks();
            }
        );
        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Add your middlewares here

        return $middlewareQueue;
    }
}
