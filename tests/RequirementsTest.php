<?php

namespace Tests;

use LicCheck\Requirements;
use PHPUnit\Framework\TestCase;

final class RequirementsTest extends TestCase
{
    /** @return void */
    public function testParse()
    {
        $requirements = array_keys(Requirements::parse('composer', true));
        $dependencies = ['psr/log', 'symfony/console', 'symfony/debug', 'symfony/polyfill-mbstring'];
        static::assertCount(count($dependencies), $requirements);
        static::assertEquals($dependencies[0], $requirements[0]);
        static::assertEquals($dependencies[1], $requirements[1]);
        static::assertEquals($dependencies[2], $requirements[2]);
        static::assertEquals($dependencies[3], $requirements[3]);
    }
}
