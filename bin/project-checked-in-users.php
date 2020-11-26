#!/usr/bin/env php
<?php

namespace Building\App\Projection;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\StreamName;
use Psr\Container\ContainerInterface;

(function () {
    /** @var ContainerInterface $container */
    $container = require __DIR__ . '/../container.php';

    $eventStore = $container->get(EventStore::class);

    foreach ($eventStore->load(new StreamName('event_stream')) as $event) {
        // ... ???
    }

    // file_put_contents(...);
})();
