Elasticsearch Indexer
=========

A plugin that integrates [WordPress](https://github.com/WordPress/WordPress) with [Elasticsearch](https://www.elastic.co/products/elasticsearch).

Aside from super fast full text search, this plugin will speed up post listings of any post type, including [WooCommerce](http://www.woothemes.com/woocommerce/) products.

The plugin works to by indexing all posts and post meta data and redirecting the requests by WordPress from MySQL to Elasticsearch.

The plugin requires that you have Elasticsearch installed on your server

###Not supported
 - Sticky posts
 - Password protected posts
 - Some date queries
 - Random sorting

###Todo
 - Add optional cross multisite searching
 - Add option for selecting which terms that should be searchable
