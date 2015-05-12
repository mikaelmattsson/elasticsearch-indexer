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

use Wallmander\ElasticsearchIndexer\Model\Indexer;

/**
 * Class Sync.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Sync
{
    /**
     * Hooked on transition_post_status. Called when a post is updated.
     *
     * @param string $newStatus
     * @param string $oldStatus
     * @param object $post
     *
     * @author 10up/ElasticPress
     */
    public static function actionTransitionPostStatus($newStatus, $oldStatus, $post)
    {
        global $importer;

        // If we have an importer we must be doing an import - let's abort
        if (!empty($importer)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $indexablePostStatuses = Indexer::getIndexablePostStati();

        if (!in_array($newStatus, $indexablePostStatuses) && !in_array($oldStatus, $indexablePostStatuses)) {
            return;
        }

        if (!current_user_can('edit_post', $post->ID) || 'revision' === get_post_type($post->ID)) {
            return;
        }

        if (!in_array($newStatus, $indexablePostStatuses)) {
            // The post is no longer an indexable post type
            $indexer = new Indexer();
            $indexer->deletePost($post->ID);
        } else {
            $post_type = get_post_type($post->ID);

            $indexablePostTypes = Indexer::getIndexablePostTypes();

            if (in_array($post_type, $indexablePostTypes)) {
                do_action('epi_index_on_transition', $post);
                $indexer = new Indexer();
                $indexer->indexPost($post);
            }
        }
    }

    /**
     * Hooked on delete_post. Called when a post is deleted.
     *
     * @param int $postsID
     */
    public static function actionDeletePost($postsID)
    {
        $indexer = new Indexer();
        $indexer->deletePost($postsID);
    }
}
