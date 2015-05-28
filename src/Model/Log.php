<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wallmander\ElasticsearchIndexer\Model;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Fetches posts.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Log
{
    /**
     * @var Logger|null
     */
    public static $log = null;

    /**
     * Get the logger instance.
     *
     * @return \Monolog\Logger
     */
    public static function getLoggerInstance()
    {
        if (static::$log === null) {
            static::$log = new Logger('elasticsearch-indexer');
            static::$log->pushHandler(new StreamHandler(static::getFilePath(), Logger::ERROR));
        }

        return static::$log;
    }

    /**
     * Add a line to the log.
     *
     * @param string $message
     */
    public static function add($message)
    {
        static::getLoggerInstance()->addError($message);
    }

    /**
     * Get entire log.
     *
     * @return string
     */
    public static function get()
    {
        return file_get_contents(static::getFilePath());
    }

    /**
     * Get the full path to a log file.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function getFilePath($filename = 'elasticsearch-indexer')
    {
        return ESI_PATH.'../../uploads/logs/'.$filename.'.log';
    }
}
