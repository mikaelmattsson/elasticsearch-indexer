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
 * Description: Elasticsearch indexer for WordPress and WooCommerce
 * Version: 1.3.0
 * Author: Mikael Mattsson
 * Text Domain: elasticsearch-indexer
 */
define('ESI_PLUGINFILE', __FILE__);
define('ESI_PATH', dirname(ESI_PLUGINFILE).'/');
define('ESI_URL', plugins_url('/', __FILE__));

if (version_compare(phpversion(), '5.4.0', '<') === true) {
    function esi_php_version_failed()
    {
        deactivate_plugins(ESI_PLUGINFILE);
        wp_die(__('Elastic search indexer requires at least php version 5.4', 'elasticsearch-indexer'));
    }
    add_action('admin_init', 'esi_php_version_failed');

    return;
}

require_once ESI_PATH.'bootstrap.php';
