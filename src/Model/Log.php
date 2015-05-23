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
     * @var string
     */
    public static $filename = 'logs/elasticsearch-indexer-simple.log';

    /**
     * Get the logger instance.
     *
     * @return \Monolog\Logger
     */
    public static function getLoggerInstance()
    {
        if (static::$log === null) {
            static::$log = new Logger('elasticsearch-indexer');
            static::$log->pushHandler(new StreamHandler(ESI_PATH.'../'.static::$filename, Logger::ERROR));
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
        return file_get_contents(ESI_PATH.'../'.static::$filename);
    }
}
