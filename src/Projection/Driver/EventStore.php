<?php

namespace PhpInPractice\Matters\Projection\Driver;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PhpInPractice\Matters\Credentials;
use PhpInPractice\Matters\Projection\Definition;
use PhpInPractice\Matters\Projection\Driver;
use PhpInPractice\Matters\Projection\Exception\ConnectionFailedException;
use PhpInPractice\Matters\Projection\Exception\ProjectionDeletedException;
use PhpInPractice\Matters\Projection\Exception\ProjectionNotFoundException;
use PhpInPractice\Matters\Projection\Exception\UnauthorizedException;
use PhpInPractice\Matters\Projection\DeletionMode as ProjectionDeletion;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class EventStore implements Driver
{
    /** @var string */
    private $url;

    /** @var ClientInterface */
    private $httpClient;

    /** @var callable[] */
    private $badCodeHandlers = [];

    /** @var ResponseInterface|null */
    private $lastResponse;

    /**
     * @param string               $url
     * @param ClientInterface|null $httpClient
     *
     * @return static
     */
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

        $definition = Definition::fromEventStore($data);
        $definition = $definition->withUpdatedQuery($this->query($definition));

        return $definition;
    }

    public function create(Credentials $credentials, Definition $definition)
    {
        $url = $this->projectionManagementUrl() .
            sprintf(
                '%s?name=%s&emit=%s&checkpoints=%s&enabled=%s',
                $definition->mode(),
                $definition->name(),
                $definition->mayEmitNewEvents() === true ? 'yes' : 'no',
                $definition->mode() === Definition::MODE_CONTINUOUS ? 'yes' : 'no', // checkpoints
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

    public function update(Credentials $credentials, Definition $definition)
    {
        // TODO: replace this with a url retrieved from the Definition
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

    public function delete(Credentials $credentials, Definition $definition, $mode = self::DELETION_SOFT)
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

        if ($mode == self::DELETION_HARD) {
            $request = $request->withHeader('ES-HardDelete', 'true');
        }

        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($url);
    }

    public function reset(Credentials $credentials, Definition $definition)
    {
        $url     = $definition->urls()['commands']['reset'];
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

    public function start(Credentials $credentials, Definition $definition)
    {
        $url     = $definition->urls()['commands']['enable'];
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

    public function stop(Credentials $credentials, Definition $definition)
    {
        $url     = $definition->urls()['commands']['disable'];
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

    public function result(Definition $definition, $partition = null)
    {
        // TODO: replace this with a url retrieved from the Definition
        $projectionUrl = $definition->urls()['result'];
        if ($partition) {
            $projectionUrl .= '?partition=' . $partition;
        }

        $this->sendRequest(new Request('GET', $projectionUrl));
        $this->ensureStatusCodeIsGood($projectionUrl);

        return $this->lastResponseAsJson();
    }

    public function state(Definition $definition, $partition = null)
    {
        $projectionUrl = $definition->urls()['state'];
        if ($partition) {
            $projectionUrl .= '?partition=' . $partition;
        }

        $this->sendRequest(new Request('GET', $projectionUrl));
        $this->ensureStatusCodeIsGood($projectionUrl);

        return $this->lastResponseAsJson();
    }

    /**
     * @param $definition
     *
     * @return mixed
     */
    public function query(Definition $definition)
    {
        $queryUrl = $definition->urls()['query'];
        $request  = new Request('GET', $queryUrl);
        $this->sendRequest($request);
        $this->ensureStatusCodeIsGood($queryUrl);

        return $this->lastResponseAsJson()['query'];
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
     * @throws ProjectionDeletedException
     * @throws ProjectionNotFoundException
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
            404 => function ($projectionUrl) {
                throw new ProjectionNotFoundException(sprintf('No projection found at %s', $projectionUrl));
            },

            410 => function ($projectionUrl) {
                throw new ProjectionDeletedException(
                    sprintf('Projection at %s has been permanently deleted', $projectionUrl)
                );
            },

            401 => function ($projectionUrl) {
                throw new UnauthorizedException(sprintf('Tried to projection stream %s got 401', $projectionUrl));
            }
        ];
    }

    private function lastResponseAsJson()
    {
        $response = json_decode($this->lastResponse->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidResponseException(
                'Unable to interpret response from eventstore, system reports: ' . json_last_error_msg()
            );
        }

        return $response;
    }
}
