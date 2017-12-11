<?php namespace DCarbone\GoTimeTests;

use DCarbone\Go\Time;
use DCarbone\Go\Time\Duration;
use PHPUnit\Framework\TestCase;

/**
 * Class DurationTest
 * @package DCarbone\GOTimeTests
 */
class DurationTest extends TestCase {
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
        $this->assertEquals((int)$t->format('i'), (int)$matches[2], sprintf('Minute mismatch: %d %d', $matches[2], $t->format('m')));
        $this->assertEquals((int)$t->format('s'), (int)$matches[3], sprintf('Second mismatch: %d %d', $matches[3], $t->format('s')));
    }

    public function testParseDuration() {
//        $d = GoTime\ParseDuration('1h2m3s4m5u6n');
    }
}