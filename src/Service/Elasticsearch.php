<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wallmander\ElasticsearchIndexer\Service;

use Exception;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\RequestException;
use Wallmander\ElasticsearchIndexer\Model\Client;
use Wallmander\ElasticsearchIndexer\Model\Config;

/**
 * Service layer for Elasticsearch.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class Elasticsearch
{
    protected static $isAvailable  = null;

    protected static $errorMessage = '';

    /**
     * Send a simple get request to the Elasticsearch server.
     *
     * @param $uri
     *
     * @return \Guzzle\Http\Message\Response
     */
    public static function httpGet($uri)
    {
        $client = new HttpClient();
        $host   = Config::getFirstHost();

        return $client->get($host.'/'.$uri)->send();
    }

    /**
     * Send a simple post request to the Elasticsearch server.
     *
     * @param $uri
     * @param array $data
     *
     * @return \Guzzle\Http\Message\Response
     */
    public static function httpPost($uri, $data = null)
    {
        $client = new HttpClient();
        $host   = Config::getFirstHost();
        if ($data) {
            $data = json_encode($data);
        }

        return $client->post($host.'/'.$uri, null, $data)->send();
    }

    /**
     * Send a simple put request to the Elasticsearch server.
     *
     * @param $uri
     * @param array $data
     *
     * @return \Guzzle\Http\Message\Response
     */
    public static function httpPut($uri, $data = null)
    {
        $client = new HttpClient();
        $host   = Config::getFirstHost();
        if ($data) {
            $data = json_encode($data);
        }

        return $client->put($host.'/'.$uri, null, $data)->send();
    }

    /**
     * Check if Elasticsearch is running and the index exists.
     *
     * @param $index
     *
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
     * Get a neat list of all indexes as a single string.
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
     * Get Elasticsearch status.
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
     *
     * @return bool|\Guzzle\Http\EntityBodyInterface|string
     */
    public static function setSettings($index, $data)
    {
        try {
            return static::httpPut($index.'/_settings', $data)->getBody();
        } catch (RequestException $e) {
            echo $e->getRequest()->getResponse();

            return false;
        }
    }

    /**
     * Optimize the index for searches.
     *
     * @param $index
     *
     * @return bool|\Guzzle\Http\EntityBodyInterface|string
     */
    public static function optimize($index = null)
    {
        try {
            if ($index) {
                return static::httpPost($index.'/_optimize')->getBody();
            }

            return static::httpPost('_optimize')->getBody();
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * Ping a server and get the status and time.
     *
     * @param string $host
     *
     * @return array
     */
    public static function ping($host)
    {
        $start   = microtime(true);
        $message = 'OK';
        $success = true;
        try {
            $client = new HttpClient();
            $client->get($host)->send();
        } catch (Exception $e) {
            $message = $e->getMessage();
            $success = false;
        }
        $end = microtime(true);

        return [
            'status'  => $message,
            'time'    => ($end - $start) * 1000,
            'success' => $success,
        ];
    }

    /**
     * Evaluate if the we can search for posts.
     *
     * @return bool
     */
    public static function isAvailable()
    {
        if (static::$isAvailable !== null) {
            return static::$isAvailable;
        }

        $client = new Client();

        try {
            static::$isAvailable = (bool) self::indicesExists($client->getIndexName());
        } catch (Exception $e) {
            static::$errorMessage = $e->getMessage();
            static::$isAvailable  = false;
        }

        return static::$isAvailable;
    }

    /**
     * Returns a latest saved status message if any.
     *
     * @return string
     */
    public static function getErrorMessage()
    {
        return self::$errorMessage;
    }
}
