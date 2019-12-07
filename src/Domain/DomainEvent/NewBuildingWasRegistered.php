<?php

declare(strict_types=1);

namespace Building\Domain\DomainEvent;

use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

final class NewBuildingWasRegistered extends AggregateChanged
{
    public static function withName(Uuid $building, string $name) : self
    {
        return self::occur($building->toString(), ['name' => $name]);
    }

    public function name() : string
    {
        return $this->payload['name'];
    }
}
