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
class Profiler
{
    /**
     * Hooked on admin_menu. Adds the menu items to the admin sidebar.
     */
    public static function setup()
    {
        if (!defined('SAVEQUERIES')) {
            define('SAVEQUERIES', true);
        }

        add_action('wp_enqueue_scripts', [get_class(), 'actionWpEnqueueScripts']);
        add_action('admin_enqueue_scripts', [get_class(), 'actionWpEnqueueScripts']);
        add_action('shutdown', [get_class(), 'actionShutdown']);
    }

    /**
     * Add profiler style.
     */
    public static function actionWpEnqueueScripts()
    {
        wp_enqueue_style('elasticsearch-indexer-profiler', ESI_URL.'assets/profiler/style.css');
    }

    /**
     * Dump the collected data.
     */
    public static function actionShutdown()
    {
        global $wpdb;

        $queries = $wpdb->queries;

        usort($queries, function ($a, $b) {
            return ($a[1] < $b[1]) * 2 - 1;
        });

        $totalCount = count($queries);
        $totalTime  = (float) 0;

        foreach ($queries as $q) {
            $totalTime += $q[1];
        }

        require ESI_PATH.'/views/profiler/footer.php';
    }
}
