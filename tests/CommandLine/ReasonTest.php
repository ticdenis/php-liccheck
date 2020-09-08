<?php

namespace Tests\CommandLine;

use LicCheck\CommandLine\Reason;
use PHPUnit\Framework\TestCase;

final class ReasonTest extends TestCase
{
    /**
     * @dataProvider data_provider_invalid_reason
     * @param $reason string
     * @return void
     */
    public function testCannotBeInstantiatedFromInvalidReason($reason)
    {
        static::setExpectedException('\InvalidArgumentException', 'Invalid Reason', 0);
        new Reason($reason);
    }

    /**
     * @dataProvider data_provider_reason
     * @param $reason string
     * @return void
     */
    public function testCanBeInstantiatedWith($reason)
    {
        static::assertEquals($reason, (new Reason($reason))->__toString());
    }

    /** @return string[][] */
    public function data_provider_reason()
    {
        return [
            ['OK'],
            ['UNAUTHORIZED'],
            ['UNKNOWN'],
        ];
    }

    /** @return string[][] */
    public function data_provider_invalid_reason()
    {
        return [
            ['INVALID'],
        ];
    }
}
