# Vib SymfUser

Prerequisites
-------------

This bundle requires :
- Symfony 4.0+ 
- Php-7.1 

**Protip:** Though the bundle doesn't enforce you to do so, it is highly recommended to use HTTPS. 

Installation
------------

1 - php bin/console doctrine:database:create

2 - php bin/console make:migration

3 - php bin/console doctrine:migrations:migrate

4 - php bin/console doctrine:fixtures:load

Specific redump autoload
------------

when adding a new class 

composer dump-autoload --optimize
