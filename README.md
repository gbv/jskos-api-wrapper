This repository contains sample applications that make use of the [jskos PHP
library](https://packagist.org/packages/gbv/jskos) to process knowledge
organization systems in [JSKOS format](https://gbv.github.io/jskos/).

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

