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
    private $mayEmitNewEvents = null;

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

    public function status()
    {
        return $this->status;
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

    /**
     * This method returns whether the projection may emit new events out of its own accord.
     *
     * In some situations this is unknown because the information is not exposed; in this case null is returned to
     * indicate that this is not known.
     *
     * @return null|boolean
     */
    public function mayEmitNewEvents()
    {
        return $this->mayEmitNewEvents;
    }

    public function withUpdatedQuery($query)
    {
        $definition = clone $this;
        $definition->query = $query;

        return $definition;
    }

    public static function createNew($name, $query, $mayEmitNewEvents = false, $mode = self::MODE_CONTINUOUS)
    {
        $definition = new static();
        $definition->name = $name;
        $definition->mode = $mode;
        $definition->query = $query;
        $definition->mayEmitNewEvents = $mayEmitNewEvents;

        return $definition;
    }

    /**
     * @param array $data
     *
     * @return Definition
     */
    public static function fromEventStore(array $data)
    {
        $definition = new static();
        $definition->name = $data['name'];
        $definition->mode = strtolower($data['mode']);
        $definition->status = strtolower($data['status']);
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
    }
}
