<?php
namespace PhpInPractice\Matters\Projection;

use EventStore\StreamDeletion;
use GuzzleHttp\ClientInterface;
use PhpInPractice\Matters\Credentials;

interface Driver
{
    const DELETION_SOFT = 'soft';
    const DELETION_HARD = 'hard';

    public function get($name);

    public function create(Credentials $credentials, Definition $definition);

    public function update(Credentials $credentials, Definition $definition);

    public function delete(Credentials $credentials, Definition $definition, $mode = self::DELETION_SOFT);

    public function reset(Credentials $credentials, Definition $definition);
    public function start(Credentials $credentials, Definition $definition);
    public function stop(Credentials $credentials, Definition $definition);

    public function result(Definition $definition, $partition = null);

    public function state(Definition $definition, $partition = null);
}
