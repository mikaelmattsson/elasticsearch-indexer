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

use Wallmander\ElasticsearchIndexer\Model\Indexer;
use Wallmander\ElasticsearchIndexer\Model\Client;

/**
 * Class Admin
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Admin
{
    /**
     * Hooked on admin_menu. Adds the menu items to the admin sidebar
     */
    public static function actionAdminMenu()
    {
        add_menu_page(
            'ES Indexer',
            'ES Indexer',
            'manage_options',
            'esindexer_index',
            [get_class(), 'getIndex'],
            'dashicons-networking',
            30
        );
        add_submenu_page(
            'esindexer_index',
            'Settings',
            'Settings',
            'manage_options',
            'esindexer_indexer',
            [get_class(), 'getSettings']
        );
        add_submenu_page(
            'esindexer_index',
            'Status',
            'Status',
            'manage_options',
            'esindexer_status',
            [get_class(), 'getStatus']
        );
    }

    /**
     * Hooked on admin_init. Registers the options and enqueues admin style and javascript
     */
    public static function actionAdminInit()
    {
        wp_enqueue_style('elasticsearch-indexer', ESI_URL . 'assets/admin/style.css');
        wp_enqueue_script('elasticsearch-indexer', ESI_URL . 'assets/admin/script.js', ['jquery']);
        register_setting('esi_options_group', 'esi_hosts');
        register_setting('esi_options_group', 'esi_shards');
        register_setting('esi_options_group', 'esi_replicas');
        register_setting('esi_options_group', 'esi_filter_subtaxes');
    }

    /**
     * Admin Indexing Page
     */
    public static function getIndex()
    {
        require ESI_PATH . '/views/admin/index.php';
    }

    /**
     * Admin Settings Page
     */
    public static function getSettings()
    {
        require ESI_PATH . '/views/admin/settings.php';
    }

    /**
     * Admin Status Page
     */
    public static function getStatus()
    {
        $client  = new Client();
        $indices = $client->getIndices();
        require ESI_PATH . '/views/admin/status.php';
    }

    /**
     * Admin reindex, requested by the index page
     */
    public static function ajaxReindex()
    {
        if (!isset($_POST['from']) || empty($_POST['size'])) {
            die('invalid request');
        }
        $from    = $_POST['from'];
        $size    = $_POST['size'];
        $indexer = new Indexer;
        $indexer->reindex($from, $size);
        die();
    }

}
