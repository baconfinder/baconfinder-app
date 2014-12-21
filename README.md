Tom Sawyer's Bacon Finder
=========================

Application developed for Sai (USA).

![Imgur](http://i.imgur.com/zlr9f8X.png)

## Specs

* Twitter & Facebook Login
* Importing Facebook friends using also the app in neo
* Importing Twitter followed contacts into neo
* Facebook & Twitter are bounded to the same user node in neo
* Friend search
* Some suggestion system
* Show paths and connectivity between the current user and founded people

## Installation :

* Clone the repository
* Install the composer dependencies :

```bash
// for development
composer install

// for production
composer install --no-dev --optimize-autoloader
```

* Install the JS deps
```bash
bower install
```

* Copy `app/config/parameters.yml.dist` to `app/config/parameters.yml` and provide the facebook and twitter app tokens

## Technical documentation

To be written

