# Vib SymfUser

1 - php bin/console doctrine:database:create
2 - php bin/console make:migration
3 - php bin/console doctrine:migrations:migrate
4 - php bin/console doctrine:fixtures:load

when adding a new class 

composer dump-autoload --optimize