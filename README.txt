=== Elasticsearch Indexer ===
Contributors: wallmanderco
Tags: elasticsearch, indexer, performance, search engine
Requires at least: 3.6
Tested up to: 4.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Aside from super fast full text search, this plugin will speed up post listings of any post type, including WooCommerce products.

== Description ==

A plugin that integrates [WordPress](https://github.com/WordPress/WordPress) with [Elasticsearch](https://www.elastic.co/products/elasticsearch).

Aside from super fast full text search, this plugin will speed up post listings of any post type, including [WooCommerce](http://www.woothemes.com/woocommerce/) products.

The plugin works to by indexing all posts and post meta data and redirecting the requests by WordPress from MySQL to Elasticsearch.

The plugin requires that you have Elasticsearch installed on your server

[View the project on Github](https://github.com/wallmanderco/elasticsearch-indexer) for more info.

== Installation ==
1. Upload 'elasticsearch-indexer' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click on the new menu item and press the button “Reindex all posts”.
