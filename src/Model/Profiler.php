<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wallmander\ElasticsearchIndexer\Model;

class Profiler
{
    /**
     * List of performed Elasticsearch queries.
     */
    private static $elasticQueries = [];

    /**
     * @return array
     */
    public static function getMySQLQueries()
    {
        global $wpdb;

        $queries = $wpdb->queries;

        usort($queries, function ($a, $b) {
            return ($a[1] < $b[1]) * 2 - 1;
        });

        return $queries;
    }

    /**
     * @return float
     */
    public static function getMySQLQueriesTime()
    {
        global $wpdb;
        $totalTime = (float) 0;

        foreach ($wpdb->queries as $query) {
            $totalTime += $query[1];
        }

        return $totalTime;
    }

    /**
     * @return array
     */
    public static function getElasticQueries()
    {
        return static::$elasticQueries;
    }

    /**
     * @param array $elasticQueries
     * @param array $queryArgs
     */
    public static function addElasticsearchQueryArgs($elasticQueries, array $queryVars)
    {
        self::$elasticQueries[] = [$elasticQueries, $queryVars];
    }
}
