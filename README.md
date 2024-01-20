# rpurinton/https

Abstract Class for HTTPS using Magic Methods (PHP/Composer)

# Usage

```
composer require rpurinton/https
```

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use RPurinton\HTTPS\HTTPSRequest;

// Return the response as a string
$response_string = (string)(new HTTPSRequest([
    'url' => 'https://raw.githubusercontent.com/rpurinton/https/master/example.json',
    'method' => 'GET',
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
            'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
    ],
    'body' => '',
]));
echo $response_string;

// Return the response as an array
$response_array = (array)(new HTTPSRequest([
    'url' => 'https://raw.githubusercontent.com/rpurinton/https/master/example.json',
    'method' => 'GET',
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
            'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
    ],
    'body' => '',
]));
print_r($response_array);

// Return the response as an object
$response_object = (object)(new HTTPSRequest([
    'url' => 'https://raw.githubusercontent.com/rpurinton/https/master/example.json',
    'method' => 'GET',
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
            'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
    ],
    'body' => '',
]));
print_r($response_object);
```

