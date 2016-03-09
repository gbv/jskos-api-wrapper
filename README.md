This repository contains sample applications that make use of the [jskos PHP
library](https://packagist.org/packages/gbv/jskos) to process knowledge
organization systems in [JSKOS format](https://gbv.github.io/jskos/).

[![Build Status](https://img.shields.io/travis/gbv/jskos-php-examples.svg)](https://travis-ci.org/gbv/jskos-php-examples)
[![Coverage Status](https://coveralls.io/repos/gbv/jskos-php-examples/badge.svg?branch=master)](https://coveralls.io/r/gbv/jskos-php-examples)

# Usage

Wrappers are based [jskos PHP library](https://packagist.org/packages/gbv/jskos).
First install dependencies with [composer](https://getcomposer.org/):

    $ composer install

You can directly serve wrappers via PHP for testing (don't use for production!):

    $ php -S localhost:8080 wrappers.php

## Examples

### OpenSKOS API Wrapper

* <http://localhost:8080/OpenSKOS?uri=http://data.europeana.eu/concept/loc>
* <http://localhost:8080/OpenSKOS?uri=http://id.loc.gov/authorities/subjects/sh2007003224>
* <http://localhost:8080/OpenSKOS?notation=1500>

### GND Wrapper

* <http://localhost:8080/GND?notation=118540475>
* <http://localhost:8080/GND?uri=http://d-nb.info/gnd/118509624>

# Installation

Copy directory `vendors` and `wrappers` to a webserver with PHP enabled.

