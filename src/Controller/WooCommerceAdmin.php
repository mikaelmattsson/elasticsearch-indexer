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

        if ('edit.php' != $pagenow || empty($wpQuery->query_vars['s']) || $wpQuery->query_vars['post_type'] != 'shop_order') {
            return;
        }

        $searchFields = apply_filters('woocommerce_shop_order_search_fields', [
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

        $searchOrderId = str_replace('Order #', '', $wpQuery->query_vars['s']);
        if (!is_numeric($searchOrderId)) {
            $searchOrderId = 0;
        }

        $query->setQuery([
            'bool' => [
                'should' => [
                    [
                        'multi_match' => [
                            'fields' => $searchFields,
                            'query'  => $wpQuery->query_vars['s'],
                        ],
                    ],
                    [
                        'fuzzy_like_this' => [
                            'fields'         => $searchFields,
                            'like_text'      => $wpQuery->query_vars['s'],
                            'min_similarity' => apply_filters('esi_min_similarity', 0.75),
                        ],
                    ],
                    [
                        'term' => [
                            'post_id' => $searchOrderId,
                        ],
                    ],
                ],
            ],
        ]);

        $query->setSort('post_date', 'desc');
    }
}
