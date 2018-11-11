# laravel-elastic

Elasticsearch Fluent interface for Laravel5

Now just have a very rough version impelements elasticsearch's basic search functionality.

## Installation

- get package from packagist

```
composer requrire kevinyan/laravel-elastic
```

- register service provider and facade in `config/app.php` （In laravel version 5.5 or higher, you can skip this step)

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


- publish configuration file into project's config directory

```
php artisan vendor:publish --provider="KevinYan\Elastic\Providers\ElasticServiceProvider"
```

## Configuration

Our configuration file is well documented, but there's one thing to emphasize.
Since in elasticsearch version 6 type was removed, if your elasticsearch server is higher than 6,
be sure not to set `type` config option in configuration file.
  

## Usage

```
// resolve elastic service from IocContainer, this will connect to the default elastic connection
$elastic = app()->make('elastic')
// connection to an specific connection
$elastic = app()->make('elastic')->connection('connection name');

// we have three different types of  term : must, should and filter, default term type is must.
// search using a must term
$elastic->select(['title', 'author', 'published_at'])->term('author', 'kevin')
        ->setTimeRange('2016-01-02 12:00:00', '2016-06-05 16:23:00)->latest()
        ->get();
// search using a must term and a should term
$elastic->select(['title', 'author', 'published_at'])->term('author', 'kevin')->term('category', '10010', 'should')
        ->setTimeRange('2016-01-02 12:00:00', '2016-06-05 16:23:00)->latest()
        ->get();
                 
        
// scrolling
# The Scrolling functionality of Elasticsearch is used to paginate over many documents in a bulk manner, 
# such as exporting all the documents belonging to a single user. 
# It is more efficient than regular from + size search because it doesn’t need to maintain an expensive 
# priority queue ordering the documents.

$result = $elastic->select(['title', 'author'])->term('author', 'kevin')->get();

//this package will automatically maintain scroll_id which is used to continue paginating through the hits
while($result && count($result) > 0) {
    //loop until the scroll "cursors" are exhausted
    $result = $elastic->srcoll();
    ...
}


```

Sometime you may need to find out what elasticsearch returns for your search action. You can simply dump
 using `dump` method, this method will dump all raw returns from elasticsearch to output(browser or standard output) and then end the script :
```
$result = $elastic->select(['title', 'author'])->term(['author', 'kevin'])->dump();
```

### Use Facade

The package comes with a facade,if you prefer the static method calls over dependency injection.

Replace `app()->make('elastic')` with `Elastic::` in the examples above.

## Contact
Open an issue on GitHub if you have any problems or suggestions.

## License
The contents of this repository is released under the MIT license.
