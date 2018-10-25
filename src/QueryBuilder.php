<?php
namespace KevinYan\Elastic;

class QueryBuilder
{
    /**
     * select 字段列表
     * @var array
     */
    protected $selectedFields = [];
    /**
     * 区间范围条件
     * @var array
     */
    protected $range;

    /**
     * term条件
     * @var array
     */
    protected $terms;

    /**
     * 排序规则
     * @var array
     */
    protected $orders;

    /**
     * 结果集起始文档距搜索结果首个文档的距离
     * @var string
     */
    protected $from;

    /**
     * 返回搜素结果文档的数目
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
     * 为搜索设置时间范围条件
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
     * 为搜索添加term条件
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
     * 设置搜索结果的排序方式
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
     * 设置搜索按照时间降序返回文档
     * @return $this
     */
    public function newest()
    {
        $this->order('@timestamp', 'desc');
        return $this;
    }

    /**
     * 设置搜索按照时间升序返回文档
     */
    public function oldest()
    {
        $this->order('@timestamp', 'asc');
        return $this;
    }

    /**
     * 设置搜索结果_source里只包含给定的字段
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
     * 清除selectedFields里的值
     */
    protected function clearSelect()
    {
        $this->selectedFields = [];
    }

    /**
     * 设置返回的第一条文档距离结果集首个文档的距离(文档数)
     * @param $offset
     * @return $this
     */
    public function from($offset)
    {
        $this->from = $offset;
        return $this;
    }

    /**
     * 设置要返回的文档数目
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
     * 组成搜索条件的Query
     * Query示例:
     *
     * [
     *     'index' => 'logstash-bussiness_weixin_bridge_page*',
     *     'type'  => 'bussiness_weixin_bridge_page',
     *     'scroll'=> '30s',//scroll镜像生存时间维持30秒, 在调用每次进行scroll搜索时会重新设置镜像的生存时间,
     *     'size'  => '50',//result size for per shard scroll created
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
     * 搜索并返回文档
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
     * 返回匹配搜索条件的文档数
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
     * 遍历搜索结果
     * 分页遍历搜索结果请不要循环用from和size来取数据, 应该用更高效的scroll api, 更多信息查看下面的文档
     * @documentaion https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_search_operations.html#_scrolling
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