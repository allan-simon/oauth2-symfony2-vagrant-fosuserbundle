# Oauth2 server (Vagrant + apache2 + posgresql + php-fpm + symfony)


[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/allan-simon/oauth2-symfony2-vagrant-fosuserbundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/allan-simon/oauth2-symfony2-vagrant-fosuserbundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/allan-simon/oauth2-symfony2-vagrant-fosuserbundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/allan-simon/oauth2-symfony2-vagrant-fosuserbundle/?branch=master)
[![Build Status](https://travis-ci.org/allan-simon/oauth2-symfony2-vagrant-fosuserbundle.svg?branch=master)](https://travis-ci.org/allan-simon/oauth2-symfony2-vagrant-fosuserbundle)

It currently support PHP5.4 ad PHP5.5, for support of PHP5.6, we're waiting for Doctrine2.5 to be released

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
php app/console acme:oauth-server:client:create \
--grant-type="password" \
--grant-type="refresh_token" \
--grant-type="token" \
--client-type="backend"
```

The `client-type` parameter is optional, the given value is used to specified the type of the client we create. This is a walk around as we cannot use the "scopes" feature provided by oauth2. In the bundle we use, this feature is not available from the client.

it will return you:

```
Added a new client with public id CLIENT_ID, secret CLIENT_SECRET
```

## Create a new end user

### Through the console
```
php app/console fos:user:create vagrant vagrant@vagrant.com vagrant
```

### Through an API call


```
echo '
{
    "email": "TEST@EXAMPLE.COM" ,
    "username" : "USER_NAME",
    "plain_password" : "PLAIN_TEXT_PASSWORD"
}
' |  http POST http://127.0.0.1:8089/app_dev.php/users
```

or

```
echo '
{
    "phone_number": "1234567" ,
    "username" : "USER_NAME",
    "plain_password" : "PLAIN_TEXT_PASSWORD"
}
' |  http POST http://127.0.0.1:8089/app_dev.php/users
```

or

```
echo '
{
    "email": "TEST@EXAMPLE.COM" ,
    "phone_number": "1234567" ,
    "username" : "USER_NAME",
    "plain_password" : "PLAIN_TEXT_PASSWORD"
}
' |  http POST http://127.0.0.1:8089/app_dev.php/users
```

if everything is made correctly you should get back this

```
HTTP/1.1 202 Accepted
Cache-Control: no-cache
Content-Type: application/json
Date: XXX
Server: XXXX
Transfer-Encoding: chunked

{
    "id": 42
}
```

once it's done you then need to activate the user with this API call

```
http PUT http://127.0.0.1:8089/app_dev.php/users/{id}/confirmation-token/{confirmationToken}
```

if the user is correctly activate you will receive a `201 Created` status code

if the data are posted are not correct a 400 status code will be returned, containing a JSON array
of error message like this:

```
[

    {
        "message": "bst.phone_and_email.missing", 
        "property_path": ""
    }, 

    {
        "message": "fos_user.username.short", 
        "property_path": "username"

    }, 

    {
        "message": "fos_user.password.blank", 
        "property_path": "plainPassword"
    }
]

```

each error has two fields :

  * `message`: the error code, it's a pre-defined string constant, made to be somewhat human-readable
  * `property`: if empty, it concerns the full form, if not, it contains the field concerned by the error message 

possible value for message:

  * `bst.phone_and_email.missing` : the user has input neither phone nor email
  * `fos_user.email.already_used` : the email is already taken (it also check case-insensitive and all rules that apply to consider two emails identical)
  * `fos_user.username.already_used` : the username is already taken (it also check case-insensitive and Unicode trick to obtain the same visual string to avoid user impersonnation)
  * `bst.phonenumber.already_used` : phone number already used.
  * `fos_user.email.short`: the email is too short
  * `fos_user.email.long`: the email is too long
  * `fos_user.username.short`: the email is too short (currently 2 characters)
  * `fos_user.username.long`: the email is too long (currently 255 characters)
  * `bst.phonenumber.short` : phone number too short ( < 3)
  * `bst.phonenumber.long` : phone number too long ( > 20)
  * `bst.phonenumber.space`: phone number contains space characters
  * `bst.phonenumber.format`: phone number does not match the format (contains letters this kind of thing)
  * `fos_user.password.blank`:  the password has not been precised by user.
  * `fos_user.password.short`:  the password is too short ( < 3 characters)

### Note:

In order to avoid a user getting "locked" in the following situation:

  1. the user register using phone A
  2. before getting the validation code, the user closes the App AND for some reason never get the validation phone

which would result in the user's email or phone number being marked as used by the system but enable to be used by the user.
In order to avoid that, if you register twice with the same credentials, without activitating the first time, the system
will accept the second registration and delete the first one.

## Backend API calls

These API calls can only be executed by an user connected through a "backend" type client. All these calls will throw access denied exception if the current user is not allowed to perform them.

- POST /admin/users - Create a new user
- PUT /admin/users/{id} - Edit an user
- PUT /admin/users/{id}/roles - Edit the roles of an user
- GET /admin/users/{id} - Get one user information
- PATCH /admin/users/{id}/disable - Disable an user

### POST /admin/users - Create an user

One user connected through the backend client is allowed to create other users.

```
echo '
{
    "email": "TEST@EXAMPLE.COM" ,
    "username" : "USER_NAME",
    "plain_password" : "PLAIN_TEXT_PASSWORD",
    "phone_number": "123456789",
    "roles": ["ROLE_1", "ROLE_2"]
}
' |  http POST http://127.0.0.1:8089/app_dev.php/admin/users 'Authorization:Bearer {accessToken}'
```

If everything is made correctly you should get back this

```
HTTP/1.1 201 Created
Cache-Control: no-cache
Connection: Keep-Alive
Content-Type: application/json
Date: XXX
Server: XXXX
Transfer-Encoding: chunked

{
    "id": 5
}
```

### PUT /admin/users/{id} - Edit an user

One user connected through the backend client is allowed to edit an user.

```
echo '
{
    "email": "TEST@EXAMPLE.COM" ,
    "username" : "USER_NAME",
    "phone_number" : "12345",
    "roles": ["ROLE_1", "ROLE_2"]
}
' |  http PUT http://127.0.0.1:8089/app_dev.php/admin/users/{id} 'Authorization:Bearer {accessToken}'
```

If everything is made correctly you should get back this

```
HTTP/1.1 200 OK
Cache-Control: no-cache
Connection: Keep-Alive
Content-Type: application/json
Date: XXX
Server: XXXX
Transfer-Encoding: chunked

{
    "id": 5
}
```

### PUT /admin/users/{id}/roles - Edit the roles of an user

One user connected through the backend client is allowed to edit the roles of an user.

```
echo '
    ["ROLE_1", "ROLE_2"]
' |  http PUT http://127.0.0.1:8089/app_dev.php/admin/users/{id}/roles 'Authorization:Bearer {accessToken}'
```

If everything is made correctly you should get back this

```
HTTP/1.1 204 No content
Cache-Control: no-cache
Connection: Keep-Alive
Content-Type: text/html
Date: XXX
Server: XXXX
Keep-Alive: XXXX
```

### GET /admin/users/{id} - Get an user information

One user connected through the backend client is allowed to check other user information.

```
http GET http://127.0.0.1:8089/app_dev.php/admin/users/{id} 'Authorization:Bearer {accessToken}'
```

If everything is made correctly you should get back this

```
HTTP/1.1 200 OK
Cache-Control: no-cache
Connection: Keep-Alive
Content-Type: application/json
Keep-Alive: XXXX
Date: XXX
Server: XXXX

{
    "email": "test@test.com",
    "id": 15,
    "phone_number": "123456789",
    "user_roles": [
        "ROLE_USER"
    ],
    "username": "Allan"
}
```

### PATCH /admin/users/{id}/disable - Disable an user

One user connected through the backend client is allowed to disable other users.

```
http PATCH http://127.0.0.1:8089/app_dev.php/admin/users/{id}/disable 'Authorization:Bearer {accessToken}'
```

If everything is made correctly you should get back this

```
HTTP/1.1 204 No Content
Cache-Control: no-cache
Connection: Keep-Alive
Content-Type: text/html
Date: XXX
Server: XXXX
```

### PATCH /admin/users/{id}/enable - Enable an user

One user connected through the backend client is allowed to enable other users.

```
http PATCH http://127.0.0.1:8089/app_dev.php/admin/users/{id}/enable 'Authorization:Bearer {accessToken}'
```

If everything is made correctly you should get back this

```
HTTP/1.1 204 No Content
Cache-Control: no-cache
Connection: Keep-Alive
Content-Type: text/html
Date: XXX
Server: XXXX
```

### PUT /admin/users/{id}/password - change password of a user

One user connected through the backend client is allowed to change password of other users.

```
echo '
{
    "new_password" : "NEW_PASSWORD",
}
' |http PATCH http://127.0.0.1:8089/app_dev.php/admin/users/{id}/password 'Authorization:Bearer {accessToken}'
```

If everything is made correctly you should get back this

```
HTTP/1.1 204 No Content
Cache-Control: no-cache
Connection: Keep-Alive
Content-Type: text/html
Date: XXX
Server: XXXX
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

  * HTTP status code 200 if the token is valid with the user's information in a Json in body
  * HTTP status code 410 (resource gone) if not. The body is purely for debugging for the moment

example of successful request

```
{
    "id": 1,
    "email": "vagrant@vagrant.com",
    "phone_number": "0545454",
    "roles": [
        "ROLE_USER"
    ],
    "username": "vagrant"
}

```

## Change password

```
echo '
{
    "new_password" : "NEW_PASSWORD",
    "old_password" : "PLAIN_TEXT_PASSWORD"
}
' |  http PATCH http://127.0.0.1:8089/app_dev.php/users/{id}/password

```

## Change email or phone number

```
echo '
{
    "new_contact_info" : "NEW_EMAIL_OR_PHONE_NUMBER",
    'password' : 'PLAIN_TEXT_PASSWORD'
}
' |  http PATCH http://127.0.0.1:8089/app_dev.php/users/{id}/request-change-contact-info

```

The server will send a validation code to the email or phone number given

Then using this validation code a second call need to be made to this API call:

```
echo '
{
    "new_contact_info" : "NEW_EMAIL_OR_PHONE_NUMBER"
    "validation_code" : "VALIDATION_CODE"
}
' |  http PATCH http://127.0.0.1:8089/app_dev.php/users/{id}/contact-info

```

## Forgot password

```
echo '
{
    "contact_info" : "EMAIL_OR_PHONE_NUMBER"
}
' |  http POST http://127.0.0.1:8089/app_dev.php/users/forgot-password

```

if you've entered an existing email or phone number it will return you

```
{ 'id' : USER_ID }
```

in the meantime a validation code will be sent to you, you can then use the USER_ID and that VALIDATION_CODE to reset your password using the call

```
echo '
{
    "new_password" : "NEW_PASSWORD",
    "validation_code" : "VALIDATION_CODE"
}
' |  http PATCH http://127.0.0.1:8089/app_dev.php/users/{id}/reset-password

```

## Reask for confirmation token to be sent

if for some reason you want to be able to resend the sms/email
containing the confirmation token sent after registration you can
do using this API call (where `{id}` is to be replaced by the id
given to you after registration)

```
http PATCH http://127.0.0.1:8089/app_dev.php/users/{id}/resend-confirmation-token
```

it returns a http status 204 in case of success, 404 if the user id does not
exist.

Note: the confirmation token will be the same as the one that was originally
sent (in order not to confuse the user if finally get the first SMS etc.)

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
