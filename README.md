# gotime

Golang-inspired time helpers for PHP **8.2+**.

[![Tests](https://github.com/dcarbone/gotime/actions/workflows/tests.yml/badge.svg)](https://github.com/dcarbone/gotime/actions/workflows/tests.yml)

## Installation

```bash
composer require dcarbone/gotime
```

## Quick start

```php
<?php

use DCarbone\Go\Time;

$t = Time::Now();
$d = Time::ParseDuration('1h30m');

echo $t, PHP_EOL;              // formatted timestamp
echo $d, PHP_EOL;              // 1h30m0s
echo $d->Nanoseconds(), PHP_EOL; // 5400000000000
```

## API overview

This package exposes a static facade class, `DCarbone\Go\Time`, plus several value objects under `DCarbone\Go\Time\*`.

### Duration (`DCarbone\Go\Time\Duration`)

`Duration` stores elapsed time as a signed integer count of nanoseconds.

Common construction patterns:

```php
use DCarbone\Go\Time;

$a = new Time\Duration(5 * Time::Second);
$b = Time::ParseDuration('5s');
$c = Time::Duration('1h2m3.5s'); // cast helper
```

Supported `ParseDuration()` units:

- `ns`
- `us`, `µs`, `μs`
- `ms`
- `s`
- `m`
- `h`

Key methods:

- `Nanoseconds()`, `Microseconds()`, `Milliseconds()`, `Seconds()`, `Minutes()`, `Hours()`
- `Truncate(Duration $m)`, `Round(Duration $m)`
- `Compare(Duration $other)` (`-1`, `0`, `1`)
- `DateInterval()` / `IntervalSpec()`
- `__toString()` (Go-like formatting, e.g. `500ms`, `1h2m3.004s`)
- `jsonSerialize()` (raw nanosecond integer)

### Time (`DCarbone\Go\Time\Time`)

`Time\Time` extends `\DateTime` and adds Go-style helpers.

Primary constructors:

```php
use DCarbone\Go\Time;

$zero = Time::New(); // Unix epoch
$now  = Time::Now(); // current time
```

Comparison helpers:

- `Before(Time $t)`, `After(Time $t)`, `Equal(Time $t)`
- `BeforeDateTime(\DateTimeInterface $dt)`, `AfterDateTime(...)`, `EqualDateTime(...)`

Unix helpers:

- `Unix()`
- `UnixNano()`
- `UnixNanoDuration()`

Date/time part helpers:

- `Year()`, `Month()`, `Day()`, `Weekday()`
- `Hour()`, `Minute()`, `Second()`, `Nanosecond()`
- `IsZero()`

Duration arithmetic:

- `AddDuration(Duration $d)`
- `SubDuration(Duration $d)`

### Static facade helpers (`DCarbone\Go\Time`)

- Constants: `Nanosecond`, `Microsecond`, `Millisecond`, `Second`, `Minute`, `Hour`
- Constructors and now/epoch:
  - `New()`
  - `Now()`
- Duration operations:
  - `ParseDuration(string $s)`
  - `Duration(null|string|int|float|Duration|\DateInterval $input)`
  - `CompareDuration(Duration $d1, Duration $d2)`
  - `ZeroDuration()`
- Relative time:
  - `Since(Time\Time $t)`
  - `SinceDateTime(\DateTimeInterface $dt)`
  - `Until(Time\Time $t)`
  - `UntilDateTime(\DateTimeInterface $dt)`

### Supporting value types

- `Time\Month` (month ordinal/name helpers)
- `Time\Weekday` (weekday ordinal/name helpers)
- `Time\IntervalSpec` (intermediate duration-to-interval representation)
- `Time\DateInterval` (extends `\DateInterval`, including fractional seconds support)

## Notes

- Precision in PHP runtime is microseconds; nanosecond APIs are represented as integer nanoseconds derived from microsecond resolution.
- `Duration(\DateInterval $input)` converts years/months using average-hour constants, so calendar-month/year conversion is approximate.

## Running tests

```bash
./vendor/bin/phpunit --display-deprecations --display-phpunit-deprecations
```
