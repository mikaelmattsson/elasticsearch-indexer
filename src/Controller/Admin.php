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

use Exception;
use Wallmander\ElasticsearchIndexer\Model\Config;
use Wallmander\ElasticsearchIndexer\Model\Indexer;
use Wallmander\ElasticsearchIndexer\Model\Log;
use Wallmander\ElasticsearchIndexer\Model\Service\Elasticsearch;
use Wallmander\ElasticsearchIndexer\Model\Service\WordPress;

/**
 * Class Admin.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Admin
{
    /**
     * Hooked on admin_menu. Adds the menu items to the admin sidebar.
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
     * Hooked on admin_init. Registers the options and enqueues admin style and javascript.
     */
    public static function actionAdminInit()
    {
        wp_enqueue_style('elasticsearch-indexer', ESI_URL.'assets/admin/style.css');
        wp_enqueue_script('elasticsearch-indexer', ESI_URL.'assets/admin/script.js', ['jquery']);
        foreach (Config::load('defaults') as $key => $value) {
            register_setting('esi_options_group', Config::OPTION_PREFIX.$key);
        }
    }

    /**
     * Admin Indexing Page.
     */
    public static function getIndex()
    {
        $sites = WordPress::getSites();
        require ESI_PATH.'/views/admin/index.php';
    }

    /**
     * Admin Settings Page.
     */
    public static function getSettings()
    {
        $hostsStatus = [];
        foreach (Config::getHosts() as $host) {
            $hostsStatus[] = Elasticsearch::ping($host);
        }

        require ESI_PATH.'/views/admin/settings.php';
    }

    /**
     * Admin Status Page.
     */
    public static function getStatus()
    {
        $indices = Elasticsearch::getIndices();
        $logs    = Log::get();
        require ESI_PATH.'/views/admin/status.php';
    }

    /**
     * Admin reindex, requested by the index page.
     */
    public static function ajaxReindex()
    {
        if (!isset($_POST['site']) || !isset($_POST['from']) || empty($_POST['size'])) {
            die('invalid request');
        }

        $site    = (int) $_POST['site'];
        $from    = (int) $_POST['from'];
        $size    = (int) $_POST['size'];

        try {
            $indexer               = new Indexer();
            list($indexed, $total) = $indexer->reindex($site, $from, $size);
            $data                  = (object) [
                'success' => false,
                'indexed' => $indexed,
                'total'   => $total,
            ];
            $data->success = true;
            header('Content-Type: application/json');
            echo json_encode($data);
        } catch (Exception $e) {
            $data = (object) [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            header('Content-Type: application/json');
            echo json_encode($data);
        }

        die();
    }
}
