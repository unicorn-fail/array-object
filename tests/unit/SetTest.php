<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UnicornFail\ArrayObject\Set;

class SetTest extends TestCase
{
    public function testUnique(): void
    {
        $set = new Set(['foo']);

        $this->assertCount(1, $set);

        $set->add('foo');

        $this->assertCount(1, $set);

        $set->add('bar');

        $this->assertCount(2, $set);
    }
}
