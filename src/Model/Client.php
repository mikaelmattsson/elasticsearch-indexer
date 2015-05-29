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

use Elasticsearch\Client as ElasticSearchClient;

/**
 * A connection to Elasticsearch.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Client extends ElasticSearchClient
{
    protected $blogID;

    /**
     * @param int|null $blogId
     */
    public function __construct($blogId = null)
    {
        $this->setBlog($blogId);

        return parent::__construct([
            'hosts'   => Config::getHosts(),
            'logging' => true,
            'logPath' => Log::getFilePath('elasticsearch'),
        ]);
    }

    /**
     * @param int|null $blogID
     *
     * @return string
     *
     * @author 10up/ElasticPress
     */
    public function getIndexName($blogID = null)
    {
        if ($blogID === null) {
            $blogID = $this->blogID;
        }

        return Config::getIndexName($blogID);
    }

    /**
     * @param int|null $blogId
     *
     * @return $this
     */
    public function setBlog($blogId = null)
    {
        $this->blogID = $blogId ? $blogId : get_current_blog_id();

        return $this;
    }

    /**
     * Evaluate if the we can search for posts.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return (bool) Service\Elasticsearch::indicesExists($this->getIndexName());
    }
}
