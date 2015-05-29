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

use Wallmander\ElasticsearchIndexer\Model\Config;

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
        $logDir = ESI_PATH.'../../uploads/logs/';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        Config::getIndexName(get_current_blog_id()); // Will generate a name if not set.
    }
}
