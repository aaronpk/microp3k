microp3k
========

This software can be used to create one or more miniblogs with a micropub endpoint. It doesn't do much else yet.

Please don't use this yet, it's really not ready for other people to try yet.

If you must, follow the installation instructions below.

Installation
------------

* `git clone git@github.com:aaronpk/microp3k.git`
* `cd microp3k`
* `composer install`
* `cp lib/config.template.php lib/config.php`
* configure your web server to serve files in `public` and route all other requests to `public/index.php`
* install beanstalkd (`apt-get install beanstalkd` works great)
* run the background worker: `php scripts/run.php`

