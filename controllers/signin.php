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

use \Firebase\JWT\JWT;


$id = 1;
$firstname = "firstname";
$lastname = "lastname";
$email = "email";

// get the rawbody from the request
$secret_key = "YOUR_SECRET_KEY";
$issuer_claim = "quadro"; // this can be the servername
$audience_claim = "THE_AUDIENCE";
$issuedat_claim = time(); // issued at
$notBeforeClaim = $issuedat_claim + 10; //not before in seconds
$expire_claim = $issuedat_claim + 60; // expire time in seconds
$token = array(
    "iss" => $issuer_claim,
    "aud" => $audience_claim,
    "iat" => $issuedat_claim,
    "nbf" => $notBeforeClaim,
    "exp" => $expire_claim,
    "data" => array(
        "id" => $id,
        "firstname" => $firstname,
        "lastname" => $lastname,
        "email" => $email
    ));

http_response_code(200);
$jwt = JWT::encode($token, $secret_key);

return
    array(
        "message" => "Successful login.",
        "jwt" => $jwt,
        "email" => $email,
        "expireAt" => $expire_claim
    );