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
use WP_Date_Query;
use Wp_Query;

/**
 * Trait WpConverterTrait.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
trait WpConverterTrait
{
    use BuilderTrait;

    protected $isSingle = false;

    public static function isCompatible(Wp_Query $wpQuery)
    {
        $q                    = $wpQuery->query_vars;
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

    public function formatArgs($wpQuery)
    {
        // Fill again in case pre_get_posts unset some vars.
        $q = $wpQuery->fill_query_vars($wpQuery->query_vars);

        $q = apply_filters('esi_before_format_args', $q, $this);

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

        $q = apply_filters('esi_before_query_building', $q, $this);

        // Loop all query arguments
        foreach ($q as $key => $value) {
            if (!$value) {
                continue;
            }
            $f = 'arg'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $f)) {
                $this->$f($q[$key], $q);
            }
        }

        do_action('esi_after_format_args', $this);

        return $this;
    }

    public function argPostStatus($value, &$q)
    {
        if ($value == 'any') {
            $ips = Indexer::getIndexablePostStati();
            $this->where('post_status', $ips);
        } else {
            $this->where('post_status', $value);
        }
    }

    public function argP($value, &$q)
    {
        $this->where('post_id', $value);
        $this->isSingle = true;
    }

    public function argPostParent($value, &$q)
    {
        $this->where('post_parent', $value);
    }

    /**
     * Automatic alias for subpost.
     *
     * @param $value
     * @param $q
     */
    public function argAttachment($value, &$q)
    {
        $q['attachment'] = sanitize_title_for_query(wp_basename($value));
        $q['name']       = $q['attachment'];
        $this->isSingle  = true;
        $q['post_type']  = 'attachment';
    }

    /**
     * Automatic alias for subpost_id.
     *
     * @param $value
     * @param $q
     */
    public function argAttachmentId($value, &$q)
    {
        $this->where('post_id', $value);
        $this->isSingle = true;
        $q['post_type'] = 'attachment';
    }

    public function argName($value, &$q)
    {
        $this->where('post_name', $value);
        $this->isSingle = true;
    }

    public function argStatic($value, &$q)
    {
        $this->where('post_type', 'page');
        $this->isSingle = false;
    }

    public function argPagename($value, &$q)
    {
        $this->where('post_name', $value);
        $this->isSingle = true;
        $q['post_type'] = 'page';
    }

    public function argPageId($value, &$q)
    {
        $this->where('post_id', $value);
        $this->isSingle = true;
        $q['post_type'] = 'page';
    }

    public function argSecond($value, &$q)
    {
        $this->where('post_date_object.second', $value);
    }

    public function argMinute($value, &$q)
    {
        $this->where('post_date_object.minute', $value);
    }

    public function argHour($value, &$q)
    {
        $this->where('post_date_object.hour', $value);
    }

    public function argDay($value, &$q)
    {
        $this->where('post_date_object.day', $value);
    }

    public function argMonthnum($value, &$q)
    {
        $this->where('post_date_object.month', $value);
    }

    public function argYear($value, &$q)
    {
        $this->where('post_date_object.year', $value);
    }

    public function argW($value, &$q)
    {
        $this->where('post_date_object.week', $value);
    }

    public function argM($value, &$q)
    {
        $this->where('post_date_object.m', $value);
    }

    public function argCategoryName($value, &$q)
    {
        $this->where('terms.category.slug', $value);
    }

    public function argTag($value, &$q)
    {
        $this->where('terms.post_tag.slug', $value);
    }

    public function argCat($value, &$q)
    {
        $this->where('terms.category.term_id', $value);
    }

    public function argTagId($value, &$q)
    {
        $this->where('terms.post_tag.term_id', $value);
    }

    public function argAuthor($value, &$q)
    {
        $this->where('post_author.id', $value);
    }

    public function argAuthorName($value, &$q)
    {
        $this->where('post_author.raw', $value);
    }

    public function argFeed($value, &$q)
    {
        //
    }

    public function argTb($value, &$q)
    {
        //
    }

    public function argPaged($value, &$q)
    {
        $this->setFrom(($value - 1) * $q['posts_per_page']);
    }

    public function argCommentsPopup($value, &$q)
    {
        //
    }

    public function argMetaKey($value, &$q)
    {
        //
    }

    public function argMetaValue($value, &$q)
    {
        //
    }

    public function argPreview($value, &$q)
    {
        //
    }

    public function argS($value, &$q)
    {
        $this->args['query'] = [
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
        ];
    }

    public function argSentence($value, &$q)
    {
        //
    }

    public function argFields($value, &$q)
    {
        //
    }

    public function argMenuOrder($value, &$q)
    {
        $this->where('menu_order', $value);
    }

    public function argCategoryIn($value, &$q)
    {
        $this->where('terms.category.term_id', $value);
    }

    public function argCategoryNotIn($value, &$q)
    {
        $this->whereNot('terms.category.term_id', $value);
    }

    public function argCategoryAnd($value, &$q)
    {
        $this->where('terms.category.term_id', '=', $value);
    }

    public function argPostIn($value, &$q)
    {
        $this->where('post_id', $value);
    }

    public function argPostNotIn($value, &$q)
    {
        $this->whereNot('post_id', $value);
    }

    public function argTagIn($value, &$q)
    {
        $this->where('terms.post_tag.term_id', $value);
    }

    public function argTagNotIn($value, &$q)
    {
        $this->whereNot('terms.post_tag.term_id', $value);
    }

    public function argTagAnd($value, &$q)
    {
        $this->where('terms.post_tag.term_id', '=', $value);
    }

    public function argTagSlugIn($value, &$q)
    {
        $this->where('terms.post_tag.slug', $value);
    }

    public function argTagSlugAnd($value, &$q)
    {
        $this->where('terms.post_tag.slug', '=', $value);
    }

    public function argPostParentIn($value, &$q)
    {
        $this->where('post_parent', $value);
    }

    public function argPostParentNotIn($value, &$q)
    {
        $this->whereNot('post_parent', $value);
    }

    public function argAuthorIn($value, &$q)
    {
        $this->where('post_author.id', $value);
    }

    public function argAuthorNotIn($value, &$q)
    {
        $this->whereNot('post_author.id', $value);
    }

    public function argCacheResults($value, &$q)
    {
        //
    }

    public function argIgnoreStickyPosts($value, &$q)
    {
        //
    }

    public function argSuppressFilters($value, &$q)
    {
        //
    }

    public function argUpdatePostTermCache($value, &$q)
    {
        $this->updatePostTermCache = (bool) $value;
    }

    public function argUpdatePostMetaCache($value, &$q)
    {
        $this->updatePostMetaCache = (bool) $value;
    }

    public function argPostType($value, &$q)
    {
        if ($value == 'any' || isset($value[0]) && $value[0] == 'any') {
            $pt = get_post_types(['exclude_from_search' => false]);
            $this->where('post_type', array_values($pt));
        } else {
            $this->where('post_type', $value);
        }
    }

    public function argPostsPerPage($value, &$q)
    {
        if ($this->isSingle) {
            $this->setSize(1);
        } elseif ($value == '-1') {
            $this->setSize(10000000);
        } else {
            $this->setSize((int) $value);
        }
    }

    public function argNopaging($value, &$q)
    {
        $this->setSize(10000000);
    }

    public function argCommentsPerPage($value, &$q)
    {
        //
    }

    public function argNoFoundRows($value, &$q)
    {
        //
    }

    public function argOrderby($value, &$q)
    {
        if ($this->isSingle) {
            return;
        }

        $o = strtolower($q['order']);
        foreach (explode(' ', $value) as $key) {
            $key = str_replace('wp_posts.', '', $key);
            switch ($key) {
                case 'ID':
                    $this->addSort('post_id', $o);
                    break;

                case 'post_author':
                case 'author':
                    $this->addSort('post_author.id', $o);
                    break;

                case 'post_title':
                case 'title':
                    $this->addSort('post_title.raw', $o);
                    break;

                case 'post_name':
                case 'post_type':
                case 'post_date':
                case 'post_modified':
                case 'post_parent':
                case 'post_comment_count':
                case 'menu_order':
                    $this->addSort($key, $o);
                    break;

                case 'rand':
                    // not supported
                    break;

                case 'meta_value_num':
                    $this->addSort('post_meta_num.'.$q['meta_key'], $o);
                    break;

                case 'meta_value':
                    $this->addSort('post_meta.'.$q['meta_key'], $o);
                    break;

                case 'relevance':
                    $this->addSort([
                        '_score'     => 'desc',
                        'menu_order' => 'asc',
                        'post_title' => 'asc',
                    ]);
                    break;

                case 'none':
                case '':
                    $this->addSort('post_date', 'desc');
                    break;

                default:
                    $this->addSort('post_'.$key, $o);
                    break;
            }
        }
    }

    public function argTaxonomy($value, &$q)
    {
        if (!empty($q['term'])) {
            $this->should([
                    'terms.'.$value.'.all_slugs' => $q['term'],
                ]
            );
        } elseif (!empty($q['term_id'])) {
            $this->should([
                    'terms.'.$value.'.term_id' => $q['term_id'],
                    'terms.'.$value.'.parent'  => $q['term_id'],
                ]
            );
        }
    }

    public function argTaxQuery($value, &$q)
    {
        $terms = [];
        foreach ($value as $tax) {
            if ($tax['field'] == 'id') {
                $tax['field'] = 'term_id';
            }
            $terms["terms.$tax[taxonomy].$tax[field]"] = $tax['terms'];
        }
        if (isset($value['relation']) && $value['relation'] == 'OR') {
            $this->should($terms);
        } else {
            $this->must($terms);
        }
    }

    public function argMetaQuery($value, &$q)
    {
        $this->bool(function ($filter) use ($value, $q) {
            foreach ($value as $key => $mq) {
                if ($key === 'relation') {
                    continue;
                }
                if (empty($mq['compare']) || $mq['compare'] == '=') {
                    $mq['compare'] = 'in'; // ”=” is handled as ”in” in meta query
                }
                $filter->where('post_meta.'.$mq['key'].'.raw', $mq['compare'], $mq['value']);
            }

        }, !empty($value['relation']) ? $value['relation'] : 'and');
    }

    public function argDateQuery($value, &$q)
    {
        $this->bool(function ($filter) use ($value, $q) {
            foreach ($value as $dq) {
                $column = !empty($dq['column']) ? $dq['column'] : 'post_date';
                $inclusive = !empty($dq['inclusive']);
                foreach ($dq as $key => $value) {
                    switch ($key) {
                        case 'before':
                            $date = static::buildDatetime($value, $inclusive);
                            $comparator = 'lt';
                            if ($inclusive) {
                                $comparator .= 'e';
                            }
                            $filter->where($column, $comparator, $date);
                            break;
                        case 'after':
                            $date = static::buildDatetime($value, !$inclusive);
                            $comparator = 'gt';
                            if ($inclusive) {
                                $comparator .= 'e';
                            }
                            $filter->where($column, $comparator, $date);
                            break;
                        case 'week' :
                        case 'w' :
                            $this->where($column.'_object.week', $value);
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
                            $this->where($column.'_object.'.$key, $value);
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
