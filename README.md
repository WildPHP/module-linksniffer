# LinkSniffer Module
[![Build Status](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/badges/build.png?b=master)](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/wildphp/module-linksniffer/v/stable)](https://packagist.org/packages/wildphp/module-linksniffer)
[![Latest Unstable Version](https://poser.pugx.org/wildphp/module-linksniffer/v/unstable)](https://packagist.org/packages/wildphp/module-linksniffer)
[![Total Downloads](https://poser.pugx.org/wildphp/module-linksniffer/downloads)](https://packagist.org/packages/wildphp/module-linksniffer)

This module shows information about a link when it is posted in a channel.

## System Requirements
This module requires the `json` php extension for various sub-modules to work.

## Installation
To install this module, we will use `composer`:

```composer require wildphp/module-linksniffer```

That will install all required files for the module. In order to activate the module, add the following line to your modules array in `config.neon`:

    - WildPHP\Modules\LinkSniffer\LinkSniffer

The bot will run the module the next time it is started.

## Configuration
It is possible to blacklist this module in certain channels. For this, add the following snippet to your `config.neon`:

```neon
disablelinksniffer:
        - '#channel1'
        - '#channel2'
```

## Usage
This module does not have additional usage information.

## License
This module is licensed under the MIT license. Please see `LICENSE` to read it.
