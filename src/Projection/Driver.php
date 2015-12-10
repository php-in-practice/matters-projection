<?php
namespace PhpInPractice\Matters\Projection;

use EventStore\StreamDeletion;
use GuzzleHttp\ClientInterface;
use PhpInPractice\Matters\Credentials;
use PhpInPractice\Matters\Projection\DeletionMode as ProjectionDeletion;

interface Driver
{
    public function get($name);

    public function create(Credentials $credentials, Definition $definition);

    public function update(Credentials $credentials, Definition $definition);

    /**
     * Delete a projection.
     *
     * @param Definition $definition
     * @param ProjectionDeletion        $mode Deletion mode (soft or hard)
     */
    public function delete(Credentials $credentials, Definition $definition, ProjectionDeletion $mode);

    public function reset(Credentials $credentials, Definition $definition);
    public function start(Credentials $credentials, Definition $definition);
    public function stop(Credentials $credentials, Definition $definition);

    public function result(Definition $definition, $partition = null);

    public function state(Definition $definition, $partition = null);
}
