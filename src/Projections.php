<?php

namespace PhpInPractice\Matters;

use EventStore\Exception\ConnectionFailedException;
use EventStore\Exception\StreamDeletedException;
use EventStore\Exception\StreamNotFoundException;
use EventStore\Exception\UnauthorizedException;
use EventStore\Http\ResponseCode;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PhpInPractice\Matters\Projection\ProjectionDeletion;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Projections implements ProjectionsInterface
{
    /** @var string */
    private $url;

    /** @var ClientInterface */
    private $httpClient;

    /** @var callable[] */
    private $badCodeHandlers = [];

    /** @var ResponseInterface|null */
    private $lastResponse;

    public static function forUrl($url, ClientInterface $httpClient = null)
    {
        return new static($url, $httpClient);
    }

    public function get($name)
    {
        $url     = $this->projectionUrl($name) . '/statistics';
        $request = new Request('GET', $url);
        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);

        $data = current(json_decode($this->lastResponse->getBody()->getContents(), true)['projections']);

        $queryUrl = urldecode($data['queryUrl']);
        $request = new Request('GET', $queryUrl);
        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($queryUrl);

        $data2 = json_decode($this->lastResponse->getBody()->getContents(), true);
        $data['query'] = $data2['query'];

        return Projection\Definition::fromEventstore($data);
    }

    public function create(Credentials $credentials, Projection\Definition $definition)
    {
        $url = $this->projectionManagementUrl() .
            sprintf(
                '%s?name=%s&emit=%s&checkpoints=%s&enabled=%s',
                $definition->mode(),
                $definition->name(),
                'no',  // emit
                'yes', // checkpoints
                $definition->enabled() ? 'yes' : 'no'
            );

        $request = new Request(
            'POST',
            $url,
            [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Basic ' . $credentials->basicAuthentication()
            ],
            $definition->query()
        );

        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);
        if ($this->lastResponse->getStatusCode() != 201) {
            throw new \Exception('Failed to create projection');
        }
    }

    public function update(Credentials $credentials, Projection\Definition $definition)
    {
        // replace this with a url retrieved from the Definition
        $url = sprintf(
            '%s/query?emit=%s',
            $this->projectionUrl($definition->name()),
            'no' // emit
        );

        $request = new Request(
            'PUT',
            $url,
            [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Basic ' . $credentials->basicAuthentication()
            ],
            $definition->query()
        );

        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);
    }

    /**
     * Delete a stream
     *
     * @param Projection\Definition $definition
     * @param ProjectionDeletion        $mode Deletion mode (soft or hard)
     */
    public function delete(Credentials $credentials, Projection\Definition $definition, ProjectionDeletion $mode)
    {
        $this->stop($credentials, $definition);
        $url     = $this->projectionUrl($definition->name());
        $request = new Request(
            'DELETE',
            $url,
            [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Basic ' . $credentials->basicAuthentication()
            ]
        );

        if ($mode == ProjectionDeletion::HARD()) {
            $request = $request->withHeader('ES-HardDelete', 'true');
        }

        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);
    }

    public function reset(Credentials $credentials, Projection\Definition $definition)
    {
        $url     = $this->projectionUrl($definition->name()) . '/command/reset';
        $request = new Request(
            'POST',
            $url,
            [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Basic ' . $credentials->basicAuthentication()
            ]
        );

        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);
    }

    public function start(Credentials $credentials, Projection\Definition $definition)
    {
        $url     = $this->projectionUrl($definition->name()) . '/command/enable';
        $request = new Request(
            'POST',
            $url,
            [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Basic ' . $credentials->basicAuthentication()
            ]
        );

        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);
    }

    public function stop(Credentials $credentials, Projection\Definition $definition)
    {
        $url     = $this->projectionUrl($definition->name()) . '/command/disable';
        $request = new Request(
            'POST',
            $url,
            [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Basic ' . $credentials->basicAuthentication()
            ]
        );

        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);
    }

    public function result(Projection\Definition $definition)
    {
        // TODO: replace this with a url retrieved from the Definition
        $projectionUrl = $this->projectionUrl($definition->name()) . '/result';

        $this->sendRequest(new Request('GET', $projectionUrl));
        $this->ensureStatusCodeIsGood($projectionUrl);

        return $this->lastResponseAsJson();
    }

    public function state(Projection\Definition $definition)
    {
        // TODO: replace this with a url retrieved from the Definition
        $projectionUrl = $this->projectionUrl($definition->name()) . '/state';

        $this->sendRequest(new Request('GET', $projectionUrl));
        $this->ensureStatusCodeIsGood($projectionUrl);

        return $this->lastResponseAsJson();
    }

    private function __construct($url, ClientInterface $httpClient = null)
    {
        $this->url = $url;
        $this->httpClient = $httpClient ?: new Client();
        $this->checkConnection();
        $this->initBadCodeHandlers();
    }

    /**
     * @param  string $projectionName
     *
     * @return string
     */
    private function projectionUrl($projectionName)
    {
        return sprintf('%s/projection/%s', $this->url, $projectionName);
    }

    /**
     * @return string
     */
    private function projectionManagementUrl()
    {
        return sprintf('%s/projections/', $this->url);
    }

    /**
     * @param RequestInterface $request
     */
    private function sendRequest(RequestInterface $request)
    {
        try {
            $this->lastResponse = $this->httpClient->send($request);
        } catch (ClientException $e) {
            $this->lastResponse = $e->getResponse();
        }
    }

    /**
     * @throws ConnectionFailedException
     */
    private function checkConnection()
    {
        try {
            $request = new Request('GET', $this->url);
            $this->sendRequest($request);
        } catch (RequestException $e) {
            throw new ConnectionFailedException($e->getMessage());
        }
    }

    /**
     * @param  string $projectionUrl
     *
     * @throws StreamDeletedException
     * @throws StreamNotFoundException
     * @throws UnauthorizedException
     */
    private function ensureStatusCodeIsGood($projectionUrl)
    {
        $code = $this->lastResponse->getStatusCode();

        if (array_key_exists($code, $this->badCodeHandlers)) {
            $this->badCodeHandlers[$code]($projectionUrl);
        }
    }

    private function initBadCodeHandlers()
    {
        $this->badCodeHandlers = [
            ResponseCode::HTTP_NOT_FOUND => function ($projectionUrl) {
                throw new StreamNotFoundException(sprintf('No projection found at %s', $projectionUrl));
            },

            ResponseCode::HTTP_GONE => function ($projectionUrl) {
                throw new StreamDeletedException(
                    sprintf('Projection at %s has been permanently deleted', $projectionUrl)
                );
            },

            ResponseCode::HTTP_UNAUTHORIZED => function ($projectionUrl) {
                throw new UnauthorizedException(sprintf('Tried to projection stream %s got 401', $projectionUrl));
            }
        ];
    }

    private function lastResponseAsJson()
    {
        return json_decode($this->lastResponse->getBody(), true);
    }
}
