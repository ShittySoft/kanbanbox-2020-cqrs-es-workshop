#!/usr/bin/env php
<?php

namespace Building\App\Projection;

use Building\Domain\DomainEvent\UserCheckedIn;
use Building\Domain\DomainEvent\UserCheckedOut;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\StreamName;
use Psr\Container\ContainerInterface;

(function () {
    /** @var ContainerInterface $container */
    $container = require __DIR__ . '/../container.php';

    $eventStore = $container->get(EventStore::class);

    /** @psalm-var array<string, array<string, null>> */
    $usersByBuilding = [];

    foreach ($eventStore->load(new StreamName('event_stream'))->streamEvents() as $event) {
        if ($event instanceof UserCheckedIn) {
            $usersByBuilding[$event->aggregateId()][$event->username()] = null;
        }

        if ($event instanceof UserCheckedOut) {
            unset($usersByBuilding[$event->aggregateId()][$event->username()]);
        }
    }

    foreach ($usersByBuilding as $buildingId => $users) {
        file_put_contents(__DIR__ . '/../public/' . $buildingId, json_encode(\array_keys($users), \JSON_THROW_ON_ERROR));
    }
})();
