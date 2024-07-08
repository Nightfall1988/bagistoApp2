<?php

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://static.xdconnects.com/ProductImages/Large/5031__S_0__0095d89f87e142c9a2fbf5815115bab8.jpg");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // Increase the timeout to 60 seconds
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$output = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
