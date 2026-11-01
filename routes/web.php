<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Okay</title>
    <style>
        body {
            align-items: center;
            background: #fff;
            display: flex;
            font-family: sans-serif;
            height: 100vh;
            justify-content: center;
            margin: 0;
        }
    </style>
</head>
<body>
    <main>Okay</main>
</body>
</html>');
});
