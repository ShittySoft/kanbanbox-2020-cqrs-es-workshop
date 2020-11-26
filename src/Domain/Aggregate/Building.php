<?php

declare(strict_types=1);

namespace Building\Domain\Aggregate;

use Building\Domain\DomainEvent\NewBuildingWasRegistered;
use Prooph\EventSourcing\AggregateRoot;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Assert\Assert;

final class Building extends AggregateRoot
{
    private ?Uuid $uuid = null;

    private ?string $name = null;

    public static function new(string $name) : self
    {
        $self = new self();

        $self->recordThat(NewBuildingWasRegistered::withName(Uuid::uuid4(), $name));

        return $self;
    }

    public function checkInUser(string $username) : void
    {
        throw new \BadFunctionCallException('To be implemented: I should record a new event on the building');
    }

    public function checkOutUser(string $username) : void
    {
        throw new \BadFunctionCallException('To be implemented: I should record a new event on the building');
    }

    protected function whenNewBuildingWasRegistered(NewBuildingWasRegistered $event) : void
    {
        $this->uuid = Uuid::fromString($event->aggregateId());
        $this->name = $event->name();
    }

    /** {@inheritDoc} */
    protected function aggregateId() : string
    {
        Assert::notNull($this->uuid);

        return $this->uuid->toString();
    }
}
