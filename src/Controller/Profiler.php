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

use Wallmander\ElasticsearchIndexer\Model\Profiler as ProfilerModel;
use Wallmander\ElasticsearchIndexer\Model\Query;
use WP_Query;

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
        add_action('esi_after_format_args', [get_class(), 'actionAfterFormatArgs'], 90, 2);
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
        $queries        = ProfilerModel::getMySQLQueries();
        $totalTime      = ProfilerModel::getMySQLQueriesTime();
        $elasticQueries = ProfilerModel::getElasticQueries();

        require ESI_PATH.'/views/profiler/footer.php';
    }

    /**
     * Save the elasticsearch query.
     *
     * @param Query    $query
     * @param WP_Query $wpQuery
     */
    public static function actionAfterFormatArgs(Query $query, WP_Query $wpQuery)
    {
        ProfilerModel::addElasticsearchQueryArgs($query->getArgs(), $wpQuery->query_vars);
    }
}
