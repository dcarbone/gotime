<?php declare(strict_types=1);

namespace DCarbone\GoTimeTests;

use DCarbone\Go\Time;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NanosecondTest extends TestCase
{
    public static function parseCases(): array
    {
        return [
            'rfc3339nano utc'      => ['2024-01-02T03:04:05.123456789Z', 1704164645, 123456789, '2024-01-02T03:04:05.123456789Z'],
            'rfc3339nano offset'   => ['2024-01-02T03:04:05.123456789-05:00', 1704182645, 123456789, '2024-01-02T03:04:05.123456789-05:00'],
            'seven digits'         => ['2024-01-02 03:04:05.1234567 UTC', 1704164645, 123456700, '2024-01-02T03:04:05.1234567Z'],
            'twelve digits'        => ['2024-01-02T03:04:05.000000001999Z', 1704164645, 1, '2024-01-02T03:04:05.000000001Z'],
            'microseconds'         => ['2024-01-02T03:04:05.5Z', 1704164645, 500000000, '2024-01-02T03:04:05.5Z'],
            'no fraction'          => ['2024-01-02T03:04:05Z', 1704164645, 0, '2024-01-02T03:04:05Z'],
            'unix positive'        => ['@1704164645.123456789', 1704164645, 123456789, '2024-01-02T03:04:05.123456789Z'],
            'unix negative'        => ['@-2.123456789', -3, 876543211, '1969-12-31T23:59:57.876543211Z'],
            'unix negative tiny'   => ['@-0.0000001', -1, 999999900, '1969-12-31T23:59:59.9999999Z'],
            'pre-epoch wall clock' => ['1969-12-31T23:59:59.999999999Z', -1, 999999999, '1969-12-31T23:59:59.999999999Z'],
        ];
    }

    #[DataProvider('parseCases')]
    public function testParseAndFormat(string $in, int $sec, int $nsec, string $rfc)
    {
        $t = new Time\Time($in);
        $this->assertSame($sec, $t->Unix());
        $this->assertSame($nsec, $t->Nanosecond());
        $this->assertSame($rfc, $t->RFC3339Nano());
        $this->assertSame(json_encode($rfc), json_encode($t));

        $rt = new Time\Time($t->RFC3339Nano());
        $this->assertTrue($t->Equal($rt));
        $this->assertSame($t->getOffset(), $rt->getOffset());
    }

    public function testCreateFromFormat()
    {
        $t = Time\Time::createFromFormat('Y-m-d\TH:i:s.uP', '2024-01-02T03:04:05.123456789+00:00');
        $this->assertInstanceOf(Time\Time::class, $t);
        $this->assertSame(123456789, $t->Nanosecond());

        $t = Time\Time::createFromFormat('Y-m-d H:i:s.u', '2024-01-02 03:04:05.123456', new \DateTimeZone('America/Chicago'));
        $this->assertInstanceOf(Time\Time::class, $t);
        $this->assertSame(123456000, $t->Nanosecond());
        $this->assertSame('America/Chicago', $t->getTimezone()->getName());

        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s.u', '2024-01-02 03:04:05.123456789xyz'));
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s', '2024-01-02 03:04:05.123456789'));

        // extra digits must directly follow an unescaped "u" field, not an escaped literal
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s \u1.123456', '2024-01-02 03:04:05 u1.123456789'));
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s.\u', '2024-01-02 03:04:05.u123456789'));
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s.123456', '2024-01-02 03:04:05.123456789'));
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s.u', '2024-01-02 03:04:05.12345x789'));

        $t = Time\Time::createFromFormat('\u Y-m-d H:i:s.uP', 'u 2024-01-02 03:04:05.12345678+02:00');
        $this->assertInstanceOf(Time\Time::class, $t);
        $this->assertSame(123456780, $t->Nanosecond());
        $this->assertSame('+02:00', $t->format('P'));

        // trailing digits required by later format characters are not consumed as fraction
        $t = Time\Time::createFromFormat('!Y-m-d H:i:s.u\0\0', '2024-01-02 03:04:05.12345678900');
        $this->assertInstanceOf(Time\Time::class, $t);
        $this->assertSame(123456789, $t->Nanosecond());
        $t = Time\Time::createFromFormat('Y-m-d H:i:s.u\0', '2024-01-02 03:04:05.12345670');
        $this->assertSame(123456700, $t->Nanosecond());
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s.u\0', '2024-01-02 03:04:05.1234567'));
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s.u\0\0\0', '2024-01-02 03:04:05.12345678'));

        // long fractions are accepted, but at most 20 trailing digits are left for the rest of the format
        $long = '2024-01-02 03:04:05.123456789' . str_repeat('9', 100000);
        $this->assertSame(123456789, Time\Time::createFromFormat('Y-m-d H:i:s.u', $long)->Nanosecond());
        $start = hrtime(true);
        $this->assertFalse(Time\Time::createFromFormat('Y-m-d H:i:s.u', $long . 'x'));
        $this->assertLessThan(1e9, hrtime(true) - $start);
        $f = 'Y-m-d H:i:s.u' . str_repeat('\0', 20);
        $this->assertSame(123456789, Time\Time::createFromFormat($f, '2024-01-02 03:04:05.123456789' . str_repeat('0', 20))->Nanosecond());
        $this->assertFalse(Time\Time::createFromFormat($f . '\0', '2024-01-02 03:04:05.123456789' . str_repeat('0', 21)));
    }

    public function testJsonSerializeYearRange()
    {
        $this->assertSame('"9999-12-31T23:59:59.999999999Z"', json_encode(Time::Unix(253402300799, 999999999)));
        $this->assertSame('"0000-01-01T00:00:00Z"', json_encode(new Time\Time('0000-01-01T00:00:00Z')));
        $this->assertSame('10000-01-01T00:00:00Z', Time::Unix(253402300800)->RFC3339Nano());

        foreach ([Time::Unix(253402300800), new Time\Time('-0001-12-31T23:59:59Z')] as $t) {
            try {
                json_encode($t);
                $this->fail('expected RangeException for ' . $t->RFC3339Nano());
            } catch (\RangeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public static function unixCases(): array
    {
        return [
            [0],
            [1],
            [-1],
            [999],
            [-999],
            [1704164645123456789],
            [-1704164645123456789],
            [PHP_INT_MAX],
            [PHP_INT_MIN],
        ];
    }

    #[DataProvider('unixCases')]
    public function testUnixNanoRoundTrip(int $ns)
    {
        $t = Time::UnixNano($ns);
        $this->assertSame($ns, $t->UnixNano());
        $this->assertSame($ns, $t->UnixNanoDuration()->Nanoseconds());
        $this->assertSame('+00:00', $t->format('P'));
        $this->assertGreaterThanOrEqual(0, $t->Nanosecond());
        $this->assertLessThan(Time::Second, $t->Nanosecond());

        $this->assertSame(intdiv($ns, 1000) - ($ns % 1000 < 0 ? 1 : 0), $t->UnixMicro());
        $this->assertSame(intdiv($ns, 1000000) - ($ns % 1000000 < 0 ? 1 : 0), $t->UnixMilli());
    }

    public function testUnixConstructors()
    {
        $t = Time::Unix(5, -1);
        $this->assertSame(4, $t->Unix());
        $this->assertSame(999999999, $t->Nanosecond());

        $t = Time::Unix(5, 3 * Time::Second + 7);
        $this->assertSame(8, $t->Unix());
        $this->assertSame(7, $t->Nanosecond());

        $t = Time::UnixMilli(-1);
        $this->assertSame(-1, $t->Unix());
        $this->assertSame(999000000, $t->Nanosecond());
        $this->assertSame(-1, $t->UnixMilli());

        $t = Time::UnixMicro(-1);
        $this->assertSame(-1, $t->Unix());
        $this->assertSame(999999000, $t->Nanosecond());
        $this->assertSame(-1, $t->UnixMicro());
    }

    public function testToString()
    {
        $this->assertSame('2024-01-02 03:04:05.000000001 +0000 UTC', (string)new Time\Time('2024-01-02T03:04:05.000000001 UTC'));
        $this->assertSame('2024-01-02 03:04:05.120000000 +0000 UTC', (string)new Time\Time('2024-01-02T03:04:05.12 UTC'));
        $this->assertSame('2024-01-02 03:04:05 +0000 UTC', (string)new Time\Time('2024-01-02T03:04:05 UTC'));
    }

    public function testComparisonsUseNanoseconds()
    {
        $a = Time::UnixNano(1000);
        $b = Time::UnixNano(1001);
        $this->assertTrue($a->Before($b));
        $this->assertTrue($b->After($a));
        $this->assertFalse($a->Equal($b));
        $this->assertSame(-1, $a->Compare($b));
        $this->assertSame(0, $a->Compare(Time::UnixNano(1000)));

        $dt = new \DateTime('@0.000001');
        $this->assertTrue($a->EqualDateTime($dt));
        $this->assertTrue($b->AfterDateTime($dt));
        $this->assertTrue($b->BeforeDateTime(new \DateTimeImmutable('@0.000002')));

        // far outside the int64 nanosecond range
        $this->assertTrue((new Time\Time('1000-01-01T00:00:00Z'))->Before(new Time\Time('3000-01-01T00:00:00Z')));
    }

    public function testAddSubDuration()
    {
        $t = Time::Unix(10, 999999999);
        $this->assertSame($t, $t->AddDuration(new Time\Duration(2)));
        $this->assertSame(11, $t->Unix());
        $this->assertSame(1, $t->Nanosecond());

        $t->SubDuration(new Time\Duration(2));
        $this->assertSame(10, $t->Unix());
        $this->assertSame(999999999, $t->Nanosecond());

        $t->AddDuration(new Time\Duration(-11 * Time::Second));
        $this->assertSame(-1, $t->Unix());
        $this->assertSame(999999999, $t->Nanosecond());

        $t = Time::UnixNano(0)->SubDuration(new Time\Duration(PHP_INT_MIN));
        $this->assertSame(9223372036, $t->Unix());
        $this->assertSame(854775808, $t->Nanosecond());
    }

    public function testUnixNanoOutOfRange()
    {
        $t = new Time\Time('2263-01-01T00:00:00Z');
        $this->assertSame(9246182400000, $t->UnixMilli());
        $this->expectException(\RangeException::class);
        $t->UnixNano();
    }

    public function testAddDurationPreservesTimezone()
    {
        $t = new Time\Time('2024-03-10T01:30:00.000000001', new \DateTimeZone('America/Chicago'));
        $t->AddDuration(new Time\Duration(Time::Hour));
        $this->assertSame('America/Chicago', $t->getTimezone()->getName());
        $this->assertSame('2024-03-10T03:30:00.000000001-05:00', $t->RFC3339Nano());
    }

    public function testSubTime()
    {
        $a = Time::Unix(10, 5);
        $b = Time::Unix(9, 999999999);
        $this->assertSame(6, $a->SubTime($b)->Nanoseconds());
        $this->assertSame(-6, $b->SubTime($a)->Nanoseconds());
        $this->assertSame(1, $a->SubTime(new \DateTime('@10'))->Nanoseconds() - 4);

        $far = new Time\Time('3000-01-01T00:00:00Z');
        $old = new Time\Time('1000-01-01T00:00:00Z');
        $this->assertSame(PHP_INT_MAX, $far->SubTime($old)->Nanoseconds());
        $this->assertSame(PHP_INT_MIN, $old->SubTime($far)->Nanoseconds());

        // differences that only fit once fractional seconds are included must not saturate
        $hi = Time::Unix(9223372037);
        $lo = Time::Unix(0, 999999999);
        $this->assertSame(9223372036000000001, $hi->SubTime($lo)->Nanoseconds());
        $this->assertSame(-9223372036000000001, $lo->SubTime($hi)->Nanoseconds());
        $this->assertSame(PHP_INT_MAX, Time::UnixNano(PHP_INT_MAX)->SubTime(Time::Unix(0))->Nanoseconds());
        $this->assertSame(PHP_INT_MIN, Time::UnixNano(PHP_INT_MIN)->SubTime(Time::Unix(0))->Nanoseconds());
        $this->assertSame(PHP_INT_MAX, Time::UnixNano(PHP_INT_MAX)->SubTime(Time::Unix(0, 1))->Nanoseconds() + 1);
        $this->assertSame(PHP_INT_MAX, Time::Unix(0)->SubTime(Time::UnixNano(PHP_INT_MIN + 1))->Nanoseconds());
        $this->assertSame(PHP_INT_MAX, Time::Unix(0)->SubTime(Time::UnixNano(PHP_INT_MIN))->Nanoseconds());
    }

    public function testUnixExtremeSeconds()
    {
        $t = Time::Unix(PHP_INT_MIN, 1000);
        $this->assertSame(PHP_INT_MIN, $t->Unix());
        $this->assertSame(1000, $t->Nanosecond());
        $t = Time::Unix(PHP_INT_MIN + 1, 999999999);
        $this->assertSame(PHP_INT_MIN + 1, $t->Unix());
        $this->assertSame(999999999, $t->Nanosecond());
        $t = Time::Unix(PHP_INT_MAX, 999999999);
        $this->assertSame(PHP_INT_MAX, $t->Unix());
        $this->assertSame(999999999, $t->Nanosecond());
    }

    public function testMutatorsHandleSubMicroseconds()
    {
        $t = new Time\Time('2024-01-02T03:04:05.123456789Z');
        $t->add(new \DateInterval('PT1S'));
        $this->assertSame(123456789, $t->Nanosecond());
        $t->modify('+1 day');
        $this->assertSame(123456789, $t->Nanosecond());
        $t->setTimezone(new \DateTimeZone('Asia/Tokyo'));
        $this->assertSame(123456789, $t->Nanosecond());

        $c = clone $t;
        $this->assertSame(123456789, $c->Nanosecond());

        $t->setTime(1, 2, 3, 4);
        $this->assertSame(4000, $t->Nanosecond());

        $t = new Time\Time('2024-01-02T03:04:05.123456789Z');
        $t->setTimestamp(5);
        $this->assertSame(0, $t->Nanosecond());
    }

    public function testSerialization()
    {
        $t = new Time\Time('2024-01-02T03:04:05.123456789', new \DateTimeZone('America/Chicago'));

        $u = unserialize(serialize($t));
        $this->assertInstanceOf(Time\Time::class, $u);
        $this->assertTrue($t->Equal($u));
        $this->assertSame('America/Chicago', $u->getTimezone()->getName());

        $e = eval('return ' . var_export($t, true) . ';');
        $this->assertInstanceOf(Time\Time::class, $e);
        $this->assertTrue($t->Equal($e));

        $i = Time\Time::createFromInterface($t);
        $this->assertNotSame($t, $i);
        $this->assertTrue($t->Equal($i));
    }

    public function testSinceUntilDateTimeAcrossMonths()
    {
        $then = (new \DateTimeImmutable())->sub(new \DateInterval('P3M'));
        $expected = (new \DateTimeImmutable())->getTimestamp() - $then->getTimestamp();
        $this->assertEqualsWithDelta($expected, Time::SinceDateTime($then)->Seconds(), 2);
        $this->assertEqualsWithDelta(-$expected, Time::UntilDateTime($then)->Seconds(), 2);
    }

    public function testDurationFromDiffIsExact()
    {
        $a = new \DateTime('2024-01-01T00:00:00.000000Z');
        $b = new \DateTime('2024-04-01T00:00:00.123456Z');
        $this->assertSame(
            91 * 24 * Time::Hour + 123456 * Time::Microsecond,
            Time::Duration($a->diff($b))->Nanoseconds()
        );
    }
}
