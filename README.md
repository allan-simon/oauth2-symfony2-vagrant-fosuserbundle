# Oauth2 server (Vagrant + apache2 + posgresql + php-fpm + symfony)

Please read and check this Readme from times to times so that
to not waste time later wondering how to do things which are
already explained here

if something is wrong or missing: tell me, if you don't tell me
I have no way to improve it =)

  * we use `vagrant` to create the dev environnement
  * `apache2` to provide the web server
  * `postgresql` for the database
  * `php-fpm` as the php interpreter
  * `symfony` + `doctrine` for the base Framework
  * `DoctrineMigration` to create database migration (should be simpler than Phinx)
  * `FOSOAuthServerBundle` to provide the Oauth2 endpoint
  * `FOSUserBundle` to provide user authentication


for windows user you can have a nfs using this plugin

```
vagrant plugin install vagrant-winnfsd
```

# Create the vagrant machine

```
vagrant up
```

# Usage

## Initiate database


in the vagrant machine (in directory `/vagrant` ) run:

```
php app/console  doctrine:migrations:migrate
```

## Create a client

in the vagrant machine (in directory `/vagrant` ) run: (in case you want to use the grant type `password`)

```
php app/console acme:oauth-server:client:create --grant-type="password" --grant-type="refresh_token" --grant-type="token"
```

it will return you:

```
Added a new client with public id CLIENT_ID, secret CLIENT_SECRET
```

## Create a new end user

```
php app/console fos:user:create vagrant vagrant@vagrant.com vagrant
```

## Get an authorization token with grant type *password*

run this HTTP request

```
http://127.0.0.1:8089/app_dev.php/oauth/v2/token?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=password&username=vagrant&password=vagrant
```

and you should get back

```
{
    "access_token":"NTdkNGI3YjE1MmY1MjExMzVkMmUwM2Q4OTQ4NWMwOGM0YTYzNjI1NGZlM2I3ZGU2ZTE2NWQ4N2UyYTZiYmY4ZA",
    "expires_in":3600,
    "token_type":"bearer",
    "scope":"user",
    "refresh_token":"NGY3ZTJhYjhmMmRjM2YyZDlmZGI4Mzk2MmY5OGMzMjZmZmY1OWFmNTkyYWFlZDg5YWZlZjA2MDU2YzNjYmU2Mw"
}
```

## Use refresh token

Once your `access token` is expired you can use the refresh token to get a new access token and new refresh token

```
 http://127.0.0.1:8089/app_dev.php/oauth/v2/token?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=refresh_token&refresh_token=PREVIOUS_REFRESH_TOKEN
```

Note: a refresh token can only be used once


## (not part of oauth2 standard) check if a token is valid

In case you have a microservice architecture and you want a service you own to be able to check
on the Oauth2 server if an access token is valid you can use this call `/oauth/access_token_valid/{accessToken}`

for example:

```
http://127.0.0.1:8089/app_dev.php/oauth/access_token_valid/NTdkNGI3YjE1MmY1MjExMzVkMmUwM2Q4OTQ4NWMwOGM0YTYzNjI1NGZlM2I3ZGU2ZTE2NWQ4N2UyYTZiYmY4ZA
```

it will return

  * HTTP status code 200 if the token is valid
  * HTTP status code 410 (resource gone) if not

for the moment the body of these reponse for this call is purely for debugging purpose

# Basic Development tasks

### Commit code

commiting code will run automatically php-codesniffer to check
that your code is well written

for common mistakes (extra spaces etc.), there is the command

```
bin/php-cs-fixer  fix src  -v
```

to fix them for you (don't forget to `git add` again after you've run this command)

### Creating a new Bundle

A middle sized project is supposed to be made of several bundles
if not, you're certainly doing something wrong (too much coupling etc.)

```
php app/console generate:bundle --namespace=%PROJECT_NAME%/%XXX%Bundle --no-interaction --format=yml
```

replace `%PROJECT_NAME%` and `%XXX%` by the project name and the name of the feature
your bundle is covering for example

```
php app/console generate:bundle --namespace=WeBridge/VideoBundle --no-interaction --format=yml
```

### Creating a Database Migration

If you want to add/delete/edit a Table or a column:

For simply create/modify your Entity as normal, and when you're done run

```
php app/console doctrine:migrations:diff
php app/console doctrine:migrations:migrate
```

more instruction in the [official documentation](http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html#generating-migrations-automatically)


#Resource

## Oauth2

 * [Tutorial I've followed to get the basic server working](http://blog.tankist.de/blog/2013/07/17/oauth2-explained-part-2-setting-up-oauth2-with-symfony2-using-fosoauthserverbundle/)
 * [Tutorial that I've used to have the Oauth2 server works with FOSUserBundle](http://stackoverflow.com/questions/21390844/fosoauthserverbundle-with-fosuserbundle-how-to-make-it-works)
 * [More information on making it work with FOSUserBundle](http://blog.logicexception.com/2012/04/securing-syfmony2-rest-service-wiith.html)

## Symfony / Doctrine

  * [The official documentation](http://symfony.com/doc/current/book/index.html)
