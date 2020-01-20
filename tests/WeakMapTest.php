<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WeakMapTest extends TestCase
{
    public function testArrayAccess() : void
    {
        $weakMap = new WeakMap();

        $a = new stdClass;
        $b = new stdClass;

        $weakMap[$a] = 123;
        self::assertTrue(isset($weakMap[$a]));
        self::assertSame(123, $weakMap[$a]);

        $weakMap[$b] = 456;
        self::assertTrue(isset($weakMap[$b]));
        self::assertSame(456, $weakMap[$b]);

        unset($weakMap[$a]);
        self::assertFalse(isset($weakMap[$a]));
        self::assertTrue(isset($weakMap[$b]));

        unset($weakMap[$b]);
        self::assertFalse(isset($weakMap[$a]));
        self::assertFalse(isset($weakMap[$b]));
    }

    public function testReusedObjectId() : void
    {
        $weakMap = new WeakMap();

        $a = new stdClass;
        $b = new stdClass;

        $weakMap[$a] = 123;
        $weakMap[$b] = 456;

        self::assertTrue(isset($weakMap[$a]));
        self::assertTrue(isset($weakMap[$b]));

        unset($a, $b);

        // typically reusing the same spl_object_id as the old ones
        $a = new stdClass;
        $b = new stdClass;

        self::assertFalse(isset($weakMap[$a]));
        self::assertFalse(isset($weakMap[$b]));
    }

    public function testAccessingUnknownObjectThrowsError() : void
    {
        $weakMap = new WeakMap();

        $a = new stdClass;

        self::expectException(Error::class);
        $weakMap[$a];
    }

    public function testCount() : void
    {
        $weakMap = new WeakMap();

        $a = new stdClass;
        $b = new stdClass;

        self::assertSame(0, $weakMap->count());

        $weakMap[$a] = 123;
        self::assertSame(1, $weakMap->count());

        $weakMap[$b] = 456;
        self::assertSame(2, $weakMap->count());

        unset($a); // implicit (WeakReference gone)
        self::assertSame(1, $weakMap->count());

        unset($weakMap[$b]); // explicit (ArrayAccess)
        self::assertSame(0, $weakMap->count());
    }

    public function testDataIsDestroyedWhenObjectIsRemoved() : void
    {
        $weakMap = new WeakMap();

        $k = new stdClass;
        $v = new stdClass;
        $r = WeakReference::create($v);

        self::assertSame(0, $weakMap->count());

        $weakMap[$k] = $v;

        // Remove our reference to $v, which is now only used as a value in the WeakMap.
        unset($v);

        self::assertSame(1, $weakMap->count());
        self::assertNotNull($r->get());

        // Removing the object key $k should force the WeakMap to clean up associated data $v;
        // the WeakReference to $v should therefore not point anywhere anymore.
        unset($weakMap[$k]);

        self::assertSame(0, $weakMap->count());
        self::assertNull($r->get());
    }

    public function testDataIsDestroyedWhenObjectIsGarbageCollected() : void
    {
        $weakMap = new WeakMap();

        $k = new stdClass;
        $v = new stdClass;
        $r = WeakReference::create($v);

        self::assertSame(0, $weakMap->count());

        $weakMap[$k] = $v;

        // Remove our reference to $v, which is now only used as a value in the WeakMap.
        unset($v);

        self::assertSame(1, $weakMap->count());
        self::assertNotNull($r->get());

        // Garbage-collecting the object key $k should force the WeakMap to clean up associated data $v;
        // the WeakReference to $v should therefore not point anywhere anymore.
        unset($k);

        self::assertSame(0, $weakMap->count());
        self::assertNull($r->get());
    }

    public function testHousekeeping() : void
    {
        if (version_compare(PHP_VERSION, '8') >= 0) {
            self::markTestSkipped("This test is internal to the polyfill, and will fail with PHP 8's WeakMap");
        }

        $weakMap = new WeakMap();

        $k = new stdClass;
        $v = new stdClass;
        $r = WeakReference::create($v);

        $unknownObject = new stdClass;

        $weakMap[$k] = $v;

        unset($k);
        unset($v);

        for ($i = 0; $i < 99; $i++) {
            self::assertNotNull($r->get());
            isset($weakMap[$unknownObject]);
        }

        self::assertNull($r->get());
    }
}
