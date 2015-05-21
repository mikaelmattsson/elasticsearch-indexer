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

/**
 * Querybuilder for Elasticsearch.
 *
 * @author Mikael Mattsson <mikael@wallmanderco.se>
 */
trait BuilderTrait
{
    protected $args = [];

    protected $filterBuildingPoint = null;

    protected function builderConstruct()
    {
        $this->args['filter']['and'] = [];
        $this->filterBuildingPoint   = &$this->args['filter']['and'];
    }

    public function setQuery(array $query)
    {
        $this->args['query'] = $query;

        return $this;
    }

    public function setSort($field, $order = 'asc')
    {
        $this->args['sort'] = [];
        $this->addSort($field, $order);

        return $this;
    }

    public function addSort($field, $order = 'asc')
    {
        if (!isset($this->args['sort'])) {
            $this->args['sort'] = [];
        }
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->args['sort'][] = [$key => ['order' => $value]];
            }
        } else {
            $this->args['sort'][] = [$field => ['order' => $order]];
        }

        return $this;
    }

    public function bool($callable, $relation = 'must')
    {
        $tmp      = &$this->filterBuildingPoint;
        $relation = strtolower($relation);
        if ($relation == 'and') { //alias
            $relation = 'must';
        }
        if ($relation == 'or') { //alias
            $relation = 'should';
        }
        $this->filterBuildingPoint = &$this->filterBuildingPoint[]['bool'][$relation];
        $this->filterBuildingPoint = [];
        $callable($this);
        $this->filterBuildingPoint = &$tmp;

        return $this;
    }

    /**
     * examples: where(['age' => 20, 'name' => 'John'])
     *           where('age', 20)
     *           where('age', [20, 21]) //warning: handled as ”in”
     *           where('age', 'in', [20, 21]) // 20 or 21
     *           where('age', '=', [20, 21]) // exact match
     *           where('age', '==', [20, 21]) // same as above
     *           where('age', '!=', 20)
     *           where('age', 'exists', true) //warning: arg3 is needed or else it will check if age = 'exists'.
     *
     * @param string|array      $arg1
     * @param string|array|null $arg2
     * @param string|array|null $arg3
     * @param bool              $not
     *
     * @return $this
     */
    public function where($arg1, $arg2 = null, $arg3 = null, $not = false)
    {
        if (is_array($arg1)) {
            $this->filterBuildingPoint[] = [
                'terms' => $arg1,
            ];

            return $this;
        }

        if ($arg3 === null) {
            $arg3 = $arg2;
            $arg2 = 'in';
        }

        $must = $not ? 'must_not' : 'must';

        switch (strtolower($arg2)) {
            case '!=': // used by meta_query
                $must = 'must_not';
            case '=': // used by meta_query
            case '==':
                $this->filterBuildingPoint[]['bool'][$must] = [
                    'term' => [$arg1 => $arg3],
                ];
                break;

            case 'in':
                $this->filterBuildingPoint[]['bool'][$must] = [
                    is_array($arg3) ? 'terms' : 'term' => [$arg1 => $arg3],
                ];
                break;

            case 'not exists': // used by meta_query
                $must = 'must_not';
            case 'exists': // used by meta_query
            case 'has':
                $this->filterBuildingPoint[]['bool'][$must]['exists'] = [
                    'field' => $arg1,
                ];
                break;

            case '>=': // used by meta_query
            case 'gte':
                $this->filterBuildingPoint[]['bool'][$must]['range'] = [
                    $arg1 => ['gte' => $arg3],
                ];
                break;

            case '<=': // used by meta_query
            case 'lte':
                $this->filterBuildingPoint[]['bool'][$must]['range'] = [
                    $arg1 => ['lte' => $arg3],
                ];
                break;

            case '>': // used by meta_query
            case 'gt':
                $this->filterBuildingPoint[]['bool'][$must]['range'] = [
                    $arg1 => ['gt' => $arg3],
                ];
                break;

            case '<': // used by meta_query
            case 'lt':
                $this->filterBuildingPoint[]['bool'][$must]['range'] = [
                    $arg1 => ['lt' => $arg3],
                ];
                break;

        }

        return $this;
    }

    public function whereNot($arg1, $arg2 = null, $arg3 = null)
    {
        $this->where($arg1, $arg2, $arg3, 1);

        return $this;
    }

    public function should($input)
    {
        $should = [];
        foreach ($input as $key => $value) {
            $should[] = [
                is_array($value) ? 'terms' : 'term' => [$key => $value],
            ];
        }
        $this->filterBuildingPoint[] = [
            'bool' => ['should' => $should],
        ];

        return $this;
    }

    public function must($input)
    {
        $must = [];
        foreach ($input as $key => $value) {
            $must[] = [
                is_array($value) ? 'terms' : 'term' => [$key => $value],
            ];
        }
        $this->filterBuildingPoint[] = [
            'bool' => ['must' => $must],
        ];

        return $this;
    }

    public function setFrom($from)
    {
        $this->args['from'] = max($from, 0);

        return $this;
    }

    public function setSize($size)
    {
        $this->args['size'] = max($size, 0);

        return $this;
    }

    public function getArgs()
    {
        return $this->args;
    }
}
