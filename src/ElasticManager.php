<?php

namespace KevinYan\Elastic;

use Illuminate\Support\Arr;

class ElasticManager
{
    /**
     * the elastic connection factory instance
     *
     * @var ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];


    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->factory = $connectionFactory;
    }

    /**
     * Get a elasticsearch connection instance.
     *
     * @param  string  $name
     * @return \KevinYan\Elastic\Connection
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }


    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return config('elastic.default');
    }

    /**
     * Get the configuration for a elastic connection.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        $connections = config('elastic.connections');

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Elastic connect configuration for connection [$name] not configured.");
        }

        return $config;
    }

    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return \KevinYan\Elastic\Connection
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);

        return $this->factory->make($config, $name);
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}