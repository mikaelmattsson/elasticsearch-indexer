<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wallmander\ElasticsearchIndexer;

use Wallmander\ElasticsearchIndexer\Model\Config;
use Wallmander\ElasticsearchIndexer\Service\Elasticsearch;

/**
 * Class Hooks.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Hooks
{
    /**
     * Setup hooks.
     */
    public static function setup()
    {
        static::setupInstall();
        static::setupProfiler();
        static::setupAdmin();

        if (!Elasticsearch::isAvailable()) {
            return;
        }

        if (Config::option('user_index_version') < Config::option('plugin_index_version')) {
            return;
        }

        static::setupSync();
        static::setupQueryIntegration();
        static::setupWooCommerce();
        static::setupWooCommerceAdmin();
    }

    /**
     * Setup Installer hook.
     */
    public static function setupInstall()
    {
        $class = __NAMESPACE__.'\Controller\Install';
        register_activation_hook(ESI_PLUGINFILE, [$class, 'actionActivate']);
    }

    /**
     * Setup Profiler Admin hooks.
     */
    public static function setupProfiler()
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (!is_admin() && !Config::option('profile_frontend')) {
            return;
        }
        if (is_admin() && !Config::option('profile_admin')) {
            return;
        }

        $class = __NAMESPACE__.'\Controller\Profiler';
        $class = apply_filters('esi_controller_profiler', $class);

        $class::setup();
    }

    /**
     * Setup Admin hooks.
     */
    public static function setupAdmin()
    {
        $class = __NAMESPACE__.'\Controller\Admin';
        $class = apply_filters('esi_controller_admin', $class);

        add_action('admin_bar_menu', [$class, 'actionAdminBarMenu'], 80);

        if (is_admin()) {
            add_action('admin_menu', [$class, 'actionAdminMenu']);
            add_action('admin_init', [$class, 'actionAdminInit']);
            add_action('wp_ajax_es_reindex', [$class, 'ajaxReindex']);
        }
    }

    /**
     * Setup Sync hooks.
     */
    public static function setupSync()
    {
        $class = __NAMESPACE__.'\Controller\Sync';
        $class = apply_filters('esi_controller_sync', $class);

        // Sync post on create or update
        add_action('save_post', [$class, 'actionSavePost'], 90, 3);

        // Sync post delete
        add_action('delete_post', [$class, 'actionDeletePost']);

        // Sync new, deleted or changed metadata
        add_action('added_post_meta', [$class, 'actionUpdatedPostMeta'], 10, 4);
        add_action('updated_post_meta', [$class, 'actionUpdatedPostMeta'], 10, 4);
        add_action('deleted_post_meta', [$class, 'actionUpdatedPostMeta'], 10, 4);
    }

    /**
     * Setup QueryIntegration hooks.
     */
    public static function setupQueryIntegration()
    {
        if (!Config::option('enable_integration') || Config::option('is_indexing')) {
            return;
        }

        $class = __NAMESPACE__.'\Controller\QueryIntegration';
        $class = apply_filters('esi_controller_queryintegration', $class);

        // Make sure we return nothing for MySQL posts query
        add_filter('posts_request', [$class, 'filterPostsRequest'], 10, 2);

        // Add header
        add_action('pre_get_posts', [$class, 'actionPreGetPosts'], 5);

        // Nukes the FOUND_ROWS() database query
        add_filter('found_posts_query', [$class, 'filterFoundPostsQuery'], 5, 2);

        // Search and filter in EP_Posts to WP_Query
        add_filter('posts_results', [$class, 'filterThePosts'], 10, 2);

        // Ensure we're in a loop before we allow blog switching
        //add_action('loop_start', [$class, 'actionLoopStart'], 10, 1);

        // Properly restore blog if necessary
        //add_action('loop_end', [$class, 'actionLoopEnd'], 10, 1);

        // Properly switch to blog if necessary
        //add_action('the_post', [$class, 'actionThePost'], 10, 1);

        //add_filter('split_the_query', '__return_false', 40);
    }

    /**
     * Setup WooCommerce hooks.
     */
    public static function setupWooCommerce()
    {
        add_action('init', function () {
            if (!class_exists('WooCommerce') || !Config::option('enable_integration')) {
                return;
            }

            $class = __NAMESPACE__.'\Controller\WooCommerce';
            $class = apply_filters('esi_controller_woocommerce', $class);

            add_filter('pre_get_posts', [$class, 'actionPreGetPosts'], 15);

            add_action('init', function () {
                static::forceRemoveAction('posts_search', 'product_search');
            }, 15);
        });
    }

    /**
     * Setup WooCommerceAdmin hooks.
     */
    public static function setupWooCommerceAdmin()
    {
        add_action('init', function () {
            if (!class_exists('WooCommerce') || !Config::option('index_private_post_types')) {
                return;
            }

            $class = __NAMESPACE__.'\Controller\WooCommerceAdmin';
            $class = apply_filters('esi_controller_woocommerceadmin', $class);
            add_filter('esi_post_sync_args', [$class, 'filterPostSyncArgs'], 10, 2);

            if (Config::option('enable_integration')) {
                static::forceRemoveAction('parse_query', 'shop_order_search_custom_fields');
                add_action('esi_after_format_args', [$class, 'actionOrderSearch']);
            }
        }, 15);
    }

    /**
     * Remove a hook without a reference to the instance.
     *
     * @param string $tag
     * @param string $functionToRemove
     * @param int    $priority
     */
    public static function forceRemoveAction($tag, $functionToRemove, $priority = 10)
    {
        global $wp_filter;

        if (!empty($wp_filter[$tag][$priority])) {
            foreach ($wp_filter[$tag][$priority] as $key => $function) {
                if (substr($key, 32) == $functionToRemove) {
                    unset($wp_filter[$tag][$priority][$key]);
                }
            }
        }
    }
}
