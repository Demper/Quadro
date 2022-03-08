<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$jwt = 'eyJhbGciOiJIUzI1NiIsInR5cGUiOiJKV1QifQ.eyJkYXRhIjoicm9iLmRlbW1lbmllQGdtYWlsLmNvbSIsImlhdCI6MTY0NjA1ODgwNSwiZXhwIjoxNjQ2MDU5NzA1fQ.bhoc_Tq2wjLmCd6U4YH0jf7L2I23nJgmcSO8WpPQopM';

$requestUri = $argv[1]??'';
$data = [];
for( $i = 2; $i < count($argv); $i++) {
    if (str_contains($argv[$i], '=')) {
        $arg = explode('=', $argv[$i]);
        $data[$arg[0]] = $arg[1];
    } else {
        $data[] = trim($argv[$i], '"');
    }
}
$data = json_encode($data);
$url = 'http://localhost:8080/' . $requestUri;
echo 'Sending: --------------------------------------', PHP_EOL;
print_r($data);
echo PHP_EOL, '-----------------------------------------------', PHP_EOL;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $jwt]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt( $ch , CURLOPT_HEADER , true );
curl_setopt($ch, CURLOPT_VERBOSE, true);
$response  = curl_exec($ch);
curl_close($ch);

print_r($response);
exit(0);