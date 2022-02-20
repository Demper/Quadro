#Sample Applications

## Default

The default, minimal application only requires to include the class file for the 
\Quadro\Application class. In this example we use the framework out of 
the box and not through installation with Composer 

`./public/index.php`:
```php
// only serve build in routes
// /
// /application     Shows content of the Application
// /check           Validates Quadro Framework installation    
// /translations[/language[/collection[?t=&[p=][&s=]]]]    Returns text translations
// /phpinfo         PHP INI information only in debug mode
// /signin
// /signup 
require_once "/location/to/libraries/Quadro/Appliactaion";
Quadro\Application::handleRequest();
```

## Add Controller scripts