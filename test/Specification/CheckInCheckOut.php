<?php

declare(strict_types=1);

namespace Specification;

use ArrayIterator;
use Behat\Behat\Context\Context;
use Building\Domain\Aggregate\Building;
use Building\Domain\DomainEvent\CheckInAnomalyDetected;
use Building\Domain\DomainEvent\NewBuildingWasRegistered;
use Building\Domain\DomainEvent\UserCheckedIn;
use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\Aggregate\AggregateType;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Assert\Assert;

final class CheckInCheckOut implements Context
{
    private Uuid $buildingId;
    private ?Building $building = null;
    /** @psalm-var list<AggregateChanged> */
    private array $pastHistory = [];
    /** @psalm-var list<AggregateChanged>|null */
    private ?array $recordedEvents = null;

    public function __construct()
    {
        $this->buildingId = Uuid::uuid4();
    }

    /** @Given I registered a building */
    public function i_registered_a_building(): void
    {
        $this->pastHistory[] = NewBuildingWasRegistered::withName($this->buildingId, 'KanbanBox');
    }

    /** @Given /^"([^"]+)" checked into the building$/ */
    public function user_checked_into_the_building(string $username): void
    {
        $this->pastHistory[] = UserCheckedIn::toBuilding($this->buildingId, $username);
    }

    /** @When /^user "([^"]+)" checks into the building$/ */
    public function user_checks_into_the_building(string $username): void
    {
        $this->building()
            ->checkInUser($username);
    }

    /** @Then /^user "([^"]+)" should have been checked into the building$/ */
    public function user_shoud_have_been_checked_into_the_building(string $username): void
    {
        $event = $this->popNextRecordedEvent();

        Assert::isInstanceOf($event, UserCheckedIn::class);
        Assert::same($event->username(), $username);
    }

    /** @Then /^a check-in anomaly should have been detected for "([^"]+)"$/ */
    public function a_check_in_anomaly_should_have_been_detected_for_user(string $username): void
    {
        $event = $this->popNextRecordedEvent();

        Assert::isInstanceOf($event, CheckInAnomalyDetected::class);
        Assert::same($event->username(), $username);
    }

    private function building(): Building
    {
        if ($this->building !== null) {
            return $this->building;
        }

        return $this->building = (new AggregateTranslator())
            ->reconstituteAggregateFromHistory(
                AggregateType::fromAggregateRootClass(Building::class),
                new ArrayIterator($this->pastHistory)
            );
    }

    private function popNextRecordedEvent(): AggregateChanged
    {
        if (null !== $this->recordedEvents) {
            return array_shift($this->recordedEvents);
        }

        $this->recordedEvents = (new AggregateTranslator())
            ->extractPendingStreamEvents($this->building());

        return array_shift($this->recordedEvents);
    }
}
