<?php

$curl = curl_init();

$request = '{
        "name": "generateToken",
        "param": {
            "email": "test@gmail.com",
            "password": "test"
        }
    }';

curl_setopt($curl, CURLOPT_URL, "localhost/jwt/index.php");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type:application/json']);

curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($curl);
$errors = curl_error($curl);

if ($errors) {
    echo "Curl error: " . $errors;
} else {
    header("Content-type: application/json");
    $response = json_decode($result, true);
    print_r($response['response']['result']['token']);

    print_r($result);
}
