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

use Exception;
use WP_Query;
use WP_User;

/**
 * Class Indexer.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Indexer extends Client
{
    /**
     * Called in admin to reindex all posts in all blogs.
     *
     * @param int $from
     * @param int $size
     */
    public function reindex($from, $size)
    {
        add_filter('esi_skip_query_integration', '__return_true');
        $indexed = 0;
        $total   = 0;
        if ($from == 0) {
            //$this->setRefreshInterval('-1');
        }
        if (is_multisite()) {
            foreach (wp_get_sites() as $site) {
                switch_to_blog($site['blog_id']);
                $this->setBlog($site['blog_id']);
                list($postCount, $foundPosts) = $this->reindexBlog($from, $size);
                $indexed += $postCount;
                $total += $foundPosts;
                restore_current_blog();
                $this->setBlog();
            }
        } else {
            list($postCount, $foundPosts) = $this->reindexBlog($from, $size);
            $indexed += $postCount;
            $total += $foundPosts;
        }
        if ($indexed >= $total) {
            echo "Finished indexing $indexed/$total posts…\n";
            $this->setRefreshInterval('10s');
            static::optimize();
        } else {
            echo "Indexed  $indexed/$total posts…\n";
        }
    }

    /**
     * Reindex all posts in current blog.
     *
     * @param int $offset
     * @param int $postsPerPage
     *
     * @return array
     */
    protected function reindexBlog($offset, $postsPerPage = 500)
    {
        set_time_limit(200);

        if ($offset == 0) {
            $this->flush();
        }

        $args = apply_filters('esi_index_posts_args', [
            'posts_per_page'      => $postsPerPage,
            'post_type'           => static::getIndexablePostTypes(),
            'post_status'         => static::getIndexablePostStati(),
            'offset'              => $offset,
            'ignore_sticky_posts' => true,
            'orderby'             => 'id',
            'order'               => 'asc',
        ]);

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $this->indexPosts($query->posts);
        }

        return [$query->post_count + $offset, $query->found_posts];
    }

    /**
     * Delete existing index, create new index and add mappings.
     */
    protected function flush()
    {
        $indexName = $this->getIndexName();
        if ($this->indices()->exists(['index' => $indexName])) {
            $this->indices()->delete(['index' => $indexName]);
        }
        $this->indices()->create([
            'index' => $indexName,
            'body'  => [
                'settings' => Config::get('settings'),
                'mappings' => Config::get('mappings'),
            ],
        ]);
    }

    /**
     * Set refresh_interval on all indexes.
     *
     * @param string $interval
     */
    public function setRefreshInterval($interval = '1s')
    {
        $sites = is_multisite() ? wp_get_sites() : [['blog_id' => get_current_blog_id()]];
        foreach ($sites as $site) {
            $index = $this->getIndexName($site['blog_id']);
            static::setSettings($index, [
                'index' => ['refresh_interval' => $interval],
            ]);
        }
    }

    /**
     * @param $post
     *
     * @return array|bool
     *
     * @author 10up/ElasticPress
     */
    public function indexPost($post)
    {
        if (!is_object($post)) {
            $post = get_post($post);
        }
        $postArgs = static::preparePost($post);

        if (apply_filters('esi_post_sync_kill', false, $postArgs, $post->ID)) {
            return false;
        }

        $response = $this->index([
            'index' => $this->getIndexName(),
            'type'  => 'post',
            'id'    => $postArgs->post_id,
            'body'  => $postArgs,
        ]);

        return $response;
    }

    /**
     * @param array $posts
     */
    public function indexPosts(array $posts)
    {
        $indexName = $this->getIndexName();
        $body      = [];
        foreach ($posts as $post) {
            $body[] = [
                'index' => [
                    '_index' => $indexName,
                    '_type'  => 'post',
                    '_id'    => $post->ID,
                ],
            ];
            $body[] = static::preparePost($post);
        }
        $responses = $this->bulk(['body' => $body]);
        if ($responses['errors']) {
            echo "Errors: \n";
            if ($responses['items']) {
                foreach ($responses['items'] as $item) {
                    if ($item['index']['status'] !== 201) {
                        echo 'Could not index: '.$item['index']['_id']."\n";
                        echo $item['index']['error']."\n";
                    }
                }
            }
        }
    }

    /**
     * Delete post index.
     *
     * @param int $postsID
     */
    public function deletePost($postsID)
    {
        try {
            $this->delete([
                'index' => $this->getIndexName(),
                'type'  => 'post',
                'id'    => $postsID,
            ]);
        } catch (Exception $e) {
        }
    }

    /**
     * @param $post
     *
     * @return object
     *
     * @author 10up/ElasticPress
     */
    public static function preparePost($post)
    {
        if (!is_object($post)) {
            $post = get_post($post);
        }

        $user = get_userdata($post->post_author);

        if ($user instanceof WP_User) {
            $user_data = [
                'raw'          => $user->user_login,
                'login'        => $user->user_login,
                'display_name' => $user->display_name,
                'id'           => $user->ID,
            ];
        } else {
            $user_data = [
                'raw'          => '',
                'login'        => '',
                'display_name' => '',
                'id'           => '',
            ];
        }

        $post_date         = $post->post_date;
        $post_date_gmt     = $post->post_date_gmt;
        $post_modified     = $post->post_modified;
        $post_modified_gmt = $post->post_modified_gmt;

        if (strtotime($post_date) <= 0) {
            $post_date = null;
        }

        if (strtotime($post_date_gmt) <= 0) {
            $post_date_gmt = null;
        }

        if (strtotime($post_modified) <= 0) {
            $post_modified = null;
        }

        if (strtotime($post_modified_gmt) <= 0) {
            $post_modified_gmt = null;
        }

        $post_args = (object) [
            'post_id'                  => $post->ID,
            'post_author'              => $user_data,
            'post_date'                => $post_date,
            'post_date_gmt'            => $post_date_gmt,
            'post_title'               => $post->post_title,
            'post_excerpt'             => $post->post_excerpt,
            'post_content'             => $post->post_content,
            'post_status'              => $post->post_status,
            'post_name'                => $post->post_name,
            'post_modified'            => $post_modified,
            'post_modified_gmt'        => $post_modified_gmt,
            'post_parent'              => $post->post_parent,
            'post_type'                => $post->post_type,
            'post_mime_type'           => $post->post_mime_type,
            'permalink'                => get_permalink($post->ID),
            'terms'                    => static::prepareTerms($post),
            'post_meta'                => static::prepareMeta($post),
            'post_date_object'         => static::prepareDateTerms($post_date),
            'post_date_gmt_object'     => static::prepareDateTerms($post_date_gmt),
            'post_modified_object'     => static::prepareDateTerms($post_modified),
            'post_modified_gmt_object' => static::prepareDateTerms($post_modified_gmt),
            'menu_order'               => $post->menu_order,
            'guid'                     => $post->guid,
            'comment_count'            => $post->comment_count,
            'post_meta_num'            => [],
        ];

        $metaInts = apply_filters('esi_post_meta_nums', ['_price'], $post);

        //for range filters
        foreach ($metaInts as $metaKey) {
            if ($metaValue = get_post_meta($post->ID, $metaKey, 1)) {
                $post_args->post_meta_num[$metaKey] = (int) $metaValue;
            }
        }

        //Add parent slug
        foreach ($post_args->terms as $taxSlug => $terms) {
            foreach ($terms as $i => $termData) {
                $post_args->terms[$taxSlug][$i]['all_slugs'] = [$termData['slug']];
                if ($termData['parent']) {
                    if ($parent = get_term_by('id', $termData['parent'], $taxSlug)) {
                        $post_args->terms[$taxSlug][$i]['all_slugs'][] = $parent->slug;
                    }
                }
            }
        }

        $post_args = apply_filters('esi_post_sync_args', $post_args, $post->ID);

        return $post_args;
    }

    /**
     * @param $post_date_gmt
     *
     * @return array
     *
     * @author 10up/ElasticPress
     */
    protected static function prepareDateTerms($post_date_gmt)
    {
        $timestamp  = strtotime($post_date_gmt);
        $date_terms = [
            'year'          => (int) date('Y', $timestamp),
            'month'         => (int) date('m', $timestamp),
            'week'          => (int) date('W', $timestamp),
            'dayofyear'     => (int) date('z', $timestamp),
            'day'           => (int) date('d', $timestamp),
            'dayofweek'     => (int) date('d', $timestamp),
            'dayofweek_iso' => (int) date('N', $timestamp),
            'hour'          => (int) date('H', $timestamp),
            'minute'        => (int) date('i', $timestamp),
            'second'        => (int) date('s', $timestamp),
            'm'             => (int) (date('Y', $timestamp).date('m', $timestamp)), // yearmonth
        ];

        return $date_terms;
    }

    /**
     * @param $post
     *
     * @return array
     *
     * @author 10up/ElasticPress
     */
    protected static function prepareTerms($post)
    {
        $taxonomies          = get_object_taxonomies($post->post_type, 'objects');
        $selected_taxonomies = [];

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->public) {
                $selected_taxonomies[] = $taxonomy;
            }
        }

        $selected_taxonomies = apply_filters('ep_sync_taxonomies', $selected_taxonomies, $post);

        if (empty($selected_taxonomies)) {
            return [];
        }

        $terms = [];

        foreach ($selected_taxonomies as $taxonomy) {
            $object_terms = get_the_terms($post->ID, $taxonomy->name);

            if (!$object_terms || is_wp_error($object_terms)) {
                continue;
            }

            foreach ($object_terms as $term) {
                $terms[$term->taxonomy][] = [
                    'term_id' => $term->term_id,
                    'slug'    => $term->slug,
                    'name'    => $term->name,
                    'parent'  => $term->parent,
                ];
            }
        }

        return $terms;
    }

    /**
     * @param $post
     *
     * @return array
     *
     * @author 10up/ElasticPress
     */
    public static function prepareMeta($post)
    {
        $meta = update_meta_cache('post', [$post->ID])[$post->ID];

        if (empty($meta)) {
            return [];
        }

        return array_map('maybe_unserialize', $meta);
    }

    /**
     * @return array
     */
    public static function getIndexablePostTypes()
    {
        if (Config::option('index_private_post_types')) {
            return get_post_types();
        }

        return get_post_types(['exclude_from_search' => false]);
    }

    /**
     * @return array
     */
    public static function getIndexablePostStati()
    {
        if (Config::option('index_private_post_types')) {
            return get_post_stati();
        }

        return get_post_stati(['exclude_from_search' => false]);
    }

    /**
     * @return array
     */
    public static function getSearchablePostTypes()
    {
        return get_post_types(['exclude_from_search' => false]);
    }
}
