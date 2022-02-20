<?php
declare(strict_types=1);

use Quadro\Application as App;
use Quadro\Authentication as Auth;

$app = App::getInstance();

/**
 * If the there is no Authentication Component registered a HTTP 409 Conflict is returned
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409.
 */
if (!$app->hasComponent(Auth::getComponentName())) {
    $app->getResponse()->setContent('Authentication Component not registered');
    $app->getResponse()->setStatusCode(409);
    return null;
}
$auth = $app->getComponent(Auth::getComponentName());

/**
 * This controller returns the JWT on success
 * on failure the IP is stored and the throttle period is increased when
 * more than the allowed attempts are made for this IP.
 *
 * We expect a public phrase(username or email) and a private phrase(password)
 * to be passed as a json object or as POST date.
*/
$result = $auth->authenticate();
if ($result === false){
    $app->getResponse()->setStatusCode( 422);
    return null;
}
return $result;
