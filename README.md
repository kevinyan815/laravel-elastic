# laravel-elastic

Elasticsearch Fluent interface for Laravel5

Now just have a very rough version impelements elasticsearch's basic search functionality.

### Installation

1. get package from packagist

```
composer requrire kevinyan/laravel-elastic 0.0.1
```

2. register service provider and facade in 'app.php'

```
'providers' => [
    ...
    KevinYan\Elastic\Providers\ElasticServiceProvider::class,

]

'aliases' => [
    ...
    'Elastic'   => KevinYan\Elastic\Facades\Elastic::class
]

```


3. publish elasitc.php into project's config directory

```
php artisan vendor:publish --provider="KevinYan\Elastic\Providers\ElasticServiceProvider"
```
