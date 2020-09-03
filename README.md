# PHP License Checker library

liccheck is an PHP License Checker library thanks to `composer licenses` command output to check composer packages listed and report license issues.

## Installation

Use the package manager [composer](https://getcomposer.org/doc/) to install liccheck.

```bash
composer require ticdenis/liccheck
```

## Usage

```bash
liccheck -s license_strategy.json -r /usr/local/bin/composer
```

Here an example of a ``license_strategy.json`` file:
```json
{
    "Licenses": {
        "authorized_licenses": [
          "MIT",
          "Apache 2.0"
        ],
        "unauthorized_licenses": [
          "propietary"
        ]
    },
    "Authorized Packages": [
      "phpunit/phpunit"
    ]
}
```

## Requirements

- PHP +5.4
- Composer +1

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://github.com/ticdenis/php-liccheck/blob/master/LICENSE)
