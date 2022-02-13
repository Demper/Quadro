<?php
use Quadro\Application as Application;
use \Quadro\Application\RegistryException as RegistryException;
/**
 * This controller expects the credentials and returns the JWT on success
 * on failure the IP is stored and the throttle period is increased when
 * more then the allowed attempts are made for this IP.
 *
 * We expect a public phrase(username or email) and a private phrase(password)
 * to be passed as a json object or as POST date. So first we check the headers
 * how the data is send. Then we retrieve, filter and sanitize this information
 * TODO ...
*/
$request = Application::getInstance()->getRequest();
$publicPhrase  = $request->getPostData('usr', FILTER_DEFAULT, 'bogus@localhost.com');
$privatePhrase = $request->getPostData('pwr', FILTER_DEFAULT, hash('sha256', 'administrator'));

/**
 * Get the Authentication Component and pass the credentials, return the generated JWT on success
 * Or a 401 code on failure. Return a 409(Conflict) when there is no Authentication Component
 */
try {

    $auth = Application::getInstance()->getComponent(Quadro\Authentication::getComponentName());
    $result = $auth->authenticate($publicPhrase, $privatePhrase);

    if ($result === false){
        Application::getInstance()->getResponse()->setStatusCode(401);
    } else {
        return $result;
    }

} catch ( RegistryException $e) {
    Application::getInstance()->getResponse()->addMessage('Authentication Component not registered');
    Application::getInstance()->getResponse()->setStatusCode(409);
}