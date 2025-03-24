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
use Gracerpro\ConvertSportActivity\Strava\StravaExchange;
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

$stravaExchange = new StravaExchange();
$observer = new EchoStravaObserver();

$exchange->convert($zipFilePath, $observer);
```

For **Addidas runing** service

```php
use Gracerpro\ConvertSportActivity\Adidas\AdidasExchange;
use Gracerpro\ConvertSportActivity\Adidas\AdidasObserver;
use Gracerpro\ConvertSportActivity\Adidas\Activity;
use Gracerpro\ConvertSportActivity\GpsPoint;

class FileAdidasObserver implements AdidasObserver
{
    public function __construct(
        /** @var resource */
        private $file,
    ){
    }

    /**
     * @param GpsPoint[] $points
     */
    public function onNewActivity(Activity $activity, array $points, int $index)
    {
        $text = $index . " " . $activity->id . "\n";
        fwrite($this->file, $text . "\n");
    }
}

$stravaExchange = new AdidasExchange();
$observer = new FileAdidasObserver();

$exchange->convert($zipFilePath, $observer);
```


## Development

### Docker

Use docker and log in to container

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

```bash
vendor/bin/phpunit tests
```


## License

See [LICENSE](LICENSE).