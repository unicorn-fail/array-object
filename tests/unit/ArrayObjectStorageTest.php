<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UnicornFail\ArrayObject\ArrayObjectStorage;
use UnicornFail\ArrayObject\ArrayObjectStorageInterface;
use stdClass;

class ArrayObjectStorageTest extends TestCase
{
    public function testInterface(): void
    {
        $storage = new ArrayObjectStorage();

        $this->assertTrue($storage instanceof ArrayObjectStorageInterface);
    }

    public function testClear(): void
    {
        $storage = new ArrayObjectStorage();

        $storage['foo'] = 'bar';

        $this->assertCount(1, $storage);

        $storage->clear();

        $this->assertCount(0, $storage);
    }

    public function testCount(): void
    {
        $storage = new ArrayObjectStorage();

        $this->assertCount(0, $storage);
    }

    public function testCurrent(): void
    {
        $storage = new ArrayObjectStorage();

        $this->assertNull($storage->current());

        $storage['foo'] = 'bar';

        $storage[new stdClass()] = 'baz';

        $this->assertSame('bar', $storage->current());

        $storage->next();

        $storage['qux'] = true;

        $this->assertSame('baz', $storage->current());
    }

    public function testGetKeyObjectById(): void
    {
        $storage = new ArrayObjectStorage();

        $this->assertNull($storage->getKeyObjectById(-1));

        $key           = new stdClass();
        $id            = \spl_object_id($key);
        $storage[$key] = 'baz';

        $this->assertSame($id, $storage->key());

        unset($storage[$key]);
        $key           = new stdClass();
        $storage[$key] = 'baz';

        $this->assertNotSame($id, $storage->key());
    }

    public function testKey(): void
    {
        $storage = new ArrayObjectStorage();

        $this->assertNull($storage->key());

        $storage['foo'] = 'bar';

        $key = new stdClass();

        $storage[$key] = 'baz';

        $this->assertSame('foo', $storage->key());

        $storage->next();

        $this->assertSame(\spl_object_id($key), $storage->key());
    }

    public function testKeys(): void
    {
        $storage = new ArrayObjectStorage();

        $storage['foo'] = 'bar';

        $key           = new stdClass();
        $storage[$key] = 'baz';

        $this->assertSame(['foo', $key], $storage->keys());
        $this->assertSame(['foo', \spl_object_id($key)], $storage->keys(false));
    }

    public function testOffset(): void
    {
        $storage = new ArrayObjectStorage();

        // offsetSet
        $storage['foo'] = 'bar';
        $this->assertCount(1, $storage);

        $key           = new stdClass();
        $storage[$key] = 'baz';
        $this->assertCount(2, $storage);

        $storage[3] = 4;
        $this->assertCount(3, $storage);

        // offsetExists
        $this->assertTrue(isset($storage['foo']));
        $this->assertTrue(isset($storage[$key]));
        $this->assertTrue(isset($storage[3]));
        $this->assertFalse(isset($storage['qux']));

        // offsetGet
        $this->assertSame('bar', $storage['foo']);
        $this->assertSame('baz', $storage[$key]);
        $this->assertSame(4, $storage[3]);
        $this->assertNull($storage['qux']);

        unset($storage['foo']);
        $this->assertNull($storage['foo']);
        $this->assertCount(2, $storage);
    }

    public function testRewind(): void
    {
        $storage = new ArrayObjectStorage();

        $storage['foo'] = 'bar';

        $storage[new stdClass()] = 'baz';

        $storage->next();

        $storage['qux'] = true;

        $this->assertSame('baz', $storage->current());

        $storage->rewind();

        $this->assertSame('bar', $storage->current());
    }

    public function testSeek(): void
    {
        $storage = new ArrayObjectStorage();

        $storage['foo'] = 'bar';

        $storage[new stdClass()] = 'baz';

        $storage['qux'] = true;

        $storage->seek(4);
        $this->assertNull($storage->current());

        $storage->seek(3);
        $this->assertNull($storage->current());

        $storage->seek(2);
        $this->assertTrue($storage->current());

        $storage->seek(1);
        $this->assertSame('baz', $storage->current());

        $storage->seek(0);
        $this->assertSame('bar', $storage->current());
    }

    public function testSerialize(): void
    {
        $key           = new stdClass();
        $storage       = new ArrayObjectStorage();
        $storage[$key] = 'bar';
        $serialized    = \serialize($storage);
        $expected      = 'C:42:"UnicornFail\ArrayObject\ArrayObjectStorage":85:{O:16:"SplObjectStorage":2:{i:0;a:2:{i:0;O:8:"stdClass":0:{}i:1;s:3:"bar";}i:1;a:0:{}}}';
        $this->assertSame($expected, $serialized);

        /** @var ArrayObjectStorage $unserialized */
        $unserialized = \unserialize($serialized);

        $this->assertInstanceOf(ArrayObjectStorage::class, $unserialized);

        $expected = $storage->toArray();
        $actual   = $unserialized->toArray();
        foreach ($actual as $key => $value) {
            $expectedKey = \array_search($value, $expected, true);
            if ($object = $unserialized->getKeyObjectById($key)) {
                $this->assertEquals($storage->getKeyObjectById($expectedKey), $object);
            }

            $this->assertSame($value, $expected[$expectedKey]);
        }
    }

    public function testValid(): void
    {
        $storage = new ArrayObjectStorage();
        $this->assertFalse($storage->valid());

        $storage['foo'] = 'bar';
        $this->assertTrue($storage->valid());

        $storage->next();
        $this->assertFalse($storage->valid());

        $storage->rewind();
        $this->assertTrue($storage->valid());
    }

    public function testValues(): void
    {
        $storage = new ArrayObjectStorage();

        $storage['foo'] = 'bar';

        $value = new stdClass();

        $storage['baz'] = $value;

        $storage['qux'] = true;

        $this->assertSame(['bar', $value, true], $storage->values());
    }
}
