<?php
/**
 * This file is part of the Quadro RestFull Framework which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
 */
declare(strict_types=1);

use Quadro\Application as App;
use Quadro\Authentication as Auth;
use Quadro\Authentication\EnumAuthenticateErrors;

$app = App::getInstance();

/**
 * If the there is no Authentication Component registered a HTTP 409 Conflict is returned
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409.
 */
if (!$app->hasComponent(Auth::getSingletonName())) {
    $app->getResponse()->setContent('Authentication Component not registered');
    $app->getResponse()->setStatusCode(409);
    return $app->getResponse();
}
$auth = $app->getComponent(Auth::getSingletonName());

/**
 * This controller returns the JWT on success
 * on failure the IP is stored and the throttle period is increased when
 * more than the allowed attempts are made for this IP.
 *
 * We expect a public phrase(username or email) and a private phrase(password)
 * to be passed as a json object or as POST date.
*/
$result = $auth->authenticate();
if($result instanceof EnumAuthenticateErrors) {
    switch ($result) {

        case EnumAuthenticateErrors::ExceedsMaxAttempts;
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode(429);
            break;

        case EnumAuthenticateErrors::NoCredentials;
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode(400);
            break;

        case EnumAuthenticateErrors::TokenIsEmpty; // no break;
        case EnumAuthenticateErrors::TokenInvalidFormat; // no break;
        case EnumAuthenticateErrors::TokenDecodeError; // no break;
        case EnumAuthenticateErrors::TokenExpirationMissing; // no break;
        case EnumAuthenticateErrors::TokenExpired; // no break;
        case EnumAuthenticateErrors::TokenInvalid; // no break;
        case EnumAuthenticateErrors::Failed; // no break;
        default:
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode( 422);
    }

} else {
    $app->getResponse()->setHeader('WWW-Authenticate: Bearer '. $result['jwt']);
    $app->getResponse()->setContent($result);
}

// return response to indicate we already set the content of the response
return $app->getResponse();

