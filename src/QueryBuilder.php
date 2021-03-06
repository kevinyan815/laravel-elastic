<?php
namespace KevinYan\Elastic;

class QueryBuilder
{
    /**
     * fields fetched in query
     *
     * @var array
     */
    protected $selectedFields = [];
    /**
     * range condition
     *
     * @var array
     */
    protected $range;

    /**
     * term condition
     *
     * @var array
     */
    protected $terms;

    /**
     * order rules
     *
     * @var array
     */
    protected $orders;

    /**
     * fetched item's offset apart from the first item in the whole result of a search
     *
     * @var string
     */
    protected $from;

    /**
     * result size
     *
     * @var string
     */
    protected $size;

    /**
     * _scroll_id
     *
     * @var string
     */
    protected $scrollId;

    /**
     * result size for per shard scroll created
     *
     * @var int
     */
    protected $scrollSize;

    /**
     * indicates how long Elasticsearch to keep the search context open for another scroll operation
     * Unit: second
     *
     * @var int
     */
    protected $scrollKeepTime;

    /**
     * Elastic connection instance
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Elastic query's body
     *
     * @var array
     */
    protected $queryBody;

    /**
     * QueryBuilder constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * initialize elastic query's body
     *
     * @param void
     */
    public function initQueryBody()
    {
        $this->queryBody = [
            'index' => $this->connection->index(),
            'type'  => $this->connection->type(),
            'body' => [
                'query' => [
                    'bool' => [
                        'filter' => [

                        ]
                    ]
                ]
            ]
        ];
    }


    /**
     * set time range condition for query
     *
     * @param string $start
     * @param string $end
     * @param string $timeField
     * @return $this
     */
    public function setTimeRange($start, $end, $timeField = "@timestamp")
    {
        $this->range = [
            "range" => [
                "$timeField" => [
                    "gte" => $start,
                    "lte" => $end,
                    "format" => "yyyy-MM-dd HH:mm:ss",
                    "time_zone" => $this->connection->timeZone
                ]
            ]
        ];
        return $this;
    }

    /**
     * add term condition for query
     *
     * @param $term
     * @param $value
     * @param string $condition condition type: must or should
     * @return $this
     */
    public function term($term, $value, $condition = 'must')
    {
        $this->terms[$condition][$term] = $value;
        return $this;
    }

    /**
     * specify data will be sorted through field in asc or desc
     *
     * @param $field
     * @param $value
     * @return $this
     */
    public function order($field, $value)
    {
        $this->orders[$field] = $value;
        return $this;
    }

    /**
     * order documents in descending time
     *
     * @param string $field  Field name
     * @return $this
     */
    public function latest($field = '@timestamp')
    {
        $this->order($field, 'desc');
        return $this;
    }

    /**
     * order documents in ascending time
     *
     * @param string $field  Field name
     * @return $this
     */
    public function oldest($field = '@timestamp')
    {
        $this->order($field, 'asc');
        return $this;
    }

    /**
     * set fields include in _source in query result
     * @param array $fields
     * @return $this
     */
    public function select(array $fields = [])
    {
        $this->selectedFields = [];
        $this->selectedFields = array_unique(array_merge($this->selectedFields, $fields));
        return $this;
    }

    /**
     * clear $selectedFields
     */
    protected function clearSelect()
    {
        $this->selectedFields = [];
    }

    /**
     * Set the distance of the first document returned from the first document in the whole query result set
     *
     * @param $offset
     * @return $this
     */
    public function from($offset)
    {
        $this->from = $offset;
        return $this;
    }

