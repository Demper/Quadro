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

// get the publicPhrase and secretPhrase from the request object
// This should be present in the raw body as a json string.

$object = Quadro\Request::getInstance()->getRawBodyAsJson();

// for testing purpose we get them from the query and create the json our self
//
$jsonString = '{"public": "'.$_GET['public'].'", "secret": "'.$_GET['secret'].'"}';
$object = json_decode($jsonString);


// validate and sanitize incoming data




return json_decode($jsonString);