# gotime
Golang-like time class(es) for PHP 7.0+

[![Build Status](https://travis-ci.org/dcarbone/gotime.svg?branch=master)](https://travis-ci.org/dcarbone/gotime)

## Desc

The goal of this lib is to achieve near-enough (as determined by me) api equivalency in PHP to the GoLang Time package,
as basically it is just better than PHP's.

## Limitations 

### Sub-Second Time Calculation

While PHP 7.0+ support creating a \DateTime object with sub-second precision down to microseconds, only PHP 7.1+ support
incrementing / decrementing microseconds via the `\DateTime::add` and `\DateTime::sub` methods.  If you use
this library with 7.0, you will NOT be able to manipulate the sub-second values of the [Time\Time](./src/Time/Time.php)
class after construction!

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

Internally the "duration" is represented as an integer, allow for much fun.

#### Serialization
Assuming `$dt = new Time\Duration(5 * Time::Second);`:

|Type|Exec|Output|
|----|----|------|
|JSON|`echo json_encode($dt);`|`5000000000`|
|string|`echo (string)$dt;`|`5s`;

#### DateInterval

[DateInterval](http://php.net/manual/en/class.dateinterval.php) pretty much sucks.  I have created my own 
[DateInterval](src/Time/DateInterval.php) and [IntervalSpec](src/Time/IntervalSpec.php) classes to help alleviate this.

These provide [Duration](src/Time/Duration.php) the ability to create an interval for use with the standard 
[DateTime::add](http://php.net/manual/en/datetime.add.php) and 
[DateTime::sub](http://php.net/manual/en/datetime.sub.php) methods as such:

```php
$dt = new \DateTime();
echo "{$dt->format('H:i:s')}\n";

$d = new Time\Duration(5 * Time::Second);
$dt->add($d->DateInterval());
echo "{$dt->format('H:i:s')}\n";

// 16:03:37
// 16:03:42
```

### Time

The [Time](src/Time/Time.php) class is designed to BARELY emulate the golang 
[time.Time](https://golang.org/src/time/time.go#L116) type.  It's basically 
[DateTime](http://php.net/manual/en/class.datetime.php) with stuff on it.  I consider it to be in a "beta" state.

There are 2 basic ways to construct a Time class:

```php
use DCarbone\Go\Time;

// Returns an instance of Time\Time with an internal time of the unix epoch 
$t = Time::New();

// Returns an instance of Time\Time with an internal time of whenever you constructed it. 
$t = Time::Now();
```
