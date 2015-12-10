<?php
use PhpInPractice\Matters\Projection\Driver\EventStore;
use PhpInPractice\Matters\Projection\EventStoreRepository;
use PhpInPractice\Matters\Projection\StateSerializer\FromArray;

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/02-read-projection-state/Organisation.php';
include __DIR__ . '/02-read-projection-state/Organisations.php';

$projections = EventStore::forUrl('192.168.99.100:2113');

$repository = new EventStoreRepository(
    $projections,
    new FromArray(),
    Organisations::class,
    'Organisation'
);

var_dump($repository->result());
