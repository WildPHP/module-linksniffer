# LinkSniffer Module
[![Build Status](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/badges/build.png?b=master)](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/WildPHP/module-linksniffer/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/wildphp/module-linksniffer/v/stable)](https://packagist.org/packages/wildphp/module-linksniffer)
[![Latest Unstable Version](https://poser.pugx.org/wildphp/module-linksniffer/v/unstable)](https://packagist.org/packages/wildphp/module-linksniffer)
[![Total Downloads](https://poser.pugx.org/wildphp/module-linksniffer/downloads)](https://packagist.org/packages/wildphp/module-linksniffer)

This module shows information about a link when it is posted in a channel.

## System Requirements
If your setup can run the main bot, it can run this module as well.

## Installation
To install this module, we will use `composer`:

composer require wildphp/module-linksniffer

That will install all required files for the module. In order to activate the module, add the following line to your `main.modules` file:

    WildPHP\Modules\LinkSniffer\LinkSniffer

The bot will run the module the next time it is started.

## License
This module is licensed under the GNU General Public License, version 3. Please see `LICENSE` to read it.
