<?php

namespace PhpInPractice\Matters\Projection;

use PhpInPractice\Matters\ProjectionsDriver;
use Mockery as m;

/**
 * @coversDefaultClass PhpInPractice\Matters\Projection\Definition
 * @covers ::<private>
 */
final class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers ::fromEventStore
     * @covers ::name
     * @covers ::enabled
     * @covers ::status
     * @covers ::query
     * @covers ::urls
     * @covers ::mode
     * @covers ::progress
     * @covers ::mayEmitNewEvents
     */
    public function it_should_create_a_definition_from_data_retrieved_from_the_event_store()
    {
        $mode = "Continuous";
        $name = "Organisation";
        $status = "Running";
        $progress = "100.0";
        $resultUrl = "http://192.168.99.100:2113/projection/Organisation/result";
        $stateUrl = "http://192.168.99.100:2113/projection/Organisation/state";
        $statusUrl = "http://192.168.99.100:2113/projection/Organisation";
        $queryUrl = "http://192.168.99.100:2113/projection/Organisation/query%3Fconfig=yes";
        $enableCommandUrl = "http://192.168.99.100:2113/projection/Organisation/command/enable";
        $disableCommandUrl = "http://192.168.99.100:2113/projection/Organisation/command/disable";
        $resetCommandUrl = "http://192.168.99.100:2113/projection/Organisation/command/reset";

        $json = <<<JSON
{
  "coreProcessingTime": 2,
  "version": 4,
  "epoch": 4,
  "effectiveName": "{$name}",
  "writesInProgress": 0,
  "readsInProgress": 0,
  "partitionsCached": 1,
  "status": "{$status}",
  "stateReason": "",
  "name": "{$name}",
  "mode": "{$mode}",
  "position": "C:63821834/P:63821834; Psybizz\\\\Neon\\\\DomainModel\\\\Organisation\\\\OrganisationRegistered: -1; ",
  "progress": {$progress},
  "lastCheckpoint": "C:63821834/P:63821834; Psybizz\\\\Neon\\\\DomainModel\\\\Organisation\\\\OrganisationRegistered: -1; ",
  "eventsProcessedAfterRestart": 3,
  "statusUrl": "{$statusUrl}",
  "stateUrl": "{$stateUrl}",
  "resultUrl": "{$resultUrl}",
  "queryUrl": "{$queryUrl}",
  "enableCommandUrl": "{$enableCommandUrl}",
  "disableCommandUrl": "{$disableCommandUrl}",
  "checkpointStatus": "",
  "bufferedEvents": 0,
  "writePendingEventsBeforeCheckpoint": 0,
  "writePendingEventsAfterCheckpoint": 0
}
JSON;

        $eventStoreProjectionData = json_decode($json, true);

        $expectedUrls = [
            'state' => urldecode($stateUrl),
            'result' => urldecode($resultUrl),
            'query' => urldecode($queryUrl),
            'commands' => [
                'enable' => urldecode($enableCommandUrl),
                'disable' => urldecode($disableCommandUrl),
                'reset' => urldecode($resetCommandUrl),
            ]
        ];
        $definition = Definition::fromEventStore($eventStoreProjectionData);
        $this->assertSame(strtolower($status), $definition->status());
        $this->assertSame(true, $definition->enabled());
        $this->assertSame(100.0, $definition->progress());
        $this->assertSame(strtolower($mode), $definition->mode());
        $this->assertSame('', $definition->query());
        $this->assertSame($expectedUrls, $definition->urls());
        $this->assertSame(null, $definition->mayEmitNewEvents());
    }

    /**
     * @test
     * @covers ::createNew
     * @covers ::mode
     * @covers ::query
     * @covers ::name
     * @covers ::mayEmitNewEvents
     */
    public function it_should_create_an_empty_definition_for_inserting_a_projection()
    {
        $name       = 'Organisation';
        $query      = 'MyQuery';

        $definition = Definition::createNew($name, $query, true, Definition::MODE_TRANSIENT);

        $this->assertSame($name, $definition->name());
        $this->assertSame($query, $definition->query());
        $this->assertSame(Definition::MODE_TRANSIENT, $definition->mode());
        $this->assertSame(true, $definition->mayEmitNewEvents());
    }

    /**
     * @test
     * @covers ::withUpdatedQuery
     * @covers ::mode
     * @covers ::query
     * @covers ::name
     * @covers ::mayEmitNewEvents
     */
    public function it_should_update_the_query_and_return_a_new_object()
    {
        $name       = 'Organisation';
        $query      = 'MyNewQuery';

        $definition = Definition::createNew($name, 'MyQuery');

        $newDefinition = $definition->withUpdatedQuery($query);

        $this->assertNotSame($newDefinition, $definition);
        $this->assertSame($name, $newDefinition->name());
        $this->assertSame($query, $newDefinition->query());
        $this->assertSame(Definition::MODE_CONTINUOUS, $newDefinition->mode());
        $this->assertSame(false, $definition->mayEmitNewEvents());
    }
}
