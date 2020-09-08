# PHP License Checker library

liccheck is an PHP License Checker library thanks to `composer licenses` command output to check composer packages listed and report license issues.

Based on `dhatim/python-license-check`.

## Installation

Use the package manager [composer](https://getcomposer.org/doc/) to install liccheck.

```bash
composer require --dev ticdenis/liccheck
```

## Usage

```bash
./vendor/bin/liccheck -s license_strategy.json
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
      "ticdenis/liccheck"
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
