<?php

namespace Tests\CommandLine;

use LicCheck\CommandLine\Level;
use PHPUnit\Framework\TestCase;

final class LevelTest extends TestCase
{
    /**
     * @dataProvider data_provider_invalid_level
     * @param $level string
     * @return void
     */
    public function testCannotBeInstantiatedFromInvalidLevel($level)
    {
        static::setExpectedException('\InvalidArgumentException', 'Invalid Level', 0);
        new Level($level);
    }

    /**
     * @dataProvider data_provider_level
     * @param $level string
     * @return void
     */
    public function testCanBeInstantiatedStartingWith($level)
    {
        static::assertEquals($level, Level::starting($level)->__toString());
    }

    /**
     * @dataProvider data_provider_invalid_level
     * @param $level string
     * @return void
     */
    public function testCanNotBeInstantiatedStartingWith($level)
    {
        static::setExpectedException('\InvalidArgumentException', sprintf('No level starting with %s', $level), 0);
        static::assertEquals($level, Level::starting($level)->__toString());
    }

    /** @return string[][] */
    public function data_provider_level()
    {
        return [
            ['STANDARD'],
            ['CAUTIOUS'],
            ['PARANOID'],
        ];
    }

    /** @return string[][] */
    public function data_provider_invalid_level()
    {
        return [
            ['INVALID'],
        ];
    }
}
