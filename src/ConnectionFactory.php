<?php

namespace KevinYan\Elastic;
use Elasticsearch\ClientBuilder;

class ConnectionFactory
{
    protected $clientBuilder;

    public function __construct(ClientBuilder $clientBuilder)
    {
        $this->clientBuilder = $clientBuilder;
    }

    public function make(array $config)
    {
        return $this->createConnection($config);
    }

    protected function createConnection(array $config)
    {
        $host = array_only($config, 'host', 'port', 'scheme', 'user', 'pass');
        $esClient = $this->clientBuilder->setHosts([$host])->build();
        $connection = new Connection($esClient, $config);
        // here we will assign index and type specified in config
        // but if you use different index and type, you can use index
        // and type method defined in connection class to set them.
        $connection->setIndex($config['index']);
        $connection->setType($config['type']);
        $connection->timeZone = $config['time_zone'];

        return $connection;
    }
}