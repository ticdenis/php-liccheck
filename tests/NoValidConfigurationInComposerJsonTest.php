<?php

namespace Tests;

use LicCheck\NoValidConfigurationInComposerJson;
use PHPUnit\Framework\TestCase;

final class NoValidConfigurationInComposerJsonTest extends TestCase
{
    /** @return void */
    public function testShouldBeAnInvalidArgumentException()
    {
        static::setExpectedException('\InvalidArgumentException', 'Test', 0);
        throw new NoValidConfigurationInComposerJson('Test');
    }
}
