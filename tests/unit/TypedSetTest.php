<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Tests\Unit;

use Consistence\InvalidArgumentTypeException;
use PHPUnit\Framework\TestCase;
use Stringable;
use UnicornFail\ArrayObject\TypedSet;

class TypedSetTest extends TestCase
{
    public function testConstructor(): void
    {
        $this->expectException(InvalidArgumentTypeException::class);

        new TypedSet('\Stringable', 'foo');
    }

    public function testAdd(): void
    {
        $this->expectException(InvalidArgumentTypeException::class);

        $set = new TypedSet('\Stringable');
        $set->add('foo');
    }

    public function testOffsetSet(): void
    {
        $this->expectException(InvalidArgumentTypeException::class);

        $set   = new TypedSet('\Stringable');
        $set[] = 'foo';
    }

    public function testMap(): void
    {
        $class = new class implements Stringable {
            public function __toString(): string
            {
                return 'bar';
            }
        };

        $set = new TypedSet('\Stringable');
        $set->add($class);

        $this->assertSame('bar', (string) $set);

        $mapped = $set->map(static function (): Stringable {
            return new class implements Stringable {
                public function __toString(): string
                {
                    return 'baz';
                }
            };
        });

        $this->assertSame('baz', (string) $mapped);

        $this->assertSame('bar', (string) $set);

        $this->expectException(InvalidArgumentTypeException::class);

        $set->map(static function (): string {
            return 'foo';
        });
    }

    public function testUnserialize(): void
    {
        $set = new TypedSet('string');
        $set->add('foo');

        $serialized = 'O:32:"UnicornFail\ArrayObject\TypedSet":4:{s:55:" UnicornFail\ArrayObject\AbstractTypedArray __valueType";s:6:"string";s:54:" UnicornFail\ArrayObject\AbstractArrayObject __storage";a:1:{i:0;s:3:"foo";}s:18:" * __iteratorClass";s:11:"ArrayObject";s:14:" * __separator";s:2:", ";}';

        $this->assertSame($serialized, \serialize($set));

        $unserialized = \unserialize($serialized);

        $this->assertInstanceOf(TypedSet::class, $unserialized);
        $this->assertEquals($unserialized, $set);
    }
}
