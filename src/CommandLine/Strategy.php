<?php

namespace LicCheck\CommandLine;

use InvalidArgumentException;

final class Strategy
{
    /** @var string[] */
    private $authorizedLicenses;
    /** @var string[] */
    private $unauthorizedLicenses;
    /** @var string[] */
    private $authorizedPackages;

    /**
     * @param $authorizedLicenses string[]
     * @param $unauthorizedLicenses string[]
     * @param $authorizedPackages string[]
     */
    public function __construct($authorizedLicenses, $unauthorizedLicenses, $authorizedPackages)
    {
        $this->authorizedLicenses   = $authorizedLicenses;
        $this->unauthorizedLicenses = $unauthorizedLicenses;
        $this->authorizedPackages   = $authorizedPackages;
    }

    /**
     * @param $strategyFile string
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromConfig($strategyFile)
    {
        if (false === is_string($strategyFile)) {
            throw new InvalidArgumentException('"$strategyFile" argument must be a string!');
        }

        $data = json_decode(file_get_contents($strategyFile), true);
        if (false === $data) {
            throw new InvalidArgumentException('"$strategyFile" is not a file!');
        } else if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('"$strategyFile" is not a JSON file valid!');
        }

        if (!array_key_exists('Licenses', $data)) {
            throw new InvalidArgumentException('Missing "Licenses" key!');
        } else if (!array_key_exists('authorized_licenses', $data['Licenses'])) {
            throw new InvalidArgumentException('Missing "authorized_licenses" key!');
        } else if (!array_key_exists('unauthorized_licenses', $data['Licenses'])) {
            throw new InvalidArgumentException('Missing "unauthorized_licenses" key!');
        } else if (!array_key_exists('Authorized Packages', $data)) {
            throw new InvalidArgumentException('Missing "Authorized Packages" key!');
        }

        if (!is_array($data['Licenses']['authorized_licenses'])) {
            throw new InvalidArgumentException('"authorized_licenses" must be an array!');
        } else if (!is_array($data['Licenses']['unauthorized_licenses'])) {
            throw new InvalidArgumentException('"authorized_licenses" must be an array!');
        } else if (!is_array($data['Authorized Packages'])) {
            throw new InvalidArgumentException('"Authorized Packages" must be an array!');
        }

        return new self(
            $data['Licenses']['authorized_licenses'],
            $data['Licenses']['unauthorized_licenses'],
            $data['Authorized Packages']
        );
    }

    /** @return string[] */
    public function authorizedLicenses()
    {
        return $this->authorizedLicenses;
    }

    /** @return string[] */
    public function unauthorizedLicenses()
    {
        return $this->unauthorizedLicenses;
    }

    /** @return string[] */
    public function authorizedPackages()
    {
        return $this->authorizedPackages;
    }
}
