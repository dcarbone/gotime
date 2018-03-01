<?php namespace DCarbone\GoTimeTests;

use DCarbone\Go\Time;
use PHPUnit\Framework\TestCase;

/**
 * Class TimeTest
 * @package DCarbone\GoTimeTests
 */
class TimeTest extends TestCase {

    /**
     * @return array
     */
    private static function getNowParts(): array {
        $mt = microtime();
        if (false !== strpos($mt, ' ')) {
            list($ns, $s) = explode(' ', $mt);
        } else {
            $ns = '0.0';
            $s = $mt;
        }
        $s = (int)$s;
        $ns = (int)rtrim(substr($ns, 2), '0') * Time::Microsecond + $s * Time::Second;

        return [$s, $ns];
    }

    public function testNew() {
        $time = Time::New();
        $this->assertInstanceOf(Time\Time::class, $time);
        $this->assertTrue($time->IsZero());
    }

    public function testParts() {
        $time = Time::New();
        $this->assertEquals(1970, $time->Year());
        $this->assertInternalType('integer', $time->Nanosecond());
        $this->assertEquals(0, $time->Nanosecond(), 'Nanosecond mismatch');
        $this->assertInternalType('integer', $time->Second());
        $this->assertEquals(0, $time->Second(), 'Second mismatch');
        $this->assertInternalType('integer', $time->Minute());
        $this->assertEquals(0, $time->Minute(), 'Minute mismatch');
        $this->assertInternalType('integer', $time->Hour());
        $this->assertEquals(0, $time->Hour(), 'Hour mismatch');
        $this->assertInternalType('integer', $time->Day());
        $this->assertEquals(1, $time->Day(), 'Day mismatch');
        $this->assertInstanceOf(Time\Weekday::class, $time->Weekday());
        $this->assertEquals(4, $time->Weekday()->Ord(), 'Weekday mismatch');
        $this->assertInstanceOf(Time\Month::class, $time->Month());
        $this->assertEquals(1, $time->Month()->Ord(), 'Month mismatch');
        $this->assertInternalType('integer', $time->Year());
        $this->assertEquals(1970, $time->Year(), 'Year mismatch');
        $this->assertInternalType('integer', $time->Unix());
        $this->assertEquals(0, $time->Unix(), 'Unix mismatch');
        $this->assertInternalType('integer', $time->UnixNano());
        $this->assertEquals(0, $time->UnixNano(), 'Unixnano mismatch');

    }

    // TODO: This one will probably cause erroneous failures...
    public function testNow() {
        list($s, $ns) = self::getNowParts();

        // close enough...
        $time = Time::Now();
        $this->assertInstanceOf(Time\Time::class, $time);
        $this->assertEquals((int)gmdate('Y', $s), $time->Year(), 'Year mismatch');
        $this->assertEquals((int)gmdate('m', $s), $time->Month()->Ord(), 'Month mismatch');
        $this->assertEquals((int)gmdate('w', $s), $time->Weekday()->Ord(), 'Weekday mismatch');
        $this->assertEquals((int)gmdate('d', $s), $time->Day(), 'Day mismatch');
        $this->assertEquals((int)gmdate('H', $s), $time->Hour(), 'Hour mismatch');
        $this->assertEquals((int)gmdate('i', $s), $time->Minute(), 'Minute mismatch');
        $this->assertEquals((int)gmdate('s', $s), $time->Second(), 'Second mismatch');
        $this->assertEquals((int)gmdate('U', $s), $time->Unix()); // TODO: mildly redundant

        // difficult to really assert, but maybe assume a small range of acceptance?
        $this->assertTrue(($ns + 500 * Time::Millisecond > $time->UnixNano()) ||
            ($ns - 500 * Time::Millisecond < $time->UnixNano()));
    }

    public function testBefore() {
        $t1 = Time::Now();

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(5 * Time::Hour));
        $this->assertTrue($t1->Before($t2));
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->SubDuration(new Time\Duration(5 * Time::Hour));
        $this->assertFalse($t1->Before($t2));
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(-5 * Time::Hour));
        $this->assertFalse($t1->Before($t2));
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(5 * Time::Microsecond));
        $this->assertTrue($t1->Before($t2));
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');
    }

    public function testBeforeDateTime() {
        $t = Time::Now();

        $dt = new \DateTime();
        $dt2 = $dt->add(new \DateInterval('PT1H'));
        $this->assertTrue($t->BeforeDateTime($dt));
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');

        $dt = new \DateTime();
        $dt2 = $dt->add(new Time\DateInterval('PT0S', true, 0.5));
        $this->assertFalse($t->BeforeDateTime($dt));
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');
    }

    public function testAfter() {
        $t1 = Time::Now();

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(5 * Time::Second));
        $this->assertFalse($t1->After($t2));
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->SubDuration(new Time\Duration(5 * Time::Second));
        $this->assertTrue($t1->After($t2));
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(-5 * Time::Second));
        $this->assertTrue($t1->After($t2));
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');
    }

    public function testAfterDateTime() {
        $t = Time::Now();

        $dt = new \DateTime();
        $dt2 = $dt->sub(new Time\DateInterval('PT5S'));
        $this->assertTrue($t->AfterDateTime($dt));
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');

        $dt = new \DateTime();
        $dt2 = $dt->add(new Time\DateInterval('PT5S', true));
        $this->assertTrue($t->AfterDateTime($dt));
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');

        $dt = new \DateTime();
        $dt2 = $dt->add(new Time\DateInterval('PT5S'));
        $this->assertFalse($t->AfterDateTime($dt));
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');
    }
}