<?php
namespace KevinYan\Elastic;
use Elasticsearch\Client as EsClient;

class Connection
{
    /**
     * Elasticsearch client instance
     *
     * @var EsClient
     */
    protected $client;

    /**
     * Query builder
     *
     * @var
     */
    protected $queryBuilder;

    /**
     * The elasticsearch connection configuration options.
     *
     * @var array
     */
    protected $config;

    /**
     * Index name for elasticsearch connection
     *
     * @var
     */
    protected $index;

    /**
     * type name for elasticsearch connection
     *
     * @var
     */
    protected $type;

    /**
     * time zone (usually using in range query)
     *
     * @var
     */
    public $timeZone;

    /**
     * Connection constructor.
     *
     * @param EsClient $client
     * @param $config
     */
    public function __construct(EsClient $client, $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Set index name for elastic connection
     *
     * @param $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * set type name
     *
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * get index name of elastic connection
     *
     * @return string
     */
    public function index()
    {
        return $this->index;
    }

    /**
     * get type name of elastic connection
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * get elastic client for this connection
     * @return mixed
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * Get query builder instance.
     *
     * @return QueryBuilder
     */
    public function query()
    {
        if (! ($this->queryBuilder instanceof QueryBuilder)) {
            $this->queryBuilder = new QueryBuilder($this);
        }
        return $this->queryBuilder;
    }

    /**
     * Dynamically pass methods to query builder.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->query()->$method(...$parameters);
    }
}