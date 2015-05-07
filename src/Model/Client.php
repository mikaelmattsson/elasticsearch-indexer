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
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\RequestException;

/**
 * Class Client
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
            'hosts' => explode(',', get_option('esi_hosts', '127.0.0.1:9200')),
        ]);
    }

    /**
     * @return string
     * @author 10up/ElasticPress
     */
    public function getIndexName()
    {
        $siteUrl = get_site_url($this->blogID);

        if (!empty($siteUrl)) {
            $indexName = preg_replace('#https?://(www\.)?#i', '', $siteUrl);
            $indexName = preg_replace('#[^\w]#', '', $indexName) . '-' . $this->blogID;
        } else {
            $indexName = false;
        }

        return apply_filters('esi_index_name', $indexName);
    }

    /**
     * @param int|null $blogId
     * @return $this
     */
    public function setBlog($blogId = null)
    {
        $this->blogID = $blogId ? $blogId : get_current_blog_id();
        return $this;
    }

    public function isAvailable()
    {
        return (bool) static::indicesExists($this->getIndexName());
    }

    public static function indicesExists($index)
    {
        try {
            $client = new HttpClient();
            $host   = explode(',', get_option('esi_hosts', '127.0.0.1:9200'));
            $res    = $client->get('http://' . $host[0] . '/' . $index)->send();
            return $res->getBody();
        } catch (RequestException $e) {
            return false;
        }
    }

    public static function getIndices()
    {
        try {
            $client = new HttpClient();
            $host   = explode(',', get_option('esi_hosts', '127.0.0.1:9200'));
            $res    = $client->get('http://' . $host[0] . '/_cat/indices?v')->send();
            return $res->getBody();
        } catch (RequestException $e) {
            return $e->getError();
        }
    }

    public static function getStatus()
    {
        try {
            $client = new HttpClient();
            $host   = explode(',', get_option('esi_hosts', '127.0.0.1:9200'));
            $res    = $client->get('http://' . $host[0] . '/_status')->send();
            return $res->getBody();
        } catch (RequestException $e) {
            return false;
        }
    }

}
