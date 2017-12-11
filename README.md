# gotime
Golang-like time class(es) for PHP 7.0+


## Desc

The goal of this lib is to achieve near-enough (as determined by me) api equivalency in PHP to the GoLang Time package,
as basically it is just better than PHP's.

## Limitations

PHP does not do super good with sub-second time values, and as a result this lib doesn't either.  It's intended purpose
is more to provide a better / simpler API for some specific use cases.  It does NOT aim to be a replacement for
[DateTime](http://php.net/manual/en/class.datetime.php), nor will I put for the effort to support all of the features
of the [time](https://golang.org/src/time) package.  This is just for my own personal amusement and use. 

## Duration

The [Duration](src/Time/Duration.php) class is designed to emulate the golang 
[time.Duration](https://golang.org/src/time/time.go#L620) type.

There are 2 ways to construct a Duration class:

```php
use \DCarbone\Go\Time;

$d = new Time\Duration(5 * Time::Second);

// TODO: Under construction.
$d = Time::ParseDuration('5s');
```

