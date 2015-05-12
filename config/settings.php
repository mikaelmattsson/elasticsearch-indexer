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
 * Full Credits to 10up/ElasticPress.
 */

return [
    'index' => [
        'number_of_shards'   => (int) get_option('esi_shards', 5),
        'number_of_replicas' => (int) get_option('esi_replicas', 1),
    ],
    'analysis' => [
        'analyzer' => [
            'default' => [
                'tokenizer' => 'standard',
                'filter'    => ['standard', 'ewp_word_delimiter', 'lowercase', 'stop', 'ewp_snowball'],
                'language'  => apply_filters('esi_analyzer_language', 'English'),
            ],
            'shingle_analyzer' => [
                'type'      => 'custom',
                'tokenizer' => 'standard',
                'filter'    => ['lowercase', 'shingle_filter'],
            ],
        ],
        'filter' => [
            'shingle_filter' => [
                'type'             => 'shingle',
                'min_shingle_size' => 2,
                'max_shingle_size' => 5,
            ],
            'ewp_word_delimiter' => [
                'type'              => 'word_delimiter',
                'preserve_original' => true,
            ],
            'ewp_snowball' => [
                'type'     => 'snowball',
                'language' => apply_filters('esi_analyzer_language', 'English'),
            ],
            'edge_ngram' => [
                'side'     => 'front',
                'max_gram' => 10,
                'min_gram' => 3,
                'type'     => 'edgeNGram',
            ],
        ],
    ],
];
