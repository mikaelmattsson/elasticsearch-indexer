<?php

if (defined('DISABLE_ES') && DISABLE_ES) {
    return;
}

require_once ESI_PATH.'vendor/autoload.php';
require_once ESI_PATH.'functions.php';

Wallmander\ElasticsearchIndexer\Hooks::setup();
