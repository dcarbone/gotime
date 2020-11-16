<?php

namespace DCarbone\GoTimeTests;

use DCarbone\Go\Time;
use PHPUnit\Framework\TestCase;

/**
 * Class TimeTest
 * @package DCarbone\GoTimeTests
 */
class TimeTest extends TestCase
{

    public function testNew()
    {
        $time = Time::New();
        $this->assertInstanceOf(Time\Time::class, $time);
        $this->assertTrue($time->IsZero());
    }

    public function testParts()
    {
        $time = Time::New();
        $this->assertEquals(1970, $time->Year());
        $this->assertIsInt($time->Nanosecond());
        $this->assertEquals(0, $time->Nanosecond(), 'Nanosecond mismatch');
        $this->assertIsInt($time->Second());
        $this->assertEquals(0, $time->Second(), 'Second mismatch');
        $this->assertIsInt($time->Minute());
        $this->assertEquals(0, $time->Minute(), 'Minute mismatch');
        $this->assertIsInt($time->Hour());
        $this->assertEquals(0, $time->Hour(), 'Hour mismatch');
        $this->assertIsInt($time->Day());
        $this->assertEquals(1, $time->Day(), 'Day mismatch');
        $this->assertInstanceOf(Time\Weekday::class, $time->Weekday());
        $this->assertEquals(4, $time->Weekday()->Ord(), 'Weekday mismatch');
        $this->assertInstanceOf(Time\Month::class, $time->Month());
        $this->assertEquals(1, $time->Month()->Ord(), 'Month mismatch');
        $this->assertIsInt($time->Year());
        $this->assertEquals(1970, $time->Year(), 'Year mismatch');
        $this->assertIsInt($time->Unix());
        $this->assertEquals(0, $time->Unix(), 'Unix mismatch');
        $this->assertIsInt($time->UnixNano());
        $this->assertEquals(0, $time->UnixNano(), 'Unixnano mismatch');
    }

    public function testNow()
    {
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
        $this->assertTrue(
            ($ns + 500 * Time::Millisecond > $time->UnixNano()) ||
            ($ns - 500 * Time::Millisecond < $time->UnixNano())
        );

        // for 1 million iterations, call ::Now() just to MAYBE HOPEFULLY catch ridiculousness...
        for ($i = 0; $i < 1000000; $i++) {
            Time::Now();
        }
    }

    // TODO: This one will probably cause erroneous failures...

    /**
     * @return array
     */
    private static function getNowParts(): array
    {
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

    public function testBefore()
    {
        $t1 = Time::Now();

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(5 * Time::Hour));
        $this->assertTrue($t1->Before($t2), 'Expected ' . $t1 . ' to be before ' . $t2);
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->SubDuration(new Time\Duration(5 * Time::Hour));
        $this->assertFalse($t1->Before($t2), 'Expected ' . $t2 . ' to be before ' . $t1);
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(-5 * Time::Hour));
        $this->assertFalse($t1->Before($t2), 'Expected ' . $t2 . ' to be before ' . $t1);
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        if (GOTIME_GTE71) {
            $t2 = Time::Now();
            $t3 = $t2->AddDuration(new Time\Duration(5 * Time::Microsecond));
            $this->assertTrue($t1->Before($t2), 'Expected ' . $t1 . ' to be before ' . $t2);
            $this->assertSame($t2, $t3, 'Expected $t3 === $t2');
        }
    }

    public function testBeforeDateTime()
    {
        $t = Time::Now();

        $dt = new \DateTime();
        $dt2 = $dt->add(new \DateInterval('PT1H'));
        $this->assertTrue(
            $t->BeforeDateTime($dt),
            'Expected ' . $t . ' to be before ' . $dt->format(Time\Time::DefaultFormat)
        );
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');

        if (GOTIME_GTE71) {
            $dt = new \DateTime();
            $dt2 = $dt->add(new Time\DateInterval('PT0S', true, 0.5));
            $this->assertFalse(
                $t->BeforeDateTime($dt),
                'Expected ' . $dt->format(Time\Time::DefaultFormat) . ' to be before ' . $t
            );
            $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');
        }
    }

    public function testAfter()
    {
        $t1 = Time::Now();

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(5 * Time::Second));
        $this->assertFalse($t1->After($t2), 'Expected ' . $t2 . ' to be after ' . $t1);
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->SubDuration(new Time\Duration(5 * Time::Second));
        $this->assertTrue($t1->After($t2), 'Expected ' . $t1 . ' to be after ' . $t2);
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');

        $t2 = Time::Now();
        $t3 = $t2->AddDuration(new Time\Duration(-5 * Time::Second));
        $this->assertTrue($t1->After($t2), 'Expected ' . $t1 . ' to be after ' . $t2);
        $this->assertSame($t2, $t3, 'Expected $t3 === $t2');
    }

    public function testAfterDateTime()
    {
        $t = Time::Now();

        $dt = new \DateTime();
        $dt2 = $dt->sub(new Time\DateInterval('PT5S'));
        $this->assertTrue(
            $t->AfterDateTime($dt),
            'Expected ' . $t . ' to be after ' . $dt->format(Time\Time::DefaultFormat)
        );
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');

        $dt = new \DateTime();
        $dt2 = $dt->add(new Time\DateInterval('PT5S', true));
        $this->assertTrue(
            $t->AfterDateTime($dt),
            'Expected ' . $t . ' to be after ' . $dt->format(Time\Time::DefaultFormat)
        );
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');

        $dt = new \DateTime();
        $dt2 = $dt->add(new Time\DateInterval('PT5S'));
        $this->assertFalse(
            $t->AfterDateTime($dt),
            'Expected ' . $dt->format(Time\Time::DefaultFormat) . ' to be after ' . $t
        );
        $this->assertSame($dt, $dt2, 'Expected $dt2 === $dt');
    }
}