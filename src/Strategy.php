<?php

namespace LicCheck;

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
     * @return self
     * @throws NoValidConfigurationInComposerJson
     */
    public static function fromComposerJSON()
    {
        $filename = __DIR__ . '/../../../composer.json';
        if (!file_exists($filename)) {
            throw new NoValidConfigurationInComposerJson('No such file or directory in ' . $filename);
        }
        $data = json_decode(file_get_contents($filename), true);
        if (false === $data) {
            throw new NoValidConfigurationInComposerJson('"composer.json" file not found!');
        } else if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NoValidConfigurationInComposerJson('"composer.json" is not a JSON file valid!');
        } else if (!array_key_exists('extra', $data) || !is_array($data['extra'])) {
            throw new InvalidArgumentException('"extra" must be present as object!');
        } else if (!array_key_exists('liccheck', $data['extra']) || !is_array($data['extra']['liccheck'])) {
            throw new InvalidArgumentException('"liccheck" must be present as object!');
        }

        $data = $data['extra']['liccheck'];

        try {
            self::validate($data);
        } catch (InvalidArgumentException $err) {
            throw new NoValidConfigurationInComposerJson($err->getMessage());
        }

        return new self(
            $data['Licenses']['authorized_licenses'],
            $data['Licenses']['unauthorized_licenses'],
            $data['Authorized Packages']
        );
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

        if (!file_exists($strategyFile)) {
            throw new InvalidArgumentException('No such file or directory in ' . $strategyFile);
        }
        $data = json_decode(file_get_contents($strategyFile), true);
        if (false === $data) {
            throw new InvalidArgumentException('"$strategyFile" is not a file!');
        } else if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('"$strategyFile" is not a JSON file valid!');
        }

        self::validate($data);

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

    /**
     * @param $data string[][]
     * @return void
     * @throws InvalidArgumentException
     */
    private static function validate($data)
    {
        if (!array_key_exists('Licenses', $data)) {
            throw new InvalidArgumentException('Missing "Licenses" key!');
        } else if (!array_key_exists('authorized_licenses', $data['Licenses'])) {
            throw new InvalidArgumentException('Missing "authorized_licenses" key!');
        } else if (!array_key_exists('unauthorized_licenses', $data['Licenses'])) {
            throw new InvalidArgumentException('Missing "unauthorized_licenses" key!');
        } else if (!array_key_exists('Authorized Packages', $data)) {
            throw new InvalidArgumentException('Missing "Authorized Packages" key!');
        } else if (!is_array($data['Licenses']['authorized_licenses'])) {
            throw new InvalidArgumentException('"authorized_licenses" must be an array!');
        } else if (!is_array($data['Licenses']['unauthorized_licenses'])) {
            throw new InvalidArgumentException('"authorized_licenses" must be an array!');
        } else if (!is_array($data['Authorized Packages'])) {
            throw new InvalidArgumentException('"Authorized Packages" must be an array!');
        }
    }
}
