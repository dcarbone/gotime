<?php namespace DCarbone\GoTimeTests;

use DCarbone\Go\Time;
use PHPUnit\Framework\TestCase;

/**
 * Class Time\DurationTest
 * @package DCarbone\GOTimeTests
 */
class DurationTest extends TestCase {
    const ZeroThreshold = 1.0e-6;

    public function testCanConstructEmpty() {
        $d = new Time\Duration();
        $this->assertInstanceOf(Time\Duration::class, $d);
    }

    /**
     * @depends testCanConstructEmpty
     */
    public function testCanConstructWithValue() {
        $n = time() * Time::Second;
        $d = new Time\Duration($n);
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals($n, $d->Nanoseconds());

        $d = new Time\Duration(-1);
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(-1, $d->Nanoseconds());
    }

    /**
     * @depends testCanConstructWithValue
     */
    public function testTruncate() {
        $d = new Time\Duration(Time::Second);
        $td = $d->Truncate(new Time\Duration(500 * Time::Millisecond));
        $this->assertNotSame($d, $td);
        $this->assertEquals(Time::Second, $td->Nanoseconds());
        $td = $d->Truncate(new Time\Duration(1001 * Time::Millisecond));
        $this->assertEquals(0, $td->Nanoseconds());
        $td = $d->Truncate(new Time\Duration(-1));
        $this->assertEquals(Time::Second, $td->Nanoseconds());
    }

    /**
     * @depends testCanConstructWithValue
     */
    public function testRound() {
        $d = new Time\Duration(Time::Second);
        $td = $d->Round(new Time\Duration(500 * Time::Millisecond));
        $this->assertNotSame($d, $td);
        $this->assertEquals(Time::Second, $td->Nanoseconds());
        $td = $d->Round(new Time\Duration(5 * Time::Second));
        $this->assertEquals(0, $td->Nanoseconds());
    }

    /**
     * @depends testCanConstructWithValue
     */
    public function testParseDuration() {
        $d = Time::ParseDuration('1ns');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(1, $d->Nanoseconds());
        $this->assertEquals(1e-9, $d->Seconds());

        $d = Time::ParseDuration('1us');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(1e3, $d->Nanoseconds());
        $this->assertEqualFloats(1e-6, $d->Seconds());

        $d = Time::ParseDuration('1µs');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(1e3, $d->Nanoseconds());
        $this->assertEqualFloats(1e-6, $d->Seconds());

        $d = Time::ParseDuration('1μs');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(1e3, $d->Nanoseconds());
        $this->assertEqualFloats(1e-6, $d->Seconds());

        $d = Time::ParseDuration('1ms');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(1e6, $d->Nanoseconds());
        $this->assertEqualFloats(1e-3, $d->Seconds());

        $d = Time::ParseDuration('1s');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(1e9, $d->Nanoseconds());
        $this->assertEqualFloats(1.0, $d->Seconds());

        $d = Time::ParseDuration('1m');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(Time::Minute, $d->Nanoseconds());
        $this->assertEqualFloats(60.0, $d->Seconds());

        $d = Time::ParseDuration('1h');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(Time::Hour, $d->Nanoseconds());
        $this->assertEqualFloats(3600.0, $d->Seconds());

        $d = Time::ParseDuration('1h0m');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(Time::Hour, $d->Nanoseconds());

        $d = Time::ParseDuration('0m0s');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(0, $d->Nanoseconds());

        $d = Time::ParseDuration('1.5s');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(1.5e9, $d->Nanoseconds());

        $d = Time::ParseDuration('1.5h2ns');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(Time::Hour + 30 * Time::Minute + 2 * Time::Nanosecond, $d->Nanoseconds());

        $d = Time::ParseDuration('1h2m3s4ms5us6ns');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals(
            Time::Hour +
            2 * Time::Minute +
            3 * Time::Second +
            4 * Time::Millisecond +
            5 * Time::Microsecond +
            6 * Time::Nanosecond,
            $d->Nanoseconds());

        $d = Time::ParseDuration('1s500ms');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEqualFloats(1.5, $d->Seconds());
    }

