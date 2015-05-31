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
     * Hooked on save_post. Called when a post is updated.
     *
     * @param int $postID
     */
    public static function actionSavePost($postID)
    {
        global $importer;

        // If we have an importer we must be doing an import - let's abort
        if (!empty($importer)) {
            return;
        }

        $post = get_post($postID);

        if (!in_array($post->post_status, Indexer::getIndexablePostStati())) {
            // The post is not indexable but might have been. Try to delete.
            $indexer = new Indexer();
            $indexer->deletePost($post->ID);

            return;
        }

        if (in_array($post->post_type, Indexer::getIndexablePostTypes())) {
            do_action('esi_before_post_save', $post);

            $indexer = new Indexer();
            $indexer->indexPost($post);
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

    /**
     * Hooked on added_post_meta, updated_post_meta and deleted_post_meta. Called when post meta data is modified.
     *
     * @param int    $incrementID
     * @param int    $postID
     * @param string $metaKey
     * @param        $metaValue
     */
    public static function actionUpdatedPostMeta($incrementID, $postID, $metaKey, $metaValue)
    {
        $data = [
            'post_meta' => [
                $metaKey => get_post_meta($postID, $metaKey),
            ],
        ];
        $indexer = new Indexer();
        $indexer->updatePost($postID, $data);
    }
}
