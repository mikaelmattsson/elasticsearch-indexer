<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wallmander\ElasticsearchIndexer\Model\Service;

/**
 * Service layer for WordPress.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class WordPress
{
    /**
     * Get a list of wordpress site ids.
     *
     * @return \Guzzle\Http\Message\Response
     */
    public static function getSites()
    {
        if (is_multisite() && $sites = wp_get_sites()) {
            return $sites;
        }

        return [get_current_blog_id()];
    }

    /**
     * Wrapper for switch_to_blog.
     *
     * @param int $id
     */
    public static function switchToBlog($id)
    {
        if (is_multisite()) {
            switch_to_blog($id);
        }
    }

    /**
     * Wrapper for restore_current_blog.
     */
    public static function restoreCurrentBlog()
    {
        if (is_multisite()) {
            restore_current_blog();
        }
    }
}
