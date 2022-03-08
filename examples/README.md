#Sample Applications

All sample Applications are tested with the build in PHP webserver and
when invoked it should point to the `index.php` as the front controller
in the public folder of the chosen application like this:
```
~ php -S localhost:8080 xxx/public/index.php
```

## Application <u>"default"</u>

The default, minimal applications only requires to include the class file for the 
\Quadro\Application class. In this example we use the framework out of 
the box and not through installation with Composer 

```php
/** 
 * @see /default/public/index.php for more information
 * 
 * The following build-in routes will be available:
 * 
 * - http://localhost:8080/application     Shows content of the Application
 * - http://localhost:8080/check           Validates Quadro Framework installation    
 * - http://localhost:8080/translations[/language[/collection[?t=&[p=][&s=]]]]    
 *                                         Returns text translations
 * - http://localhost:8080/phpinfo         PHP INI information only in debug mode
 * 
 * set the application path manually to avoid Application Path Error
 */
define('QUADRO_DIR_APPLICATION', realpath(__DIR__ .  '/../'). '/' ); 
require_once "/location/to/libraries/Quadro/Application";
Quadro\Application::handleRequest();
```
## Application <u>"auth10"</u>

In this application we enable authorization. By adding the build in JWT
Authorization Component. This will deny all URI's(except the signup 
and sign-in URI's) when no valid JWT token is added in the authorization 
header:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cGUiOiJKV1QifQ.eyJkYXRhIjoicm9iQGFtc3RlbHZlZW4uZGlnaWJlbmRlLm5sIiwiaWF0IjoxNjQ1MzczODU4LCJleHAiOjE2NDU0NjAyNTh9.cCq1_PJEnGCQ0iY8ZBhqpWxRs2nnPo4wCSsaghwOPZ8
```
 
```php
/**
 ** @see /auth10/public/index.php for more information
 * 
 * The following build-in routes will be available:
 * - http://localhost:8080/accounts/authenticate
 * - http://localhost:8080/accounts/register
 *
 * set the application path manually to avoid Application Path Error
 */
define('QUADRO_DIR_APPLICATION', realpath(__DIR__ .  '/../'). '/' );
require_once "/location/to/libraries/Quadro/Application";
Quadro\Application::getInstance()->addComponent(new Quadro\Authentication\Jwt());
Quadro\Application::handleRequest();
```

To test registration or authentication call the scripts below on 
the command line (php-curl need to be installed). In both cases localhost:8080 
should be up and running.

```
~ php /auth10/curl-post-request.php accounts/register email=<email> pass=<password>
~ php /auth10/curl-post-request.php accounts/authenticate email=<email> pass=<password>
```
 