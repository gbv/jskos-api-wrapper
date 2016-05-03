This repository contains sample applications that make use of the [jskos PHP
library](https://packagist.org/packages/gbv/jskos) to process knowledge
organization systems (KOS) in [JSKOS format](https://gbv.github.io/jskos/). In
particular there is a set of **wrappers to access different KOS via a uniform
API**. The uniform API is specified as [Entity Lookup Microservice API
(ELMA)](http://gbv.github.io/elma/) for simple access and will be extended to
[JSKOS-API](https://gbv.github.io/jskos-api/) for more complex queries.

[![Build Status](https://img.shields.io/travis/gbv/jskos-php-examples.svg)](https://travis-ci.org/gbv/jskos-php-examples)
[![Coverage Status](https://coveralls.io/repos/gbv/jskos-php-examples/badge.svg?branch=master)](https://coveralls.io/r/gbv/jskos-php-examples)

# Try out

The examples can be tried online at <https://jskos-php-examples.herokuapp.com>.

# Local usage

Wrappers are based [jskos PHP library](https://packagist.org/packages/gbv/jskos).
First install dependencies with [composer](https://getcomposer.org/):

    $ composer install

You can directly serve wrappers via PHP for testing (don't use for production!):

    $ php -S localhost:8080 -t src

And accesed via <http://localhost:8080>. You can also start the server with

    $ make run

# Installation

Copy directory `vendors` and `wrappers` to a webserver with PHP enabled.

