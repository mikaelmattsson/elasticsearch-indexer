<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Wallmander\ElasticsearchIndexer\Model\Config;

return [
    'index' => [
        'number_of_shards'   => (int) Config::option('shards'),
        'number_of_replicas' => (int) Config::option('replicas'),
    ],
    'analysis' => [
        'analyzer' => [
            'esi_search_analyzer' => [
                'type'      => 'custom',
                'tokenizer' => 'standard',
                'filter'    => ['standard', 'lowercase', 'stop', 'esi_ngram', 'esi_snowball'],
                'language'  => apply_filters('esi_analyzer_language', 'English'),
            ],
            'esi_index_analyzer' => [
                'type'      => 'custom',
                'tokenizer' => 'keyword',
                'filter'    => ['standard', 'lowercase'],
            ],
            'esi_simple_analyzer' => [
                'type'      => 'custom',
                'tokenizer' => 'standard',
                'filter'    => ['standard', 'lowercase', 'keyword_repeat', 'porter_stem'],
            ],
        ],
        'filter' => [
            'esi_ngram' => [
                'type'     => 'nGram',
                'min_gram' => 3,
                'max_gram' => 20,
            ],
            'esi_snowball' => [
                'type'     => 'snowball',
                'language' => apply_filters('esi_analyzer_language', 'English'),
            ],
        ],
    ],
];
