<?php

declare(strict_types=1);

namespace MailgunLogger;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTime;

final class Logger
{
    private const API_URL_EU = 'https://api.eu.mailgun.net';
    private const API_URL_US = 'https://api.mailgun.net';

    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(
        HttpClientInterface $client,
        string $apiKey
    ) {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function get(string $region, string $domain, array $parameters): array
    {
        $logs = [];

        // Set the region
        $apiUrl = ($region === 'us' ? self::API_URL_US : self::API_URL_EU);

        // Initial request (to the base Events API end-point
        $response = $this->makeCall($apiUrl . sprintf('/v3/%s/events', $domain), $parameters);

        foreach ($response['items'] as $item) {
            $logs[] = $item;
        }

        $nextUrl = $response['paging']['next'];

        // If the result set fits into 1 request, return the result
        if (count($response['items']) < 300) {
            return $logs;
        }

        // Follow-up requests (to the paginated URL we got from the initial request)
        do {
            $response = $this->makeCall($nextUrl, $parameters);

            foreach ($response['items'] as $item) {
                $logs[] = $item;
            }

            $nextUrl = $response['paging']['next'];
        } while ($response['items'] != null);

        return $logs;
    }

    /**
     * Performs an API call to the supplied URL. The parameters should always contain
     * a begin and end DateTime object and optionally a filter.
     *
     * The filter can be any filter field as defined in the Mailgun Events API:
     * https://documentation.mailgun.com/en/latest/api-events.html#filter-field
     *
     * @param string $url
     * @param array $parameters
     * @return object
     */
    private function makeCall(string $url, array $parameters): array
    {
        if (!array_key_exists('begin', $parameters) ||
            !$parameters['begin'] instanceof DateTime) {
            throw new \InvalidArgumentException('Missing begin DateTime.');
        }

        if (!array_key_exists('end', $parameters) ||
            !$parameters['begin'] instanceof DateTime) {
            throw new \InvalidArgumentException('Missing end DateTime.');
        }

        $queryParameters = [
            'begin' => $parameters['begin']->getTimeStamp(),
            'end' => $parameters['end']->getTimeStamp(),
            'limit' => 300,
        ];

        if (array_key_exists('filter', $parameters) && $parameters['filter'] !== null) {
            $queryParameters = array_merge($queryParameters, $parameters['filter']);
        }

        $response = $this->client->request(
            'GET',
            $url,
            [
                'headers' => [ "Authorization" => "Basic " . base64_encode ( "api:" . $this->apiKey) ],
                'query' => $queryParameters
            ]
        );

        return json_decode($response->getContent(), true);
    }
}
