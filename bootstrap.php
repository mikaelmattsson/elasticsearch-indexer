<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (defined('DISABLE_ES') && DISABLE_ES) {
    return;
}

require_once ESI_PATH.'vendor/autoload.php';
require_once ESI_PATH.'functions.php';

Wallmander\ElasticsearchIndexer\Hooks::setup();

do_action('esi_after_setup');
