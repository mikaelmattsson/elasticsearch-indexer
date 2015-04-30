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
use Wp_Query;

/**
 * Trait WpConverterTrait
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

        //dd($q);

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
        if ($wpQuery->is_search() && $q['orderby'] == 'menu_order title') {
            $q['orderby'] = 'relevance';
        } elseif (empty($q['orderby'])) {
            $q['orderby'] = 'none';
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
            $f = 'arg' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $f)) {
                $this->$f($q[$key], $q);
            }
        }

        //jd($this->args);
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

    public function argM($value, &$q)
    {
        //
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
     * Automatic alias for subpost
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
     * Automatic alias for subpost_id
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
        //
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
        //
    }

    public function argMinute($value, &$q)
    {
        //
    }

    public function argHour($value, &$q)
    {
        //
    }

    public function argDay($value, &$q)
    {
        //
    }

    public function argMonthnum($value, &$q)
    {
        //
    }

    public function argYear($value, &$q)
    {
        //
    }

    public function argW($value, &$q)
    {
        //
    }

    public function argCategoryName($value, &$q)
    {
        //
    }

    public function argTag($value, &$q)
    {
        //
    }

    public function argCat($value, &$q)
    {
        //
    }

    public function argTagId($value, &$q)
    {
        //
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
                            'like_text' => $value,
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
        //
    }

    public function argCategoryIn($value, &$q)
    {
        // note:  $value is an array
    }

    public function argCategoryNotIn($value, &$q)
    {
        // note:  $value is an array
    }

    public function argCategoryAnd($value, &$q)
    {
        // note:  $value is an array
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
        // note:  $value is an array
    }

    public function argTagNotIn($value, &$q)
    {
        // note:  $value is an array
    }

    public function argTagAnd($value, &$q)
    {
        // note:  $value is an array
    }

    public function argTagSlugIn($value, &$q)
    {
        // note:  $value is an array
    }

    public function argTagSlugAnd($value, &$q)
    {
        // note:  $value is an array
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
                    $this->addSort('post_meta_num.' . $q['meta_key'], $o);
                    break;

                case 'meta_value':
                    $this->addSort('post_meta.' . $q['meta_key'], $o);
                    break;

                case 'relevance':
                    $this->addSort([
                        '_score' => 'desc',
                        'menu_order' => 'asc',
                        'post_title' => 'asc',
                    ]);
                    break;

                case 'none':
                case '':
                    $this->addSort('post_date', 'desc');
                    break;

                default:
                    $this->addSort('post_' . $key, $o);
                    break;
            }
        }
    }

    public function argTaxonomy($value, &$q)
    {
        if (!empty($q['term'])) {
            $this->should([
                    'terms.' . $value . '.all_slugs' => $q['term']
                ]
            );
        } elseif (!empty($q['term_id'])) {
            $this->should([
                    'terms.' . $value . '.term_id' => $q['term_id'],
                    'terms.' . $value . '.parent' => $q['term_id']
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
        if (empty($value['relation'])) {
            $value['relation'] = 'and';
        }
        $this->bool(function ($filter) use ($value, $q) {

            foreach ($value as $key => $mq) {
                if ($key === 'relation') {
                    continue;
                }
                if (empty($mq['compare']) || $mq['compare'] == '=') {
                    $mq['compare'] = 'in'; // ”=” is handeled as ”in” in meta query
                }
                $filter->where('post_meta.' . $mq['key'] . '.raw', $mq['compare'], $mq['value']);
            }

        }, $value['relation']);
    }
}
