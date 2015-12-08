<?php
namespace PhpInPractice\Matters;

use EventStore\StreamDeletion;
use GuzzleHttp\ClientInterface;

interface Projections
{
    public static function forUrl($url, ClientInterface $httpClient = null);

    public function get($name);

    public function create(Credentials $credentials, Projection\Definition $definition);

    public function update(Credentials $credentials, Projection\Definition $definition);

    /**
     * Delete a projection.
     *
     * @param Projection\Definition $definition
     * @param ProjectionDeletion        $mode Deletion mode (soft or hard)
     */
    public function delete(Credentials $credentials, Projection\Definition $definition, ProjectionDeletion $mode);

    public function reset(Credentials $credentials, Projection\Definition $definition);
    public function start(Credentials $credentials, Projection\Definition $definition);
    public function stop(Credentials $credentials, Projection\Definition $definition);

    public function result(Projection\Definition $definition, $partition = null);

    public function state(Projection\Definition $definition, $partition = null);
}
