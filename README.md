# gotime
Golang-like time class(es) for PHP 7.0+


## Desc

The goal of this lib is to achieve near-enough (as determined by me) api equivalency in PHP to the GoLang Time package,
as basically it is just better than PHP's.

## Limitations

### Precision

PHP sucks at floats.  I recommend you set your precision to ~17.

### Sub-Second Time Calculation

PHP sucks at sub-second time manipulation. I don't really aim to fix this.

## Classes

### Duration

The [Duration](src/Time/Duration.php) class is designed to emulate the golang 
[time.Duration](https://golang.org/src/time/time.go#L620) type.

There are 2 ways to construct a Duration class:

```php
use \DCarbone\Go\Time;

$d = new Time\Duration(5 * Time::Second);
// produces a Duration with an internal value of 5e9;

$d = Time::ParseDuration('5s');
// produces a Duration with an internal value of 5e9;
```

