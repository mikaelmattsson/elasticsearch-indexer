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

use Wallmander\ElasticsearchIndexer\Model\Query;
use WP_Query;

/**
 * Class QueryIntegration
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class QueryIntegration
{
    private static $queryStack = [];

    /**
     * Filter query string used for get_posts(). Search for posts and save for later.
     * Return a query that will return nothing.
     *
     * @param string $request
     * @param \WP_Query $query
     * @return string
     */
    public static function filterPostsRequest($request, WP_Query $query)
    {
        if (apply_filters('esi_skip_query_integration', false, $query)) {
            return $request;
        }

        if (!Query::isCompatible($query)) {
            $query->is_elasticsearch_compatible = false;
            return $request;
        }

        $query->is_elasticsearch_compatible = true;

        global $wpdb;

        return "SELECT * FROM $wpdb->posts WHERE 1=0";
    }

    /**
     * Remove the found_rows from the SQL Query
     *
     * @param string $sql
     * @param \WP_Query $query
     * @return string
     */
    public static function filterFoundPostsQuery($sql, WP_Query $query)
    {
        if (apply_filters('esi_skip_query_integration', false, $query)) {
            return $sql;
        }

        if (empty($query->is_elasticsearch_compatible)) {
            return $sql;
        }

        return '';
    }

    /**
     * Disables cache_results, adds header.
     *
     * @param \WP_Query $query
     */
    public static function actionPreGetPosts(WP_Query $query)
    {
        if (apply_filters('esi_skip_query_integration', false, $query)) {
            return;
        }

        //$query->query_vars['suppress_filters'] = false;
        $query->set('cache_results', false);

        if (!headers_sent()) {
            header('X-ElasticsearchIndexer: true');
        }
    }

    /**
     * @param array $posts
     * @param \WP_Query &$query
     * @return array
     */
    public static function filterThePosts($posts, WP_Query &$query)
    {
        if (apply_filters('esi_skip_query_integration', false, $query)) {
            return $posts;
        }

        if (empty($query->is_elasticsearch_compatible)) {
            return $posts;
        }

        $scope = 'current';
        if (!empty($query->query_vars['sites'])) {
            $scope = $query->query_vars['sites'];
        }

        return Query::fromWpQuery($query, $scope)->getPosts();
    }

    /**
     * Switch to the correct site if the post site id is different than the actual one
     *
     * @param array $post
     */
    public static function actionThePost($post)
    {
        if (!is_multisite()) {
            return;
        }

        if (empty(static::$queryStack)) {
            return;
        }

        if (!esi_plugin_activated(static::$queryStack[0]) || apply_filters('esi_skip_query_integration', false,
                static::$queryStack[0])
        ) {
            return;
        }

        if (!empty($post->site_id) && get_current_blog_id() != $post->site_id) {
            restore_current_blog();

            switch_to_blog($post->site_id);

            remove_action('the_post', [get_class(), 'actionThePost'], 10, 1);
            setup_postdata($post);
            add_action('the_post', [get_class(), 'actionThePost'], 10, 1);
        }
    }

    /**
     * Ensure we've started a loop before we allow ourselves to change the blog
     *
     * @param \WP_Query $query
     */
    public static function actionLoopStart(WP_Query $query)
    {
        if (!is_multisite()) {
            return;
        }

        array_unshift(static::$queryStack, $query);
    }

    /**
     * Make sure the correct blog is restored
     *
     * @param \WP_Query $query
     */
    public static function actionLoopEnd(WP_Query $query)
    {
        if (!is_multisite()) {
            return;
        }

        array_pop(static::$queryStack);

        if (apply_filters('esi_skip_query_integration', false, $query)) {
            return;
        }

        if (!empty($GLOBALS['switched'])) {
            restore_current_blog();
        }
    }
}