    /**
     * @depends testParseDuration
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionThrownWithInvalidDuration() {
        Time::ParseDuration('922337203685477581ns');
    }

    /**
     * @depends testParseDuration
     */
    public function testStringer() {
        $this->assertEquals('500ms', (string)new Time\Duration(500 * Time::Millisecond));
        $this->assertEquals('500µs', (string)new Time\Duration(500 * Time::Microsecond));
        $this->assertEquals('500ns', (string)new Time\Duration(500 * Time::Nanosecond));

        $now = time();
        $d = new Time\Duration($now * Time::Second);
        preg_match('/^(\d+)h(\d+)m(\d+)s$/', (string)$d, $matches);
        $this->assertEquals((int)$d->Hours(), (int)$matches[1]);
        $t = \DateTime::createFromFormat('U', $now, new \DateTimeZone('UTC'));
        $this->assertEquals(
            (int)$t->format('i'),
            (int)$matches[2],
            sprintf('Minute mismatch: %d %d', $matches[2], $t->format('m'))
        );
        $this->assertEquals(
            (int)$t->format('s'),
            (int)$matches[3],
            sprintf('Second mismatch: %d %d', $matches[3], $t->format('s'))
        );

        $d = Time::ParseDuration('1h30m');
        $this->assertInstanceOf(Time\Duration::class, $d);
        $this->assertEquals('1h30m0s', (string)$d);

        $d = Time::ParseDuration('1h2m3s4ms5us6ns');
        $this->assertEquals('1h2m3.004005006s', (string)$d);
    }

    /**
     * @depends testParseDuration
     */
    public function testCompare() {
        $d1 = Time::ParseDuration('5s');
        $this->assertEquals(1, $d1->Compare(Time::ParseDuration('1s')));
        $this->assertEquals(0, $d1->Compare(Time::ParseDuration('5s')));
    }

    public function testIntervalSpec() {
        $d = new Time\Duration();
        $spec = $d->IntervalSpec();
        $this->assertInstanceOf(Time\IntervalSpec::class, $spec);
        $this->assertEquals('PT0S', $spec->spec);
        $this->assertEquals(0.0, $spec->f);
        $this->assertFalse($spec->invert);

        $d = new Time\Duration(3600 * Time::Hour + 5 * Time::Minute + 3 * Time::Millisecond);
        $spec = $d->IntervalSpec();
        $this->assertInstanceOf(Time\IntervalSpec::class, $spec);
        $this->assertEquals('PT3600H5M0S', $spec->spec);
        $this->assertEquals(0.003, $spec->f);
        $this->assertFalse($spec->invert);

        $d = new Time\Duration(-500 * Time::Second);
        $spec = $d->IntervalSpec();
        $this->assertInstanceOf(Time\IntervalSpec::class, $spec);
        $this->assertEquals('PT8M20S', $spec->spec);
        $this->assertEquals(0.0, $spec->f);
        $this->assertTrue($spec->invert);
    }

    public function testDateInterval() {
        $d = new Time\Duration();
        $di = $d->DateInterval();
        $this->assertInstanceOf(\DateInterval::class, $di);
        $this->assertEquals(0.0, $di->f, 'Sub-seconds mismatch');
        $this->assertEquals(0, $di->s, 'Seconds mismatch');
        $this->assertEquals(0, $di->i, 'Minutes mismatch');
        $this->assertEquals(0, $di->h, 'Hours mismatch');
        $this->assertEquals(0, $di->days, 'Days mismatch');
        $this->assertEquals(0, $di->m, 'Months mismatch');
        $this->assertEquals(0, $di->y, 'Years mismatch');
        $this->assertEquals(0, $di->invert, 'Inversion mismatch');

        $d = new Time\Duration(3 * Time::Hour + 120 * Time::Minute + 5 * Time::Second);
        $di = $d->DateInterval();
        $this->assertInstanceOf(\DateInterval::class, $di);
        $this->assertEquals(0.0, $di->f, 'Sub-seconds mismatch');
        $this->assertEquals(5, $di->s, 'Seconds mismatch');
        $this->assertEquals(0, $di->i, 'Minutes mismatch');
        $this->assertEquals(5, $di->h, 'Hours mismatch');
        $this->assertEquals(0, $di->invert, 'Inversion mismatch');

        $d = new Time\Duration(-1000 * Time::Millisecond);
        $di = $d->DateInterval();
        $this->assertInstanceOf(\DateInterval::class, $di);
        $this->assertEquals(1, $di->s, 'Seconds mismatch');
        $this->assertEquals(1, $di->invert, 'Inversion mismatch');

        $d = new Time\Duration(-50 * Time::Microsecond);
        $di = $d->DateInterval();
        $this->assertInstanceOf(\DateInterval::class, $di);
        $this->assertEquals(0.00005, $di->f);
        $this->assertEquals(1, $di->invert);
    }

    /**
     * @param float $expected
     * @param float $actual
     * @return void
     */
    private function assertEqualFloats(float $expected, float $actual) {
        $this->assertLessThanOrEqual(self::ZeroThreshold,
            abs($expected - $actual),
            sprintf('equal assertion fail, %.6f != %.6f', $expected, $actual));
    }
}