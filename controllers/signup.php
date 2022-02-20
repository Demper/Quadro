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

// Get the publicPhrase and secretPhrase from the request object
// This should be present in the raw body as a json string.
if (false === ($object = Quadro\Application::request()->getRawBodyAsJson()))
{
    Quadro\Application::response()->setStatusCode(422);
    Quadro\Application::response()->addMessage('Expected {\'email\': \'\', \'password\': \'\'}');
};

return null;