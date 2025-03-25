# Convert sport activity

This library can to read ZIP-archive of sport activities

1. Strava
2. Adidas running


## Installation

Via composer

```bash
composer require gracerpro/convert-sport-activity
```

## Documentation

For **Strava** service

```php
use Gracerpro\ConvertSportActivity\Strava\StravaArchive;
use Gracerpro\ConvertSportActivity\Strava\StravaObserver;
use Gracerpro\ConvertSportActivity\Strava\Activity;
use Gracerpro\ConvertSportActivity\GpsPoint;

class EchoStravaObserver implements StravaObserver
{
    /**
     * @param GpsPoint[] $points
     */
    public function onNewActivity(Activity $activity, array $points, int $index)
    {
        echo $index . " " . $activity->id . "\n";
    }
}

$archive = new StravaArchive();
$observer = new EchoStravaObserver();

$archive->convert($zipFilePath, $observer);
```

For **Adidas running** service

```php
use Gracerpro\ConvertSportActivity\Adidas\AdidasArchive;
use Gracerpro\ConvertSportActivity\Adidas\AdidasObserver;
use Gracerpro\ConvertSportActivity\Adidas\Activity;
use Gracerpro\ConvertSportActivity\GpsPoint;

class FileAdidasObserver implements AdidasObserver
{
    public function __construct(
        /** @var resource */
        private $stream,
    ){
    }

    /**
     * @param GpsPoint[] $points
     */
    public function onNewActivity(Activity $activity, array $points, int $index)
    {
        $text = $index . " " . $activity->id . "\n";
        fwrite($this->stream, $text . "\n");
    }
}

$stream = fopen("...");

$archive = new AdidasArchive();
$observer = new FileAdidasObserver($stream);

$archive->convert($zipFilePath, $observer);

fclose($stream);
```


## Development

### Docker

Use *docker* and log in to container

```bash
docker exec -it --user "$(id -u):$(id -g)" convert-sport-activity__php-cli bash
```

### php-cs-fixer

Check project

```bash
php ./vendor/bin/php-cs-fixer fix
```

### phpstan

```bash
php ./vendor/bin/phpstan analyse
# or
php -d memory_limit=1G ./vendor/bin/phpstan analyse
```

### phpunit

Run unit tests

```bash
vendor/bin/phpunit tests
```

Show code coverage

```
XDEBUG_MODE=coverage vendor/bin/phpunit tests --coverage-text
```


## License

See [LICENSE](LICENSE).


## TODO

* unit tests
