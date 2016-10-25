<?php

namespace PhpInPractice\Matters\Projection\Driver;

use GuzzleHttp\ClientInterface;
use PhpInPractice\Matters\Projection\Proxy;

final class EventStoreProxy implements Proxy
{
    /** @var string */
    private $url;

    /** @var ClientInterface */
    private $httpClient;

    /**
     * @param string $url
     * @param ClientInterface|null $httpClient
     *
     * @return static
     */
    public static function forUrl($url, ClientInterface $httpClient = null)
    {
        return new static($url, $httpClient);
    }

    public function query($projectionName, $partition = null)
    {
        $url = sprintf('%s/projection/%s', $this->url, $projectionName);

        if ($partition) {
            $url .= "?q=" . $partition;
        }

        $response = $this->httpClient->request('GET', $url);

        return json_decode($response->getBody(), true);
    }

    private function __construct($url, ClientInterface $httpClient = null)
    {
        $this->url = $url;
        $this->httpClient = $httpClient ?: new Client();
    }
}
