<?php
use PhpInPractice\Matters\Projection\Definition;
use PhpInPractice\Matters\Projection\ProjectionDeletion;
use PhpInPractice\Matters\EventStoreProjections;

include __DIR__ . '/../vendor/autoload.php';

$query = <<<JS
fromAll().when({
    'ProjectCreated': function (s,e) {
        s[e.data.id] = {
            'id': e.data.id,
            'name': e.data.name
        };
    }
});
JS;

$credentials = \PhpInPractice\Matters\Credentials::fromUsernameAndPassword('admin', 'changeit');
$projectionDefinition = Definition::createNew('example_projects', $query);

$projections = EventStoreProjections::forUrl('192.168.99.100:2113');
$projections->create($credentials, $projectionDefinition);

$definition = $projections->get('example_projects');
var_dump($definition);

var_dump($projections->result($definition));

$projections->delete($credentials, $definition, ProjectionDeletion::SOFT());

