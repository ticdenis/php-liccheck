<?php

namespace LicCheck;

use LicCheck\CommandLine\Strategy;

if (in_array('-h', $argv, true) || in_array('--help', $argv, true)) {
    echo(sprintf(
        "usage: liccheck [-h] [-s [STRATEGY_JSON_FILE=license_strategy.json]] [-r [COMPOSER_PATH=composer]]
            
Check license of packages and their dependencies.

optional arguments:
  -h, --help                                    show this help message and exit
  -s [STRATEGY_JSON_FILE=license_strategy.json] strategy json file
  -r [COMPOSER_PATH=composer]                   path/to/composer binary
"));
    exit(0);
}

$defaultLicenseStrategyPath = __DIR__ . '/../license_strategy.json';
$licensesIndex              = (int)array_search('-s', $argv, true);
$licensesPath               = $licensesIndex <= 0 ? $defaultLicenseStrategyPath : $argv[$licensesIndex + 1];

$strategy = Strategy::fromConfig($licensesPath);

$dependenciesIndex = (int)array_search('-r', $argv, true);
$command           = sprintf('%s licenses --format json', $dependenciesIndex <= 0 ? 'composer' : $argv[$dependenciesIndex + 1]);
exec($command, $returnContent, $returnCode);
if (0 !== ((int)$returnCode)) {
    echo(sprintf('Failed executing "%s" command%s', $command, PHP_EOL));
    echo(sprintf('KO%s', PHP_EOL));
    exit(1);
}

$dependencies = json_decode(join('', $returnContent), true);
if (false === $dependencies) {
    echo(sprintf('Error decoding result of "%s" command%s', $command, PHP_EOL));
    echo(sprintf('KO%s', PHP_EOL));
    exit(1);
} else if (!array_key_exists('dependencies', $dependencies)) {
    echo(sprintf('Need "%s" key in result of "%s" command%s', 'dependencies', $command, PHP_EOL));
    echo(sprintf('KO%s', PHP_EOL));
    exit(1);
} else {
    $dependencies = $dependencies['dependencies'];
}

// Remove Authorized Packages from dependencies
foreach ($strategy->authorizedPackages() as $authorizedPackage) {
    foreach ($dependencies as $dependency => $value) {
        if ($authorizedPackage === $dependency) {
            unset($dependencies[$dependency]);
        }
    }
}

// Validate Authorized Licenses and Unauthorized Licenses
$intersection = array_intersect($strategy->authorizedLicenses(), $strategy->unauthorizedLicenses());
if (!empty($intersection)) {
    echo(sprintf(
        'Duplicated Authorized and Unauthorized Licenses%s%s',
        PHP_EOL,
        json_encode($intersection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ));
    echo(sprintf('KO%s', PHP_EOL));
    exit(1);
}

// Prepare Unauthorized Licenses
$unauthorizedLicenses = $strategy->unauthorizedLicenses();
foreach ($dependencies as $dependency) {
    $unauthorizedLicenses = array_merge($unauthorizedLicenses, $dependency['license']);
}
$unauthorizedLicenses = array_unique($unauthorizedLicenses);

// Remove Authorized Licenses from dependencies
foreach ($strategy->authorizedLicenses() as $authorizedLicense) {
    if (in_array($authorizedLicense, $unauthorizedLicenses, true)) {
        unset($unauthorizedLicenses[array_search($authorizedLicense, $unauthorizedLicenses)]);
    }
    foreach ($dependencies as $dependency => $value) {
        if (in_array($authorizedLicense, $value['license'], true)) {
            unset($dependencies[$dependency]);
            break;
        }
    }
}

// Remove Unauthorized Licenses found from dependencies and add to $unauthorizedLicensesFound array
$unauthorizedLicensesFound = [];
foreach ($unauthorizedLicenses as $unauthorizedLicense) {
    foreach ($dependencies as $dependency => $value) {
        if (in_array($unauthorizedLicense, $value['license'], true)) {
            $unauthorizedLicensesFound[$unauthorizedLicense][] = $dependency;
            unset($dependencies[$dependency]);
        }
    }
}

if (!empty($unauthorizedLicensesFound)) {
    echo(sprintf(
        'Unauthorized Licenses found%s%s',
        PHP_EOL,
        json_encode($unauthorizedLicensesFound, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ));
    echo(sprintf('KO%s', PHP_EOL));
    exit(1);
}

echo(sprintf('OK%s', PHP_EOL));
exit(0);
