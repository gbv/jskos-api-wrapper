This repository contains wrappers to provide JSKOS-API for other terminology
services.

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


# Installation

Copy directory `vendors` and `wrappers` to a webserver with PHP enabled.

