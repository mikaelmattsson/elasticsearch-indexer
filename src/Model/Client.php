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
     * @param int|null $blogId
     * @return string
     * @author 10up/ElasticPress
     */
    public function getIndexName($blogId = null)
    {
        if ($blogId === null) {
            $blogId = $this->blogID;
        }
        $siteUrl = get_site_url($blogId);

        if (!empty($siteUrl)) {
            $indexName = preg_replace('#https?://(www\.)?#i', '', $siteUrl);
            $indexName = preg_replace('#[^\w]#', '', $indexName) . '-' . $blogId;
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

    /**
     * Evaluate if the we can search for posts
     *
     * @return bool
     */
    public function isAvailable()
    {
        return (bool) static::indicesExists($this->getIndexName());
    }

    /**
     * Send a simple get request to the Elasticsearch server
     *
     * @param $uri
     * @return \Guzzle\Http\Message\Response
     */
    public static function httpGet($uri)
    {
        $client = new HttpClient();
        $host   = explode(',', get_option('esi_hosts', '127.0.0.1:9200'));
        return $client->get('http://' . $host[0] . '/' . $uri)->send();
    }

    /**
     * Send a simple post request to the Elasticsearch server
     *
     * @param $uri
     * @param array $data
     * @return \Guzzle\Http\Message\Response
     */
    public static function httpPost($uri, $data = null)
    {
        $client = new HttpClient();
        $host   = explode(',', get_option('esi_hosts', '127.0.0.1:9200'));
        if ($data) {
            $data = json_encode($data);
        }
        return $client->post('http://' . $host[0] . '/' . $uri, null, $data)->send();
    }

    /**
     * Send a simple put request to the Elasticsearch server
     *
     * @param $uri
     * @param array $data
     * @return \Guzzle\Http\Message\Response
     */
    public static function httpPut($uri, $data = null)
    {
        $client = new HttpClient();
        $host   = explode(',', get_option('esi_hosts', '127.0.0.1:9200'));
        if ($data) {
            $data = json_encode($data);
        }
        return $client->put('http://' . $host[0] . '/' . $uri, null, $data)->send();
    }

    /**
     * Check if Elasticsearch is running and the index exists
     *
     * @param $index
     * @return bool|\Guzzle\Http\EntityBodyInterface|string
     */
    public static function indicesExists($index)
    {
        try {
            return static::httpGet($index)->getBody();
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * Get a neat list of all indexes as a single string
     *
     * @return \Guzzle\Http\EntityBodyInterface|string
     */
    public static function getIndices()
    {
        try {
            return static::httpGet('_cat/indices?v')->getBody();
        } catch (RequestException $e) {
            return $e->getRequest()->getResponse();
        }
    }

    /**
     * Get Elasticsearch status
     *
     * @return bool|\Guzzle\Http\EntityBodyInterface|string
     */
    public static function getStatus()
    {
        try {
            return static::httpGet('_status')->getBody();
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * @param $index
     * @param array|object $data
     * @return bool|\Guzzle\Http\EntityBodyInterface|string
     */
    public static function setSettings($index, $data)
    {
        try {
            return static::httpPut($index . '/_settings', $data)->getBody();
        } catch (RequestException $e) {
            echo $e->getRequest()->getResponse();
            return false;
        }
    }

    /**
     * Optimize the index for searches
     *
     * @param $index
     * @return bool|\Guzzle\Http\EntityBodyInterface|string
     */
    public static function optimize($index = null)
    {
        try {
            if ($index) {
                return static::httpPost($index . '/_optimize')->getBody();
            } else {
                return static::httpPost('_optimize')->getBody();
            }
        } catch (RequestException $e) {
            return false;
        }
    }
}
