symfony-aws
===========

> /!\ **Work in progress** /!\

Run the project locally
-----------------------

Using docker compose :

```bash
docker compose up -d
docker compose exec php composer install
docker compose exec php php bin/console doctrine:schema:update --force
docker compose exec php php bin/console assets:install
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console
```

To be verified: 

```bash
docker compose exec php php bin/console app:system:check
```

--- 

Documentation page available on http://localhost/api-doc.html.
