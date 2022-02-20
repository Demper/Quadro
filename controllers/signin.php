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



// #1 get the authentication component
// #2 get the json body of the request
// #3 get check email and password in authentication component
// #4 login or return an error


// Get the publicPhrase and secretPhrase from the request object
// This should be present in the raw body as a json string.
//if (false === ($object = Quadro\Application::request()->getRawBodyAsJson()))
//{
//    Quadro\Application::response()->setStatusCode(422);
//    Quadro\Application::response()->addMessage('Expected {\'email\': \'\', \'password\': \'\'}');
//};

/*
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


Quadro\Application::response()->addMessage("Successful login.");
Quadro\Application::response()->setReturnType('JWT');
exit($jwt);
$o = new stdClass();
$o->token= $jwt;
$o->expireAt = $expire_claim;
return $o;
*/

function generateJwt($headers, $payload, $secret = 'secret')
{
    $headersEncoded = base64UrlEncode(json_encode($headers));
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('SHA256', "$headersEncoded.$payloadEncoded", $secret, true);
    $signatureEncoded = base64urlEncode($signature);
    return "$headersEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * Both Base64 and Base64url are ways to encode binary data in string form.
 * The problem with Base64 is that it contains the characters +, /,
 * and =, which have a reserved meaning in some filesystem names and URLs.
 * So base64url solves this by replacing + with - and / with _. The trailing
 * padding character = can be eliminated when not needed.
 *
 * @param $str
 * @return string
 */
function base64UrlEncode($str) : string
{
    return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
}
function base64UrlDecode(string $base64Url): string
{
    return base64_decode(strtr($base64Url, '-_', '+/'));
}


$secret = 'Humpty Dumpty Set On The Wall';
$headers = [
    'alg' => 'HS256',
    'type' => 'jwt'
];
$payload = [
    'name' => 'Bogus'
];


exit(generateJwt($headers, $payload, $secret));