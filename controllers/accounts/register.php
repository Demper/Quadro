<?php
declare(strict_types=1);

use Quadro\Application as App;
use Quadro\Authentication as Auth;
use Quadro\Authentication\EnumRegisterErrors;

$app = App::getInstance();

/**
 * If there is no Authentication Component registered exit with "HTTP 409 Conflict"
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409.
 */
if (!$app->hasComponent(Auth::getSingletonName())) {
    $app->getResponse()->setContent('Authentication Component not registered');
    $app->getResponse()->setStatusCode(409);
    return null;
}
$auth = $app->getComponent(Auth::getSingletonName());

/**
 * You can create a custom Authentication Component implementing the
 * Authentication Interface and still use this Controller. The Component must manually
 * be registered on start up of the request.
 *  *
 * The build in Authentication Component expects an valid email and password and stores this in a
 * SQL light Database. It will need activation and allows a maximum attempts
 *
 * If we have a Authentication Component we pass the request to the Authentication Component.
 *
 * @see Quadro\Authentication::register();
 *
 * If not passed as parameters the Component it wil look for public phrase(i.e. username or email)
 * and a privatePhrase(i.e. password) either to be passed as a json object or as POST data. The component
 * is responsible for filtering, sanitizing and validation the information. THe return value is check on
 * how to exit the process
 */
$result = $auth->register();
if($result instanceof EnumRegisterErrors) {
    switch ($result) {

        /**
         *  On repeated failure we exit with an HTTP 429 "Too Many Request". How many attempts are valid is
         *  up to the Component
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429
         */
        case EnumRegisterErrors::ExceedsMaxAttempts:
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode(429);
            break;

        /**
         *  When the information is invalidated it will provide an appropriate message and the process exits
         *  with a HTTP 400 "Bad request"
         * @see https://developer.mozilla.org/en-US/docs/web/http/status/400
         */
        case EnumRegisterErrors::NoCredentials: // no break;
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode(400);
            break;

        case EnumRegisterErrors::CredentialsDoesNotMeetRequirements:
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode(400);
            break;

        /**
         * A User with the same unique credentials already exists, exit the process
         * with HTTP 409  Conflict
         */
        case EnumRegisterErrors::NotUnique:
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode(409);
            break;

        /**
         * The credentials should be able to save but unexpected errors may occur
         */
        case EnumRegisterErrors::Unexpected:
            $app->getResponse()->setContent($result->getMessage());
            $app->getResponse()->setStatusCode(500);
            break;

        /**
         * In case we forgot something just deny access
         */
        default:
            $app->getResponse()->setStatusCode(401);
            break;
    }
} else {

    /**
     * When correctly registered the returned information is sent back and this process exits with an HTTP 200 "Ok"
     * The component may optionally send extra information on how to activate/complete the registration
     */
    $app->getResponse()->setContent($result);
}

// return response to indicate we already set the content of the response
return $app->getResponse();



