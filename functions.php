<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Wallmander\ElasticsearchIndexer\Model\Query;

/**
 * @return bool
 */
function esi_plugin_activated()
{
    return true;
}

/**
 * @param null|WP_Query $wpQuery
 *
 * @return \Wallmander\ElasticsearchIndexer\Model\Query
 */
function es_query($wpQuery = null)
{
    $esq = new Query();
    if ($wpQuery instanceof WP_Query) {
        $esq->applyWpQuery($wpQuery);
    }

    return $esq;
}
