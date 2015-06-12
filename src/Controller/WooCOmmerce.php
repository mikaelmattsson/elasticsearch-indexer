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

use WP_Query;

class WooCommerce
{
    /**
     * Hooked on pre_get_posts.
     *
     * @param WP_Query $wpQuery
     */
    public static function actionPreGetPosts(WP_Query $wpQuery)
    {
        /*
         * Remove WooCommerce hook on product search
         */
        if ($wpQuery->is_main_query() && $wpQuery->is_search()) {
            remove_action('wp', [WC()->query, 'get_products_in_view'], 2);
        }
    }
}
