<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Tests\Unit;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use UnicornFail\ArrayObject\AbstractObjectMap;
use UnicornFail\ArrayObject\ArrayObject;
use UnicornFail\ArrayObject\AssociativeArrayObjectInterface;
use UnicornFail\ArrayObject\Exception\EmptyException;
use UnicornFail\ArrayObject\UniqueArrayObjectInterface;

class ArrayObjectTest extends TestCase
{
    /**
     * Gets returns a proxy for any method of an object, regardless of scope
     *
     * @param object $object     Any object
     * @param string $methodName The name of the method you want to proxy
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function getObjectMethod(object $object, string $methodName): Closure
    {
        $ref = new ReflectionMethod($object, $methodName);
        $ref->setAccessible(true);

        return static function () use ($object, $ref) {
            return $ref->invokeArgs($object, \func_get_args());
        };
    }

    public function testConstruct(): void
    {
        $mock = $this->getMockBuilder(AbstractObjectMap::class);
        $mock->onlyMethods(['add']);

        $array = $mock->getMockForAbstractClass();
        $array->expects($this->never())->method('add');

        $array = $this->getMockBuilder(AbstractObjectMap::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['add'])
            ->setConstructorArgs([['foo']])
            ->getMockForAbstractClass();

        $array->expects($this->once())->method('add');

        $this->getObjectMethod($array, '__construct')(['foo']);
    }

    public function testAdd(): void
    {
        $array = new ArrayObject();
        $this->assertCount(0, $array);
        $this->assertSame([], $array->toArray());

        $array->add('foo');
        $this->assertCount(1, $array);
        $this->assertSame(['foo'], $array->toArray());

        $array->add('bar', 'baz');
        $this->assertCount(3, $array);
        $this->assertSame(['foo', 'bar', 'baz'], $array->toArray());

        $array->add(...['foo']);
        $this->assertCount(4, $array);
        $this->assertSame(['foo', 'bar', 'baz', 'foo'], $array->toArray());

        $array->add(['bar']);
        $this->assertCount(5, $array);
        $this->assertSame(['foo', 'bar', 'baz', 'foo', 'bar'], $array->toArray());

        $array = new class extends ArrayObject implements AssociativeArrayObjectInterface {
        };

        $this->assertCount(0, $array);
        $this->assertSame([], $array->toArray());

        $array->add('foo');
        $this->assertCount(1, $array);
        $this->assertSame(['foo'], $array->toArray());

        $array->add(['bar' => 'baz']);
        $this->assertCount(2, $array);
        $this->assertSame(['foo', 'bar' => 'baz'], $array->toArray());

        $array = new class extends ArrayObject implements UniqueArrayObjectInterface {
        };

        $this->assertCount(0, $array);
        $this->assertSame([], $array->toArray());

        $array->add('foo');
        $this->assertCount(1, $array);
        $this->assertSame(['foo'], $array->toArray());

        $array->add('bar');
        $this->assertCount(2, $array);
        $this->assertSame(['foo', 'bar'], $array->toArray());

        $array->add('foo');
        $this->assertCount(2, $array);
        $this->assertSame(['foo', 'bar'], $array->toArray());
    }

    public function testClear(): void
    {
        $array = new ArrayObject(['foo']);
        $this->assertCount(1, $array);
        $this->assertSame(['foo'], $array->toArray());

        $array->clear();
        $this->assertCount(0, $array);
        $this->assertSame([], $array->toArray());
    }

    public function testContains(): void
    {
        $array = new ArrayObject(['foo']);
        $this->assertTrue($array->contains('foo'));

        $array = new ArrayObject(['1']);
        $this->assertFalse($array->contains(1));

        $array = new ArrayObject(['1']);
        $this->assertTrue($array->contains(1, false));
    }

    public function testCount(): void
    {
        $array = new ArrayObject();
        $this->assertCount(0, $array);

        $array->add('foo');
        $this->assertCount(1, $array);
    }

    public function testFilter(): void
    {
        $array = new ArrayObject([1, 2, 3, 4, 5, 6]);

        $filtered = $array->filter(static function ($value): bool {
            return (int) $value % 2 === 0;
        });

        $this->assertNotSame($array, $filtered);
        $this->assertSame([1, 2, 3, 4, 5, 6], $array->toArray());
        $this->assertSame([2, 4, 6], $filtered->toArray());

        $array = new class ([1, 2, 3, 4, 5, 6]) extends ArrayObject implements AssociativeArrayObjectInterface {
        };

        $filtered = $array->filter(static function ($value): bool {
            return (int) $value % 2 === 0;
        });

        $this->assertNotSame($array, $filtered);
        $this->assertSame([1, 2, 3, 4, 5, 6], $array->toArray());
        $this->assertSame([1 => 2, 3 => 4, 5 => 6], $filtered->toArray());
    }

    public function testFirstEmpty(): void
    {
        $this->expectException(EmptyException::class);
        $array = new ArrayObject();
        $array->first();
    }

    public function testFirst(): void
    {
        $array = new ArrayObject(['foo', 'bar', 'baz']);
        $this->assertSame('foo', $array->first());

        $array = new ArrayObject([null, 'bar', 'baz']);
        $this->assertNull($array->first());

        $array = new ArrayObject([false, 'bar', 'baz']);
        $this->assertFalse($array->first());
    }

    public function testFlatten(): void
    {
        $values    = [1, [2, [3, [4, [5, [6]]]]]];
        $expected  = [1, 2, 3, 4, 5, 6];
        $array     = new ArrayObject($values);
        $flattened = $array->flatten();

        $this->assertNotSame($array, $flattened);
        $this->assertSame($values, $array->toArray());
        $this->assertSame($expected, $flattened->toArray());

        $values    = ['one' => 1, ['two' => 2, ['three' => 3, ['four' => 4, ['five' => 5, ['six' => 6]]]]]];
        $expected  = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6];
        $array     = new class ($values) extends ArrayObject implements AssociativeArrayObjectInterface {
        };
        $flattened = $array->flatten();

        $this->assertNotSame($array, $flattened);
        $this->assertSame($values, $array->toArray());
        $this->assertSame($expected, $flattened->toArray());
    }

    public function testGetIterator(): void
    {
        $expected = ['foo', 'bar', 'baz'];

        $array = new ArrayObject($expected);

        $i = 0;
        foreach ($array as $key => $value) {
            $this->assertSame($i++, $key);
            $this->assertSame($expected[$key], $value);
        }
    }

    public function testIndexOf(): void
    {
        $array = new ArrayObject(['1', 'bar']);
        $this->assertSame(1, $array->indexOf('bar'));
        $this->assertNull($array->indexOf(1));
        $this->assertSame(0, $array->indexOf(1, false));
    }

    public function testIsEmpty(): void
    {
        $array = new ArrayObject();
        $this->assertTrue($array->isEmpty());

        $array = new ArrayObject('foo');
        $this->assertFalse($array->isEmpty());
    }

    public function testJoin(): void
    {
        $array = new ArrayObject(['foo', 'bar']);
        $this->assertSame('foo, bar', $array->join());
        $this->assertSame('foo|bar', $array->join('|'));
    }

    public function testJsonSerialize(): void
    {
        $array = new ArrayObject(['foo', 'bar']);
        $this->assertSame('["foo","bar"]', \json_encode($array));
        $this->assertSame("[\n    \"foo\",\n    \"bar\"\n]", \json_encode($array, JSON_PRETTY_PRINT));
    }

    public function testLastEmpty(): void
    {
        $this->expectException(EmptyException::class);
        $array = new ArrayObject();
        $array->last();
    }

    public function testLast(): void
    {
        $array = new ArrayObject(['foo', 'bar', 'baz']);
        $this->assertSame('baz', $array->last());

        $array = new ArrayObject(['foo', 'bar', null]);
        $this->assertNull($array->last());

        $array = new ArrayObject(['foo', 'bar', false]);
        $this->assertFalse($array->last());
    }

    public function testMap(): void
    {
        $i        = 0;
        $values   = [1, 2, 3, 4, 5, 6];
        $expected = [2, 4, 6, 8, 10, 12];
        $array    = new ArrayObject($values);
        $mapped   = $array->map(function ($value, $key) use (&$i) {
            $this->assertSame($i++, $key);

            return (int) $value * 2;
        });

        $this->assertNotSame($array, $mapped);
        $this->assertSame($values, $array->toArray());
        $this->assertSame($expected, $mapped->toArray());

        $i            = 0;
        $values       = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6];
        $expected     = ['one' => 2, 'two' => 4, 'three' => 6, 'four' => 8, 'five' => 10, 'six' => 12];
        $expectedKeys = \array_keys($expected);
        $array        = new class ($values) extends ArrayObject implements AssociativeArrayObjectInterface {
        };
        $mapped       = $array->map(function ($value, $key) use (&$i, $expectedKeys) {
            /** @psalm-suppress MixedArrayOffset */
            $this->assertSame($expectedKeys[$i++], $key);

