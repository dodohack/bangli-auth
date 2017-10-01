# This is the repository of auth.bangli.uk.

* This is the sso server for bangli global.
* It is based on laravel lumen 5.5.
* It is a server that only provides RESTful APIs.

## AUTHENTICATION
This is a simple flow of user login process
* User inputs username/email/password at client side SPA, say bangli-spa.
* User clicks submit button, sends credentials to bangli-auth server.
* Bangli-auth server validates the credentials, return JWT token on success
* Client side SPA keeps JWT in localStorage, and send it as Authentication
   request header to the API server when it is asking protected resources.
* API server verify if user JWT is valid and serve data to client if true.


## DEVELOPMENT
* Run git pull and composer update
* Check if CORS header is set in public/index.php
* Before bringing this server up, you need to create .env by copying
  from .env.example, and update the .env variables to correct value.
* To start this test api server at local, you need to go to http root 
  bangli-api/server/public and run php -S localhost:port
* If there is something wrong with your test server, make sure .env is correctly
  configured.
* Reference "DEPLOY" section for more details of .env config.

E.g.
cd bangli-auth/server/public
php -S localhost:5000
Now the auth server can be accessed by visiting localhost:5000.

## DATABASE
Before start coding, we need to migrate some user data from our old user table.
Follow the steps:
* Create database and tables using SQL script bangli-auth/database/bangli-auth.sql,
  default database name is 'bangli-auth'. Make sure this database name matches
  DB_DATABASE in your .env file.
* DONE.


## DEPLOY

* Run git pull and composer update
* Check if CORS is enabled in public/.htaccess
* Create file .env on production server
* Update APP_KEY to a unique string with 32 characters in .env
* Update DB_* setting in .env
* Update CACHE_DRIVER etc in .env
* Update MAIL_* settings in .env
* Set JWT_SECRET to a unique string with 32 characters in .env; make sure
  all api servers has the same JWT_SECRET as this auth server, otherwise
  authentication will fail.
* Reference the files under config/ to get a knowledge of how these variables
  defined in .env are used.
