<?php

/*
 * This file is part of Elasticsearch Indexer.
 *
 * (c) Wallmander & Co <mikael@wallmanderco.se>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wallmander\ElasticsearchIndexer\Model\Query;

use Wallmander\ElasticsearchIndexer\Model\Indexer;
use Wallmander\ElasticsearchIndexer\Model\Query;
use WP_Date_Query;
use Wp_Query;

/**
 * Builds an Elasticsearch query from Wp_Query using the query builder.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
class WpConverter
{
    public static function isCompatible(Wp_Query $wpQuery)
    {
        $q = $wpQuery->query_vars;

        $unsupportedQueryArgs = [
            'suppress_filters',
            'has_password',
            'post_password',
            'preview',
            'fields',
        ];

        foreach ($q as $key => $value) {
            if ($value && in_array($key, $unsupportedQueryArgs)) {
                return false;
            }
        }

        if ($q['fields'] == 'ids' || $q['fields'] == 'id=>parent') {
            return false;
        }

        if (!empty($q['post_status'])) {
            if (is_string($q['post_status'])) {
                $q['post_status'] = explode(' ', str_replace(',', ' ', $q['post_status']));
            }
            $ips = Indexer::getIndexablePostStati();
            foreach ($q['post_status'] as $value) {
                if (!in_array($value, $ips)) {
                    return false;
                }
            }
        }

        if (!empty($q['post_type']) && $q['post_type'] !== 'any') {
            if (is_string($q['post_type'])) {
                $q['post_type'] = explode(' ', str_replace(',', ' ', $q['post_type']));
            }
            $ipt = Indexer::getIndexablePostTypes();
            foreach ($q['post_type'] as $value) {
                if (!in_array($value, $ipt)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function formatArgs(Query $query, Wp_Query $wpQuery)
    {
        // Fill again in case pre_get_posts unset some vars.
        $q = $wpQuery->fill_query_vars($wpQuery->query_vars);

        $q = apply_filters('esi_before_format_args', $q, $query);

        if ($wpQuery->is_posts_page) {
            $q['pagename'] = '';
        }

        // Defaults
        if (!empty($q['nopaging'])) {
            $q['posts_per_page'] = '-1';
        }
        if (empty($q['posts_per_page'])) {
            $q['posts_per_page'] = get_option('posts_per_page');
        }
        if (empty($q['post_type'])) {
            if (!empty($q['wc_query']) && $q['wc_query'] == 'product_query') {
                $q['post_type'] = 'product';
            } elseif (!empty($q['tax_query'])) {
                $q['post_type'] = 'any';
            } else {
                $q['post_type'] = 'post';
            }
        } elseif (is_string($q['post_type'])) {
            $q['post_type'] = explode(' ', str_replace(',', ' ', $q['post_type']));
        }
        if (empty($q['orderby'])) {
            $q['orderby'] = 'none';
        }
        if ($wpQuery->is_search()) {
            if ($q['orderby'] == 'none' || $q['orderby'] == 'menu_order title') {
                $q['orderby'] = 'relevance';
            }
        }
        if (empty($q['post_status'])) {
            $q['post_status'] = ['publish'];
            if (is_user_logged_in()) {
                $q['post_status'][] = 'private';
            }
            if (is_admin()) {
                $q['post_status'][] = 'future';
                $q['post_status'][] = 'draft';
                $q['post_status'][] = 'pending';
            }
        } elseif (is_string($q['post_status'])) {
            $q['post_status'] = explode(' ', str_replace(',', ' ', $q['post_status']));
        }

        $q = apply_filters('esi_before_query_building', $q, $query);

        // Loop all query arguments
        foreach ($q as $key => $value) {
            if (!$value) {
                continue;
            }
            $f = 'arg'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            $c = get_class();
            if (method_exists($c, $f)) {
                $c::$f($query, $q[$key], $q);
            }
        }

        do_action('esi_after_format_args', $query, $wpQuery);
    }

    public static function argPostStatus(Query $query, $value, &$q)
    {
        if ($value == 'any') {
            $ips = Indexer::getIndexablePostStati();
            $query->where('post_status', $ips);
        } else {
            $query->where('post_status', $value);
        }
    }

    public static function argP(Query $query, $value, &$q)
    {
        $query->where('post_id', $value);
        $query->isSingle = true;
    }

    public static function argPostParent(Query $query, $value, &$q)
    {
        $query->where('post_parent', $value);
    }

    /**
     * Automatic alias for subpost.
     *
     * @param $value
     * @param $q
     */
    public static function argAttachment(Query $query, $value, &$q)
    {
        $q['attachment'] = sanitize_title_for_query(wp_basename($value));
        $q['name']       = $q['attachment'];
        $query->isSingle = true;
        $q['post_type']  = 'attachment';
    }

    /**
     * Automatic alias for subpost_id.
     *
     * @param $value
     * @param $q
     */
    public static function argAttachmentId(Query $query, $value, &$q)
    {
        $query->where('post_id', $value);
        $query->isSingle = true;
        $q['post_type']  = 'attachment';
    }

    public static function argName(Query $query, $value, &$q)
    {
        $query->where('post_name', $value);
        $query->isSingle = true;
    }

    public static function argStatic(Query $query, $value, &$q)
    {
        $query->where('post_type', 'page');
        $query->isSingle = false;
    }

    public static function argPagename(Query $query, $value, &$q)
    {
        $query->where('post_name', $value);
        $query->isSingle = true;
        $q['post_type']  = 'page';
    }

    public static function argPageId(Query $query, $value, &$q)
    {
        $query->where('post_id', $value);
        $query->isSingle = true;
        $q['post_type']  = 'page';
    }

    public static function argSecond(Query $query, $value, &$q)
    {
        $query->where('post_date_object.second', $value);
    }

    public static function argMinute(Query $query, $value, &$q)
    {
        $query->where('post_date_object.minute', $value);
    }

    public static function argHour(Query $query, $value, &$q)
    {
        $query->where('post_date_object.hour', $value);
    }

    public static function argDay(Query $query, $value, &$q)
    {
        $query->where('post_date_object.day', $value);
    }

    public static function argMonthnum(Query $query, $value, &$q)
    {
        $query->where('post_date_object.month', $value);
    }

    public static function argYear(Query $query, $value, &$q)
    {
        $query->where('post_date_object.year', $value);
    }

    public static function argW(Query $query, $value, &$q)
    {
        $query->where('post_date_object.week', $value);
    }

    public static function argM(Query $query, $value, &$q)
    {
        $query->where('post_date_object.m', $value);
    }

    public static function argCategoryName(Query $query, $value, &$q)
    {
        $query->where('terms.category.slug', $value);
    }

    public static function argTag(Query $query, $value, &$q)
    {
        $query->where('terms.post_tag.slug', $value);
    }

    public static function argCat(Query $query, $value, &$q)
    {
        $query->where('terms.category.term_id', $value);
    }

    public static function argTagId(Query $query, $value, &$q)
    {
        $query->where('terms.post_tag.term_id', $value);
    }

    public static function argAuthor(Query $query, $value, &$q)
    {
        $query->where('post_author.id', $value);
    }

    public static function argAuthorName(Query $query, $value, &$q)
    {
        $query->where('post_author.raw', $value);
    }

    public static function argFeed(Query $query, $value, &$q)
    {
        //
    }

    public static function argTb(Query $query, $value, &$q)
    {
        //
    }

    public static function argPaged(Query $query, $value, &$q)
    {
        $query->setFrom(($value - 1) * $q['posts_per_page']);
    }

    public static function argCommentsPopup(Query $query, $value, &$q)
    {
        //
    }

    public static function argMetaKey(Query $query, $value, &$q)
    {
        //
    }

    public static function argMetaValue(Query $query, $value, &$q)
    {
        //
    }

    public static function argPreview(Query $query, $value, &$q)
    {
        //
    }

    public static function argS(Query $query, $value, &$q)
    {
        $query->setQuery([
            'bool' => [
                'should' => [
                    [
                        'multi_match' => [
                            'fields' => apply_filters('esi_search_fields_multi_match', [
                                'post_title^10',
                                'terms.*.name^4',
                                'post_excerpt^2',
                                'post_content',
                            ]),
                            'query' => $value,
                        ],
                    ],
                    [
                        'fuzzy_like_this' => [
                            'fields' => apply_filters('esi_search_fields_fuzzy', [
                                'post_title',
                                'post_excerpt',
                            ]),
                            'like_text'      => $value,
                            'min_similarity' => apply_filters('esi_min_similarity', 0.75),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public static function argSentence(Query $query, $value, &$q)
    {
        //
    }

    public static function argFields(Query $query, $value, &$q)
    {
        //
    }

    public static function argMenuOrder(Query $query, $value, &$q)
    {
        $query->where('menu_order', $value);
    }

    public static function argCategoryIn(Query $query, $value, &$q)
    {
        $query->where('terms.category.term_id', array_values($value));
    }

    public static function argCategoryNotIn(Query $query, $value, &$q)
    {
        $query->whereNot('terms.category.term_id', array_values($value));
    }

    public static function argCategoryAnd(Query $query, $value, &$q)
    {
        $query->where('terms.category.term_id', '=', array_values($value));
    }

    public static function argPostIn(Query $query, $value, &$q)
    {
        $query->where('post_id', array_values($value));
    }

    public static function argPostNotIn(Query $query, $value, &$q)
    {
        $query->whereNot('post_id', array_values($value));
    }

    public static function argTagIn(Query $query, $value, &$q)
    {
        $query->where('terms.post_tag.term_id', array_values($value));
    }

    public static function argTagNotIn(Query $query, $value, &$q)
    {
        $query->whereNot('terms.post_tag.term_id', array_values($value));
    }

    public static function argTagAnd(Query $query, $value, &$q)
    {
        $query->where('terms.post_tag.term_id', '=', array_values($value));
    }

    public static function argTagSlugIn(Query $query, $value, &$q)
    {
        $query->where('terms.post_tag.slug', array_values($value));
    }

    public static function argTagSlugAnd(Query $query, $value, &$q)
    {
        $query->where('terms.post_tag.slug', '=', array_values($value));
    }

    public static function argPostParentIn(Query $query, $value, &$q)
    {
        $query->where('post_parent', array_values($value));
    }

    public static function argPostParentNotIn(Query $query, $value, &$q)
    {
        $query->whereNot('post_parent', array_values($value));
    }

    public static function argAuthorIn(Query $query, $value, &$q)
    {
        $query->where('post_author.id', array_values($value));
    }

    public static function argAuthorNotIn(Query $query, $value, &$q)
    {
        $query->whereNot('post_author.id', array_values($value));
    }

    public static function argCacheResults(Query $query, $value, &$q)
    {
        //
    }

    public static function argIgnoreStickyPosts(Query $query, $value, &$q)
    {
        //
    }

    public static function argSuppressFilters(Query $query, $value, &$q)
    {
        //
    }

    public static function argUpdatePostTermCache(Query $query, $value, &$q)
    {
        $query->updatePostTermCache = (bool) $value;
    }

    public static function argUpdatePostMetaCache(Query $query, $value, &$q)
    {
        $query->updatePostMetaCache = (bool) $value;
    }

    public static function argPostType(Query $query, $value, &$q)
    {
        if ($value == 'any' || isset($value[0]) && $value[0] == 'any') {
            $pt = Indexer::getSearchablePostTypes();
            $query->where('post_type', array_values($pt));
        } else {
            $query->where('post_type', $value);
        }
    }

    public static function argPostsPerPage(Query $query, $value, &$q)
    {
        if ($query->isSingle) {
            $query->setSize(1);
        } elseif ($value == '-1') {
            $query->setSize(10000000);
        } else {
            $query->setSize((int) $value);
        }
    }

    public static function argNopaging(Query $query, $value, &$q)
    {
        $query->setSize(10000000);
    }

    public static function argCommentsPerPage(Query $query, $value, &$q)
    {
        //
    }

    public static function argNoFoundRows(Query $query, $value, &$q)
    {
        //
    }

    public static function argOrderby(Query $query, $value, &$q)
    {
        if ($query->isSingle) {
            return;
        }

        $o = !empty($q['order']) ? strtolower($q['order']) : 'desc';
        foreach (explode(' ', $value) as $key) {
            $key = str_replace('wp_posts.', '', $key);
            switch ($key) {
                case 'ID':
                    $query->addSort('post_id', $o);
                    break;

                case 'post_author':
                case 'author':
                    $query->addSort('post_author.id', $o);
                    break;

                case 'post_title':
                case 'title':
                    $query->addSort('post_title.raw', $o);
                    break;

                case 'post_name':
                case 'post_type':
                case 'post_date':
                case 'post_modified':
                case 'post_parent':
                case 'post_comment_count':
                case 'menu_order':
                    $query->addSort($key, $o);
                    break;

                case 'rand':
                    // not supported
                    break;

                case 'meta_value_num':
                    $query->addSort('post_meta_num.'.$q['meta_key'], $o);
                    break;

                case 'meta_value':
                    $query->addSort('post_meta.'.$q['meta_key'], $o);
                    break;

                case 'relevance':
                    $query->addSort([
                        '_score'     => 'desc',
                        'menu_order' => 'asc',
                        'post_title' => 'asc',
                    ]);
                    break;

                case 'none':
                case '':
                    $query->addSort('post_date', 'desc');
                    break;

                default:
                    $query->addSort('post_'.$key, $o);
                    break;
            }
        }
    }

    public static function argTaxonomy(Query $query, $value, &$q)
    {
        if (!empty($q['tax_query'])) {
            return;
        }
        if (!empty($q['term'])) {
            $query->should([
                    'terms.'.$value.'.all_slugs' => $q['term'],
                ]
            );
        } elseif (!empty($q['term_id'])) {
            $query->should([
                    'terms.'.$value.'.term_id' => $q['term_id'],
                    'terms.'.$value.'.parent'  => $q['term_id'],
                ]
            );
        }
    }

    public static function argTaxQuery(Query $query, $value, &$q)
    {
        $currentValue = $value;
        $function     = function (Query $query) use (&$currentValue, &$function) {
            foreach ($currentValue as $key => $tax) {
                if ($key === 'relation') {
                    continue;
                }
                if (!isset($tax[0]) || !is_array($tax[0])) {
                    // not nested
                    $include_children = !isset($tax['include_children']) || $tax['include_children'] != false;
                    $compare          = empty($tax['operator']) ? 'in' : $tax['operator'];
                    $terms            = $tax['terms'];
                    if (is_string($terms)) {
                        if (strpos($terms, '+') !== false) {
                            $terms = preg_split('/[+]+/', $terms);
                        } else {
                            $terms = preg_split('/[,]+/', $terms);
                        }
                    }
                    switch ($tax['field']) {
                        case 'term_id' :
                            $query->where("terms.$tax[taxonomy].term_id", $compare, $terms);
                            if ($include_children) {
                                $query->where("terms.$tax[taxonomy].parent", $compare, $terms);
                            }
                            break;
                        case 'slug' :
                            if ($include_children) {
                                $query->where("terms.$tax[taxonomy].all_slugs", $compare, $terms);
                            } else {
                                $query->where("terms.$tax[taxonomy].slug", $compare, $terms);
                            }
                            break;
                        case 'name' :
                            // plugin exclusive feature.
                            $query->where("terms.$tax[taxonomy].name", $compare, $terms);
                            break;
                    }
                } else {
                    // nested
                    $currentValue = $tax;
                    $query->bool($function, !empty($currentValue['relation']) ? $currentValue['relation'] : 'and');
                }
            }
        };

        $query->bool($function, !empty($value['relation']) ? $value['relation'] : 'and');
    }

    public static function argMetaQuery(Query $query, $value, &$q)
    {
        $query->bool(function (Query $query) use ($value, $q) {
            foreach ($value as $key => $mq) {
                if ($key === 'relation') {
                    continue;
                }
                if (empty($mq['compare']) || $mq['compare'] == '=') {
                    $mq['compare'] = 'in'; // ”=” is handled as ”in” in meta query
                }
                if (!empty($mq['type']) && strtolower($mq['type']) === 'numeric') {
                    $term = 'post_meta_num';
                } else {
                    $term = 'post_meta';
                }
                $query->where($term.'.'.$mq['key'].'.raw', $mq['compare'], $mq['value']);
            }

        }, !empty($value['relation']) ? $value['relation'] : 'and');
    }

    public static function argDateQuery(Query $query, $value, &$q)
    {
        $query->bool(function (Query $query) use ($value, $q) {
            foreach ($value as $dq) {
                $column    = !empty($dq['column']) ? $dq['column'] : 'post_date';
                $inclusive = !empty($dq['inclusive']);
                foreach ($dq as $key => $value) {
                    switch ($key) {
                        case 'before':
                            $date       = static::buildDatetime($value, $inclusive);
                            $comparator = 'lt';
                            if ($inclusive) {
                                $comparator .= 'e';
                            }
                            $query->where($column, $comparator, $date);
                            break;
                        case 'after':
                            $date       = static::buildDatetime($value, !$inclusive);
                            $comparator = 'gt';
                            if ($inclusive) {
                                $comparator .= 'e';
                            }
                            $query->where($column, $comparator, $date);
                            break;
                        case 'week' :
                        case 'w' :
                            $query->where($column.'_object.week', $value);
                            break;
                        case 'year' :
                        case 'month' :
                        case 'dayofyear' :
                        case 'day' :
                        case 'dayofweek' :
                        case 'dayofweek_iso' :
                        case 'hour' :
                        case 'minute' :
                        case 'second' :
                            $query->where($column.'_object.'.$key, $value);
                            break;
                    }
                }
            }
        }, !empty($value['relation']) ? $value['relation'] : 'and');
    }

    public static function buildDatetime($date, $inclusive = false)
    {
        $wpDateQuery = new WP_Date_Query([]);

        return $wpDateQuery->build_mysql_datetime($date, $inclusive);
    }
}
