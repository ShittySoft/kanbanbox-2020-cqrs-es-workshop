<?php

declare(strict_types=1);

namespace Building\Domain\Aggregate;

use Building\Domain\DomainEvent\CheckInAnomalyDetected;
use Building\Domain\DomainEvent\NewBuildingWasRegistered;
use Building\Domain\DomainEvent\UserCheckedIn;
use Building\Domain\DomainEvent\UserCheckedOut;
use Prooph\EventSourcing\AggregateRoot;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Assert\Assert;
use function array_key_exists;

final class Building extends AggregateRoot
{
    private ?Uuid $uuid = null;

    private ?string $name = null;
    /** @psalm-var array<string, null> */
    private array $checkedInUsers = [];

    public static function new(string $name) : self
    {
        $self = new self();

        $self->recordThat(NewBuildingWasRegistered::withName(Uuid::uuid4(), $name));

        return $self;
    }

    public function checkInUser(string $username) : void
    {
        $id = $this->uuid;

        Assert::notNull($id);

        $anomaly = array_key_exists($username, $this->checkedInUsers);

        $this->recordThat(UserCheckedIn::toBuilding($id, $username));

        if ($anomaly) {
            $this->recordThat(CheckInAnomalyDetected::inBuilding($id, $username));
        }
    }

    public function checkOutUser(string $username) : void
    {
        $id = $this->uuid;

        Assert::notNull($id);

        $anomaly = ! array_key_exists($username, $this->checkedInUsers);

        $this->recordThat(UserCheckedOut::ofBuilding($id, $username));

        if ($anomaly) {
            $this->recordThat(CheckInAnomalyDetected::inBuilding($id, $username));
        }
    }

    protected function whenNewBuildingWasRegistered(NewBuildingWasRegistered $event) : void
    {
        $this->uuid = Uuid::fromString($event->aggregateId());
        $this->name = $event->name();
    }

    protected function whenUserCheckedIn(UserCheckedIn $event) : void
    {
        $this->checkedInUsers[$event->username()] = null;
    }

    protected function whenUserCheckedOut(UserCheckedOut $event) : void
    {
        unset($this->checkedInUsers[$event->username()]);
    }

    protected function whenCheckInAnomalyDetected(CheckInAnomalyDetected $event) : void
    {
        // Empty, on purpose
    }

    /** {@inheritDoc} */
    protected function aggregateId() : string
    {
        Assert::notNull($this->uuid);

        return $this->uuid->toString();
    }
}
