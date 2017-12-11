<?php namespace DCarbone\GoTimeTests;

ini_set('precision', 17);

use DCarbone\Go\Time;
use DCarbone\Go\Time\Duration;
use PHPUnit\Framework\TestCase;

/**
 * Class DurationTest
 * @package DCarbone\GOTimeTests
 */
class DurationTest extends TestCase {
    const ZeroThreshold = 1.0e-6;

    public function testCanConstructEmpty() {
        $d = new Duration();
        $this->assertInstanceOf(Duration::class, $d);
    }

    /**
     * @depends testCanConstructEmpty
     */
    public function testCanConstructWithValue() {
        $n = time() * Time::Second;
        $d = new Duration($n);
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals($n, $d->Nanoseconds());
    }

    /**
     * @depends testCanConstructWithValue
     */
    public function testSubSecondFormat() {
        $this->assertEquals('500ms', (string)new Duration(500 * Time::Millisecond));
        $this->assertEquals('500µs', (string)new Duration(500 * Time::Microsecond));
        $this->assertEquals('500ns', (string)new Duration(500 * Time::Nanosecond));
    }

    /**
     * @depends testCanConstructWithValue
     */
    public function testLargerThanSecondFormat() {
        $now = time();
        $d = new Duration($now * Time::Second);
        preg_match('/^(\d+)h(\d+)m(\d+)s$/', (string)$d, $matches);
        $this->assertEquals((int)$d->Hours(), (int)$matches[1]);
        $t = \DateTime::createFromFormat('U', $now, new \DateTimeZone('UTC'));
        $this->assertEquals((int)$t->format('i'),
            (int)$matches[2],
            sprintf('Minute mismatch: %d %d', $matches[2], $t->format('m')));
        $this->assertEquals((int)$t->format('s'),
            (int)$matches[3],
            sprintf('Second mismatch: %d %d', $matches[3], $t->format('s')));
    }

    /**
     * @depends testCanConstructWithValue
     */
    public function testParseDuration() {
        $d = Time::ParseDuration('1ns');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(1, $d->Nanoseconds());
        $this->assertEquals(1e-9, $d->Seconds());

        $d = Time::ParseDuration('1us');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(1e3, $d->Nanoseconds());
        $this->assertEqualFloats(1e-6, $d->Seconds());

        $d = Time::ParseDuration('1µs');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(1e3, $d->Nanoseconds());
        $this->assertEqualFloats(1e-6, $d->Seconds());

        $d = Time::ParseDuration('1μs');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(1e3, $d->Nanoseconds());
        $this->assertEqualFloats(1e-6, $d->Seconds());

        $d = Time::ParseDuration('1ms');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(1e6, $d->Nanoseconds());
        $this->assertEqualFloats(1e-3, $d->Seconds());

        $d = Time::ParseDuration('1s');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(1e9, $d->Nanoseconds());
        $this->assertEqualFloats(1.0, $d->Seconds());

        $d = Time::ParseDuration('1m');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(Time::Minute, $d->Nanoseconds());
        $this->assertEqualFloats(60.0, $d->Seconds());

        $d = Time::ParseDuration('1h');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(Time::Hour, $d->Nanoseconds());
        $this->assertEqualFloats(3600.0, $d->Seconds());

        $d = Time::ParseDuration('1h2m3s4ms5us6ns');
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEquals(
            Time::Hour +
            2 * Time::Minute +
            3 * Time::Second +
            4 * Time::Millisecond +
            5 * Time::Microsecond +
            6 * Time::Nanosecond,
            $d->Nanoseconds());

        $d = Time::ParseDuration('1s500ms');
        $this->assertInstanceOf(Duration::class, $d);
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
    public function testCanGetDateTime() {
        $n = time();
        $d = Time::ParseDuration(sprintf('%ds', $n));
        $this->assertInstanceOf(Duration::class, $d);
        $this->assertEqualFloats((float)$n, $d->Seconds());

        $dt = $d->DateTime();
        $this->assertInstanceOf(\DateTime::class, $dt);
        $this->assertEqualFloats($d->Seconds(), (float)$dt->format('U'));
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