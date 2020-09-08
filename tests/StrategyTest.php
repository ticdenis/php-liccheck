<?php

namespace Tests;

use LicCheck\Strategy;
use PHPUnit\Framework\TestCase;

final class StrategyTest extends TestCase
{
    /** @return void */
    public function testFromComposerJSON()
    {
        // TODO
    }

    /** @return void */
    public function testFromConfig()
    {
        $strategyFile       = __DIR__ . '/../license_strategy.json';
        $strategy           = Strategy::fromConfig($strategyFile);
        $authorizedLicenses = ['BSD-3-Clause', 'MIT'];
        static::assertCount(count($authorizedLicenses), $strategy->authorizedLicenses());
        static::assertEquals($authorizedLicenses[0], $strategy->authorizedLicenses()[0]);
        static::assertEquals($authorizedLicenses[1], $strategy->authorizedLicenses()[1]);
        $unauthorizedLicenses = ['propietary'];
        static::assertCount(count($unauthorizedLicenses), $strategy->unauthorizedLicenses());
        static::assertEquals($unauthorizedLicenses[0], $strategy->unauthorizedLicenses()[0]);
        $authorizedPackages = ['ticdenis/liccheck'];
        static::assertCount(count($authorizedPackages), $strategy->authorizedPackages());
        static::assertEquals($authorizedPackages[0], $strategy->authorizedPackages()[0]);
    }
}
