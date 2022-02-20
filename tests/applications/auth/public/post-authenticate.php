<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$credentials = [];
$credentials['email'] = $argv[1]??'';
$credentials['pass'] = $argv[2]??'';
$data = json_encode($credentials);
$url = 'http://localhost:8080/accounts/authenticate';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response  = curl_exec($ch);
curl_close($ch);

print_r($response);
//print_r(json_decode($response, true));