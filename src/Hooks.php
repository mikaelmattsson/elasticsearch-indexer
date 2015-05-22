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

use Wallmander\ElasticsearchIndexer\Model\Client;
use Wallmander\ElasticsearchIndexer\Model\Config;

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
        static::setupProfiler();

        static::setupAdmin();

        $client = new Client();
        if (!$client->isAvailable()) {
            return;
        }

        static::setupSync();

        if (Config::option('enable_integration')) {
            static::setupQueryIntegration();
        }
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

        $class = 'Wallmander\ElasticsearchIndexer\Controller\Profiler';
        $class = apply_filters('esi_controller_profiler', $class);

        $class::setup();
    }

    /**
     * Setup Admin hooks.
     */
    public static function setupAdmin()
    {
        if (!is_admin()) {
            return;
        }
        $class = 'Wallmander\ElasticsearchIndexer\Controller\Admin';
        $class = apply_filters('esi_controller_admin', $class);

        add_action('admin_menu', [$class, 'actionAdminMenu']);
        add_action('admin_init', [$class, 'actionAdminInit']);
        add_action('wp_ajax_es_reindex', [$class, 'ajaxReindex']);
    }

    /**
     * Setup Sync hooks.
     */
    public static function setupSync()
    {
        $class = 'Wallmander\ElasticsearchIndexer\Controller\Sync';
        $class = apply_filters('esi_controller_sync', $class);

        // Sync post on update
        add_action('transition_post_status', [$class, 'actionTransitionPostStatus'], 10, 3);

        // Delete posts
        add_action('delete_post', [$class, 'actionDeletePost']);
    }

    /**
     * Setup QueryIntegration hooks.
     */
    public static function setupQueryIntegration()
    {
        $class = 'Wallmander\ElasticsearchIndexer\Controller\QueryIntegration';
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
        //add_action('loop_start', [$class, 'actionLoopStart'], 10, 1); //see todo in README.md

        // Properly restore blog if necessary
        //add_action('loop_end', [$class, 'actionLoopEnd'], 10, 1); //see todo in README.md

        // Properly switch to blog if necessary
        //add_action('the_post', [$class, 'actionThePost'], 10, 1); //see todo in README.md

        //add_filter('split_the_query', '__return_false', 10, 2);
    }
}
