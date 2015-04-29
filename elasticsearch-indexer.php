<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Elasticsearch Indexer
 * Description:       Elasticsearch indexer for Wordpress and Woocommerce
 * Version:           0.1.0
 * Author:            Mikael Mattsson
 * Text Domain:       elasticsearch-indexer
 */

if (defined('DISABLE_ES') && DISABLE_ES) {
    return;
}

define('ESI_PATH', dirname(__FILE__) . '/');
define('ESI_URL', plugins_url('/', __FILE__));

require_once ESI_PATH . 'vendor/autoload.php';
require_once ESI_PATH . 'functions.php';

Wallmander\ElasticsearchIndexer\Hooks::setup();