            return (int) $value * 2;
        });

        $this->assertNotSame($array, $mapped);
        $this->assertSame($values, $array->toArray());
        $this->assertSame($expected, $mapped->toArray());
    }

    public function testOffsetExists(): void
    {
        $array = new ArrayObject(['foo', 'bar']);

        $this->assertTrue(isset($array[0]));
        $this->assertTrue(isset($array[1]));
        $this->assertFalse(isset($array[2]));

        $array = new class (['foo' => 'bar']) extends ArrayObject implements AssociativeArrayObjectInterface {
        };

        $this->assertTrue(isset($array['foo']));
        $this->assertFalse(isset($array['bar']));
    }

    public function testOffsetGet(): void
    {
        $array = new ArrayObject(['foo', 'bar']);

        $this->assertSame('foo', $array[0]);
        $this->assertSame('bar', $array[1]);
        $this->assertNull($array[2]);

        $array = new class (['foo' => 'bar']) extends ArrayObject implements AssociativeArrayObjectInterface {
        };

        $this->assertSame('bar', $array['foo']);
        $this->assertNull($array['bar']);
    }

    public function testOffsetSet(): void
    {
        $array   = new ArrayObject();
        $array[] = 'foo';
        $this->assertCount(1, $array);
        $this->assertSame(['foo'], $array->toArray());

        $array['foo'] = 'bar';
        $this->assertCount(2, $array);
        $this->assertSame(['foo', 'bar'], $array->toArray());

        $array[] = 'foo';
        $this->assertCount(3, $array);
        $this->assertSame(['foo', 'bar', 'foo'], $array->toArray());

        $array = new class extends ArrayObject implements AssociativeArrayObjectInterface {
        };

        $array['foo'] = 'bar';
        $this->assertCount(1, $array);
        $this->assertSame(['foo' => 'bar'], $array->toArray());

        $array['bar'] = 'baz';
        $this->assertCount(2, $array);
        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $array->toArray());

        $array = new class extends ArrayObject implements UniqueArrayObjectInterface {
        };

        $array[] = 'foo';
        $this->assertCount(1, $array);
        $this->assertSame(['foo'], $array->toArray());

        $array[] = 'foo';
        $this->assertCount(1, $array);
        $this->assertSame(['foo'], $array->toArray());
    }

    public function testOffsetUnset(): void
    {
        $array = new ArrayObject(['foo', 'bar']);

        $this->assertSame('foo', $array[0]);
        $this->assertSame('bar', $array[1]);

        unset($array[0]);
        $this->assertSame('bar', $array[0]);
        $this->assertNull($array[1]);
        $this->assertSame(['bar'], $array->toArray());

        $array = new class (['foo' => 'bar', 'bar' => 'baz']) extends ArrayObject implements AssociativeArrayObjectInterface {
        };

        $this->assertSame('bar', $array['foo']);
        $this->assertSame('baz', $array['bar']);
        $this->assertNull($array['baz']);

        unset($array['foo']);
        $this->assertSame(['bar' => 'baz'], $array->toArray());
    }

    public function testPopEmpty(): void
    {
        $this->expectException(EmptyException::class);
        $array = new ArrayObject();
        $array->pop();
    }

    public function testPop(): void
    {
        $array = new ArrayObject(['foo', 'bar', 'baz']);
        $this->assertSame('baz', $array->pop());
        $this->assertSame(['foo', 'bar'], $array->toArray());

        $array = new ArrayObject(['foo', 'bar', null]);
        $this->assertNull($array->pop());
        $this->assertSame(['foo', 'bar'], $array->toArray());

        $array = new ArrayObject(['foo', 'bar', false]);
        $this->assertFalse($array->pop());
        $this->assertSame(['foo', 'bar'], $array->toArray());
    }

    public function testRemove(): void
    {
        $array = new ArrayObject(['foo', 'bar', 'baz']);
        $this->assertTrue($array->remove('bar'));
        $this->assertSame(['foo', 'baz'], $array->toArray());

        $array = new ArrayObject(['1', '2', '3']);
        $this->assertFalse($array->remove(2));
        $this->assertSame(['1', '2', '3'], $array->toArray());

        $array = new ArrayObject(['1', '2', '3']);
        $this->assertTrue($array->remove(2, false));
        $this->assertSame(['1', '3'], $array->toArray());
    }

    public function testReplace(): void
    {
        $array = new ArrayObject(['foo', 'bar', 'baz']);
        $this->assertTrue($array->replace('bar', 'qux'));
        $this->assertSame(['foo', 'qux', 'baz'], $array->toArray());

        $array = new ArrayObject(['1', '2', '3']);
        $this->assertFalse($array->replace(2, 4));
        $this->assertSame(['1', '2', '3'], $array->toArray());

        $array = new ArrayObject(['1', '2', '3']);
        $this->assertTrue($array->replace(2, 4, false));
        $this->assertSame(['1', 4, '3'], $array->toArray());
    }

    public function testSerialize(): void
    {
        $array    = new ArrayObject(['foo', 'bar', 'baz']);
        $expected = 'O:35:"UnicornFail\ArrayObject\ArrayObject":3:{s:54:" UnicornFail\ArrayObject\AbstractArrayObject __storage";a:3:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";}s:18:" * __iteratorClass";s:11:"ArrayObject";s:14:" * __separator";s:2:", ";}';
        $this->assertSame($expected, \serialize($array));
    }

    public function testSetSeparator(): void
    {
        $array = new ArrayObject(['foo', 'bar']);
        $array->setSeparator('|');
        $this->assertSame('foo|bar', $array->join());
        $this->assertSame('foo, bar', $array->join(', '));
    }

    public function testShiftEmpty(): void
    {
        $this->expectException(EmptyException::class);
        $array = new ArrayObject();
        $array->shift();
    }

    public function testShift(): void
    {
        $array = new ArrayObject(['foo', 'bar', 'baz']);
        $this->assertSame('foo', $array->shift());
        $this->assertSame(['bar', 'baz'], $array->toArray());

        $array = new ArrayObject([null, 'bar', 'baz']);
        $this->assertNull($array->shift());
        $this->assertSame(['bar', 'baz'], $array->toArray());

        $array = new ArrayObject([false, 'bar', 'baz']);
        $this->assertFalse($array->shift());
        $this->assertSame(['bar', 'baz'], $array->toArray());
    }

    public function testToArray(): void
    {
        $array = new ArrayObject(['foo', 'bar', 'baz']);
        $this->assertSame(['foo', 'bar', 'baz'], $array->toArray());
    }

    public function testToString(): void
    {
        $this->assertSame('foo, bar', (string) new ArrayObject(['foo', 'bar']));
    }

    public function testUnserialize(): void
    {
        $array = \unserialize('O:35:"UnicornFail\ArrayObject\ArrayObject":3:{s:54:" UnicornFail\ArrayObject\AbstractArrayObject __storage";a:3:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;s:3:"baz";}s:18:" * __iteratorClass";s:11:"ArrayObject";s:14:" * __separator";s:2:", ";}');

        $this->assertTrue($array instanceof ArrayObject);
        $this->assertCount(3, $array);
        $this->assertSame(['foo', 'bar', 'baz'], $array->toArray());
    }
}
