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

if (Quadro\Application::getInstance()->getEnvironment() == Quadro\Application::ENV_PRODUCTION) {
    throw new Quadro\Dispatcher\ForbiddenException('Not available in production');
}

$app = Quadro\Application::getInstance();
$app->getResponse()->addMessage('Not Implemented yet');
$app->getResponse()->setStatusCode(409);
