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
        $dependencies = [];
        static::assertCount(count($dependencies), $requirements);
    }
}
