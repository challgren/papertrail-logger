<?php
declare(strict_types=1);

namespace PapertrailLogger\Console;

use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\HelperRegistry;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

class PapertrailLoggerConsoleIo extends ConsoleIo
{
    /**
     * @var \Monolog\Logger|null Logger to send to Papertrail
     */
    protected $_log = null;

    /**
     * @var int Max dashes to have (this filters out ConsoleIo->hr($int) )
     */
    protected $_maxDashes = 10;

    /**
     * @inheritDoc
     */
    public function __construct(
        ?ConsoleOutput $out = null,
        ?ConsoleOutput $err = null,
        ?ConsoleInput $in = null,
        ?HelperRegistry $helpers = null
    ) {
        parent::__construct($out, $err, $in, $helpers);

        if (!Configure::read('debug')) {
            $formatter = new LineFormatter('[%datetime%] %channel%.%level_name%: %message%', 'Y-m-d H:i:s.v');

            $this->_log = new Logger(strval(Configure::read('papertrail.console.channel', 'cakephp')));
            $sysLog = new SyslogUdpHandler(
                strval(Configure::read('papertrail.host', getenv('PAPERTRAIL_HOST'))),
                intval(Configure::read('papertrail.port', getenv('PAPERTRAIL_PORT'))),
                LOG_USER,
                Logger::DEBUG,
                true,
                strval(Configure::read('papertrail.console.ident', 'cakephp'))
            );
            $sysLog->setFormatter($formatter);
            $this->_log->pushHandler($sysLog);
        }
    }

    /**
     * @inheritDoc
     */
    public function err($message = '', int $newlines = 1): int
    {
        if (is_array($message)) {
            foreach ($message as $k => $v) {
                if ($this->_log && !empty($v) && substr_count($v, '-') < $this->_maxDashes) {
                    $this->_log->error(strip_tags($v));
                }
                $message[$k] = FrozenTime::now()->format('Y-m-d H:i:s.v ') . $v;
            }
        } else {
            if ($this->_log && !empty($message) && substr_count($message, '-') < $this->_maxDashes) {
                $this->_log->error(strip_tags($message));
            }
            $message = FrozenTime::now()->format('Y-m-d H:i:s.v ') . $message;
        }

        return parent::err($message, $newlines);
    }

    /**
     * @inheritDoc
     */
    public function out($message = '', int $newlines = 1, int $level = self::NORMAL): ?int
    {
        if ($level <= $this->_level) {
            if (is_array($message)) {
                foreach ($message as $k => $v) {
                    $message[$k] = FrozenTime::now()->format('Y-m-d H:i:s.v ') . $v;
                    if ($this->_log && !empty($v) && substr_count($v, '-') < $this->_maxDashes) {
                        $this->_log->info(strip_tags($v));
                    }
                }
            } elseif ($this->_log && !empty($message) && substr_count($message, '-') < $this->_maxDashes) {
                $this->_log->info(strip_tags($message));
                $message = FrozenTime::now()->format('Y-m-d H:i:s.v ') . $message;
            }
        }

        return parent::out($message, $newlines, $level);
    }
}
