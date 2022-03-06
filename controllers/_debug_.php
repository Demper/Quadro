<?php
declare(strict_types=1);

use Quadro\Application as App;

$app = App::getInstance();

print_r($app->getRequest());
print_r($app->getRequest()->getRawBody());
print_r($app->getRequest()->getRawBodyAsJson());
print_r($app->getRequest()->getPostData());


exit();