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

// nanosecond-precision parsing (fractions beyond 6 digits are retained)
$t = new Time\Time('2024-01-02T03:04:05.123456789Z');
$t = Time\Time::createFromFormat('Y-m-d H:i:s.u', '2024-01-02 03:04:05.123456789');

// from Unix offsets (UTC)
$t = Time::Unix(1704164645, 123456789);
$t = Time::UnixMilli(1704164645123);
$t = Time::UnixMicro(1704164645123456);
$t = Time::UnixNano(1704164645123456789);
```

Nanosecond precision:

`\DateTime` only stores microseconds, so `Time\Time` keeps the remaining sub-microsecond digits itself. They are
preserved by `add()`, `sub()`, `modify()`, `setDate()`, `setTimezone()`, cloning, `serialize()` and `var_export()`,
and reset to zero by `setTimestamp()`, `setTime()` and `setMicrosecond()`. Note that `modify()` with an absolute time
(e.g. `'midnight'`) does **not** reset them. Native `format()` output is limited to microseconds; use `RFC3339Nano()`,
`Nanosecond()` or `__toString()` for full precision.

Comparison helpers:

- `Before(Time $t)`, `After(Time $t)`, `Equal(Time $t)`
- `Compare(\DateTimeInterface $dt)` (`-1`, `0`, `1`)
- `BeforeDateTime(\DateTimeInterface $dt)`, `AfterDateTime(...)`, `EqualDateTime(...)`

Unix helpers:

- `Unix()`
- `UnixMilli()`, `UnixMicro()`, `UnixNano()` (throw `\RangeException` if the value does not fit in an int)
- `UnixNanoDuration()`

Date/time part helpers:

- `Year()`, `Month()`, `Day()`, `Weekday()`
- `Hour()`, `Minute()`, `Second()`, `Nanosecond()`
- `IsZero()`

Duration arithmetic:

- `AddDuration(Duration $d)`, `SubDuration(Duration $d)` (exact to the nanosecond; mutate and return the instance)
- `SubTime(\DateTimeInterface $dt)` (returns the `Duration` between two times, saturating like Go's `Time.Sub`)

Formatting:

- `RFC3339Nano()` (e.g. `2024-01-02T03:04:05.123456789Z`, trailing zeros trimmed)
- `__toString()` (Go-like `2006-01-02 15:04:05.999999999 -0700 MST` layout)
- `jsonSerialize()` (RFC3339Nano string, matching Go's `MarshalJSON`)

### Static facade helpers (`DCarbone\Go\Time`)

- Constants: `Nanosecond`, `Microsecond`, `Millisecond`, `Second`, `Minute`, `Hour`
- Constructors and now/epoch:
  - `New()`
  - `Now()`
  - `Unix(int $sec, int $nsec = 0)`, `UnixMilli(int $ms)`, `UnixMicro(int $us)`, `UnixNano(int $ns)`
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

- `Time::Now()` is limited to the microsecond resolution of the PHP runtime clock.
- `Since*()` / `Until*()` compute exact elapsed time rather than going through `\DateInterval`.
- `Duration(\DateInterval $input)` uses the exact `days` value when present (e.g. results of `diff()`); otherwise
  years/months are converted using average-hour constants and are approximate.

## Running tests

```bash
./vendor/bin/phpunit --display-deprecations --display-phpunit-deprecations
```
