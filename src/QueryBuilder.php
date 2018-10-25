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
            'scroll' => '30s',
            'size' => 50,
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
     * @param $start
     * @param $end
     * @return $this
     */
    public function setTimeRange($start, $end)
    {
        $this->range = [
            "range" => [
                "@timestamp" => [
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
     * @param $term
     * @param $value
     * @return $this
     */
    public function term($term, $value)
    {
        $this->terms[$term] = $value;
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
     * @return $this
     */
    public function latest()
    {
        $this->order('@timestamp', 'desc');
        return $this;
    }

    /**
     * order documents in ascending time
     *
     * @return $this
     */
    public function oldest()
    {
        $this->order('@timestamp', 'asc');
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
     *                    [
     *                        "term" => ["material_id" => 268]
     *                    ],
     *                    [
     *                        "term" => ["material_type" => 1]
     *                    ]
     *                ],
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
            $filter = array_get($this->queryBody, 'body.query.bool.filter');
            foreach($this->terms as $key => $value) {
                array_push($filter, ["term" => [$key => $value]]);
            }
            array_set($this->queryBody, 'body.query.bool.filter', $filter);
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
}