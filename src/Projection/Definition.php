<?php

namespace PhpInPractice\Matters\Projection;

final class Definition
{
    const MODE_CONTINUOUS = 'continuous';
    const MODE_TRANSIENT = 'transient';

    private $name = '';
    private $mode = self::MODE_CONTINUOUS;
    private $query = '';
    private $status = 'stopped';
    private $progress = 0;
    private $urls = [];

    public function name()
    {
        return $this->name;
    }

    public function mode()
    {
        return $this->mode;
    }

    public function query()
    {
        return $this->query;
    }

    public function enabled()
    {
        return $this->status !== 'stopped';
    }

    public function progress()
    {
        return $this->progress;
    }

    public function urls()
    {
        return $this->urls;
    }

    public static function createNew($name, $query, $mode = self::MODE_CONTINUOUS, $enabled = true)
    {
        $definition = new static();
        $definition->name = $name;
        $definition->mode = $mode;
        $definition->enabled = $enabled;
        $definition->query = $query;

        return $definition;
    }

    public static function withUpdatedQuery(Definition $oldDefinition, $query)
    {
        $definition = clone $oldDefinition;
        $definition->query = $query;

        return $definition;
    }

    /**
     * @param array $data
     *
     * @return Definition
     */
    public static function fromEventstore(array $data)
    {
        $definition = new static();
        $definition->name = $data['name'];
        $definition->mode = strtolower($data['mode']);
        $definition->status = strtolower($data['status']);
        $definition->query = $data['query'];
        $definition->progress = $data['progress'];
        $definition->urls = [
            'state' => $data['stateUrl'],
            'result' => $data['resultUrl'],
            'query' => urldecode($data['queryUrl']),
            'commands' => [
                'enable' => urldecode($data['enableCommandUrl']),
                'disable' => urldecode($data['disableCommandUrl']),
                // not exposed yet so we fake it
                'reset' => urldecode(str_replace('disable', 'reset', $data['disableCommandUrl'])),
            ]
        ];

        return $definition;
/*
  "coreProcessingTime": 31,
  "version": 5,
  "epoch": 5,
  "effectiveName": "projects",
  "writesInProgress": 0,
  "readsInProgress": 0,
  "partitionsCached": 1,
  "status": "Running",
  "stateReason": "",
  "name": "projects",
  "mode": "Continuous",
  "position": "C:451060301/P:451060301; PhpInPractice\\\\Cid\\\\ProjectStarted: -1; ",
  "progress": 100.0,
  "lastCheckpoint": "C:451060301/P:451060301; PhpInPractice\\\\Cid\\\\ProjectStarted: -1; ",
  "eventsProcessedAfterRestart": 11,
  "statusUrl": "http://192.168.99.100:2113/projection/projects",
  "stateUrl": "http://192.168.99.100:2113/projection/projects/state",
  "resultUrl": "http://192.168.99.100:2113/projection/projects/result",
  "queryUrl": "http://192.168.99.100:2113/projection/projects/query%3Fconfig=yes",
  "enableCommandUrl": "http://192.168.99.100:2113/projection/projects/command/enable",
  "disableCommandUrl": "http://192.168.99.100:2113/projection/projects/command/disable",
  "checkpointStatus": "",
  "bufferedEvents": 0,
  "writePendingEventsBeforeCheckpoint": 0,
  "writePendingEventsAfterCheckpoint": 0
*/
    }
}
