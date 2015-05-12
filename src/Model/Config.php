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

/**
 * Class Config.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Config
{
    /**
     * Fetch config array from a file in the config directory.
     *
     * @param string $config
     *
     * @return array
     */
    public static function get($config)
    {
        return require ESI_PATH.'config/'.$config.'.php';
    }
}
