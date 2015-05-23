<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wallmander\ElasticsearchIndexer\Controller;

/**
 * Class Profiler.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Install
{
    /**
     * Hooked on plugin activation.
     */
    public static function actionActivate()
    {
        $logDir = ESI_PATH.'../logs/';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }
}
