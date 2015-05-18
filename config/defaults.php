<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'enable_integration'                  => true,
    'hosts'                               => '127.0.0.1:9200',
    'shards'                              => 5,
    'replicas'                            => 1,
    'include_posts_from_child_taxonomies' => true,
    'index_private_post_types'            => false,
    'profile_admin'                       => false,
    'profile_frontend'                    => false,
];