    /**
     * set size of return result
     *
     * @param $size
     * @return $this
     */
    public function size($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * set result size for per scroll shard
     *
     * @param $scrollSize
     * @return $this
     */
    public function scrollSize($scrollSize)
    {
        $this->scrollSize = $scrollSize;
        return $this;
    }

    /**
     * compose query based on all options we set before
     * Query Example:
     *
     * [
     *     'index' => 'index name ',
     *     'type'  => 'type name',
     *     'scroll'=> '30s',// how long between scroll requests. should be small!
     *     'size'  => '50', //results size for per shard scroll created
     *     'body'  => [
     *         'query' => [
     *             'bool' => [
     *                 "filter" => [
     *                     [
     *                         "range" => [
     *                             "@timestamp" => [
     *                                 "gte" => "2018-04-03 11:00:00",
     *                                 "lte" => "2018-04-04 21:00:00",
     *                                 "format" => "yyyy-MM-dd HH:mm:ss",
     *                                 "time_zone" => "+08:00"
     *                             ]
     *                        ]
     *                    ],
     *                ],
     *                "must" => [
     *                    "field_name" => "abc"
     *                ]
     *            ]
     *        ],
     *        'sort' => [
     *            [
     *                "@timestamp" => ["order" => "desc"]
     *            ]
     *        ],
     *        "from" => 5,
     *        "size" => 2
     *    ]
     *];
     */
    public function composeQuery()
    {
        $this->initQueryBody();
        if ($this->selectedFields) {
            array_set($this->queryBody, 'body._source', $this->selectedFields);
            $this->clearSelect();
        }
        if ($this->range) {
            $filter = array_get($this->queryBody, 'body.query.bool.filter');
            array_push($filter, $this->range);
            array_set($this->queryBody, 'body.query.bool.filter', $filter);

        }
        if ($this->terms) {
            foreach($this->terms as $condition => $conditionTerms) {
                $$condition = array_get($this->queryBody, "body.query.bool.{$condition}");
                $$condition = $$condition ?: [];
                foreach ($conditionTerms as $key => $value) {
                    array_push($$condition, ["term" => [$key => $value]]);
                }
                array_set($this->queryBody, "body.query.bool.{$condition}", $$condition);
            }

        }
        if ($this->from) {
            array_set($this->queryBody, 'body.from', $this->from);
        }
        if ($this->size) {
            array_set($this->queryBody, 'body.size', $this->size);
        }
        if ($this->scrollSize) {
            array_set($this->queryBody, 'size', $this->scrollSize);
        }
        if ($this->scrollKeepTime) {
            array_set($this->queryBody, 'scroll',  $this->scrollKeepTime);
        }
        if ($this->orders) {
            $orders = [];
            foreach($this->orders as $field => $value) {
                array_push($orders, [$field => ["order" => $value]]);
            }
            array_set($this->queryBody, 'body.sort', $orders);
        }
    }


    /**
     * fetch then return documents
     *
     * @return array
     */
    public function get()
    {
        $this->composeQuery();
        $result = $this->connection->client()->search($this->queryBody);
        $this->scrollId = array_get($result,'_scroll_id');

        return array_get($result, 'hits.hits');
    }

    /**
     * count matching documents
     *
     * @return int
     */
    public function count()
    {
        $this->composeQuery();
        $queryBody = $this->queryBody;
        array_forget($queryBody, 'body.from');
        array_forget($queryBody, 'body.size');
        array_forget($queryBody, 'body.sort');
        array_forget($queryBody, 'body._source');
        array_forget($queryBody, 'scroll');
        $result = $this->connection->client()->count($queryBody);

        return $result['count'];
    }

    /**
     * iterate documents matching search options
     * this is more efficient than specify from and size in query body, more about scroll you can checkout
     * @documentaion https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_search_operations.html#_scrolling
     *
     * @return array
     */
    public function scroll()
    {
        $response = $this->connection->client()->scroll([
            'scroll_id' => $this->scrollId,
            'scroll' => '30s'
        ]);
        $this->scrollId = array_get($response, '_scroll_id');
        return array_get($response, 'hits.hits');
    }

    /**
     * Dump the raw elastic search result and end the script
     *
     * @param void
     * @return void
     */
    public function dump()
    {
        $this->composeQuery();
        $result = $this->connection->client()->search($this->queryBody);

        dd($result);
    }

    /**
     * indicates how long Elasticsearch to keep the search context open for another scroll operation
     * Unit: second
     *
     * @param $keepTime
     */
    public function setScrollKeepTime($keepTime)
    {
        $this->scrollKeepTime = $keepTime;
    }
}