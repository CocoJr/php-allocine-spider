[![Build Status](https://travis-ci.org/CocoJr/php-allocine-spider.svg?branch=master)](https://travis-ci.org/CocoJr/php-allocine-spider)

### Installation

`composer require cocojr/allocine-spider`

### Tests

`./vendor/bin/phpunit`

### Allocine Spider written in PHP

A simple class to fetch films, series and persons from allocine.fr
Use `fetchFilm` with the allocine ID to get a \StdClass with all informations.
Informations included:

 - allocineId
 - title
 - synopsis
 - img
 - releaseDate
 - duration
 - creators
 - actors
 - types
 - nationalities

Use `fetchPerson` with the allocine ID to get a \StdClass with all informations of the person.
Informations included:

 - allocineId
 - name
 - birthDate
 - img
 - nationality
 - biography

To get the allocine ID, just show the URL:
̀`http://www.allocine.fr/film/fichefilm_gen_cfilm={ALLOCINE ID}.html` for a film
`http://www.allocine.fr/personne/fichepersonne_gen_cpersonne={ALLOCINE ID}.html` for a person

@TODO: Implement the `fetchSerie` method.
