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

use stdClass;
use Wallmander\ElasticsearchIndexer\Model\Query;
use WP_Post;

/**
 * Class WooCommerceAdmin.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class WooCommerceAdmin
{
    /**
     * Search custom fields as well as content.
     * Replaces WC_Admin_Post_Types::Replaces shop_order_search_custom_fields.
     *
     * @param \Wallmander\ElasticsearchIndexer\Model\Query $query
     */
    public static function actionOrderSearch(Query $query)
    {
        global $pagenow;

        $wpQuery = $query->wp_query;

        if ('edit.php' != $pagenow || !$wpQuery->get('s') || $wpQuery->get('post_type') != 'shop_order') {
            return;
        }

        $search = str_replace('Order #', '', $wpQuery->get('s'));

        $searchFields = apply_filters('woocommerce_shop_order_search_fields', [
            '_billing_first_name',
            '_billing_last_name',
            '_shipping_first_name',
            '_shipping_last_name',
            '_order_key',
            '_billing_company',
            '_billing_address_1',
            '_billing_address_2',
            '_billing_city',
            '_billing_postcode',
            '_billing_country',
            '_billing_state',
            '_billing_email',
            '_billing_phone',
            '_shipping_address_1',
            '_shipping_address_2',
            '_shipping_city',
            '_shipping_postcode',
            '_shipping_country',
            '_shipping_state',
        ]);

        foreach ($searchFields as $key => $value) {
            $searchFields[$key] = 'post_meta.'.$value;
        }

        $searchFields[] = 'order_item_names';
        $searchFields[] = 'post_id';

        $query->setQuery([
            'bool' => [
                'should' => [
                    [
                        'multi_match' => [
                            'fields'               => $searchFields,
                            'type'                 => 'cross_fields',
                            'operator'             => 'and',
                            'minimum_should_match' => '50%',
                            'analyzer'             => 'esi_index_simple',
                            'query'                => $search,
                        ],
                    ],
                ],
            ],
        ]);

        if (!$wpQuery->get('orderby')) {
            $query->setSort('post_date', 'desc');
        }
    }

    /**
     * Add more fields to the index.
     *
     * @param stdClass $queryArgs
     * @param WP_Post  $post
     *
     * @return array
     */
    public static function filterPostSyncArgs(stdClass $queryArgs, WP_Post $post)
    {
        global $wpdb;
        if ($post->post_type !== 'shop_order') {
            return $queryArgs;
        }

        $orderItemNames = $wpdb->get_col(
            $wpdb->prepare("
                    SELECT order_item_name
                    FROM {$wpdb->prefix}woocommerce_order_items as order_items
                    WHERE order_id = %d
                    ",
                $post->ID
            )
        );

        $queryArgs->order_item_names = $orderItemNames;

        return $queryArgs;
    }
}
