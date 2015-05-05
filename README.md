Elasticsearch Indexer
=========

A plugin that integrates [WordPress](https://github.com/WordPress/WordPress) with [Elasticsearch](https://www.elastic.co/products/elasticsearch).

Aside from super fast full text search, this plugin will speed up post listings of any post type, including [WooCommerce](http://www.woothemes.com/woocommerce/) products.

The plugin works to by indexing all posts and post meta data and redirecting the requests by WordPress from MySQL to Elasticsearch.

The plugin requires that you have Elasticsearch installed on your server

### Demo
 - [WooCommerce 10 000 products](http://enabled-1.es-demo.wallmanderco.se/)
 - [WooCommerce 10 000 products without elasticsearch](http://disabled-1.es-demo.wallmanderco.se/)
 - [WooCommerce 135 000 products](http://enabled-2.es-demo.wallmanderco.se/)
 - [WooCommerce 135 000 products without elasticsearch](http://disabled-2.es-demo.wallmanderco.se/)

## Installation from Github
 1. Download the zip file from github.
 1. Unzip and put the new directory inside you plugins directory (`/wp-content/plugins/`) in wordpress.
 1. run `composer install` inside the new directory.
 1. Make sure that Elasticsearch is installed and running on your server  
(You can test this by running `curl -XGET -i 'http://127.0.0.1:9200'` in the command line)
 1. Activate the plugin in the wordpress admin panel.
 1. Click on the new menu item and press the button “Reindex all posts”.

### Not supported
 - Sticky posts
 - Password protected posts
 - Random sorting

## Links
 - Wordpress plugin page: https://wordpress.org/plugins/elasticsearch-indexer/

## License
Elasticsearch Indexer is licensed under [General Public License (GPLv2)](LICENSE).
