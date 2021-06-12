<?php

declare(strict_types=1);

// Setting the timezone is important if you want specific logs
date_default_timezone_set('Europe/Brussels');

require __DIR__ . '/vendor/autoload.php';

use MailgunLogger\Logger;
use Symfony\Component\HttpClient\CurlHttpClient;

// Create an instance of a HTTP client
$client = new CurlHttpClient();

// Create an instance of our Logger
$logger = new Logger($client,'<your-api-key>');

$parameters = [
    'begin' => new DateTime('06/12/2021 00:00'),
    'end' => new DateTime('06/12/2021 23:59'),
    'filter' => [
        'event' => 'delivered',
    ]
];

// All the logs matching the filter will be returned as an associative array
$logs = $logger->get('eu', '<your-domain>', $parameters);

// In this example, we dump all recipients to a CSV file
$fp = fopen('demo.csv', 'w');

foreach ($logs as $row) {
    fputcsv($fp, [$row['recipient']]);
}

fclose($fp);