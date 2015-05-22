<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @wordpress-plugin
 * Plugin URI: http://wallmanderco.github.io/elasticsearch-indexer/
 * Plugin Name: Elasticsearch Indexer
 * Description: Elasticsearch indexer for Wordpress and Woocommerce
 * Version: 1.2.0
 * Author: Mikael Mattsson
 * Text Domain: elasticsearch-indexer
 */
if (defined('DISABLE_ES') && DISABLE_ES) {
    return;
}

define('ESI_PATH', dirname(__FILE__).'/');
define('ESI_URL', plugins_url('/', __FILE__));

require_once ESI_PATH.'vendor/autoload.php';
require_once ESI_PATH.'functions.php';

Wallmander\ElasticsearchIndexer\Hooks::setup();
