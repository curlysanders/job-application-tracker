<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Lingoda\DomainEventsBundle\Domain\Model\OutboxStore;
use Lingoda\DomainEventsBundle\Infra\Doctrine\Entity\OutboxRecord;

#[AsDoctrineListener(event: 'onFlush', priority: -999, connection: 'default')]
final class PersistRecordedDomainEventsSubscriber
{
    private static ?\ReflectionProperty $outboxEventType = null;

    public function __construct(private readonly OutboxStore $outboxStore)
    {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entities) {
            $this->persistEvents($entities, $unitOfWork);
        }
        $this->persistEvents($unitOfWork->getScheduledEntityInsertions(), $unitOfWork);

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof OutboxRecord) {
                $unitOfWork->computeChangeSet($entityManager->getClassMetadata(OutboxRecord::class), $entity);
            }
        }
    }

    /** @param array<array-key, object> $entities */
    private function persistEvents(
        array $entities,
        UnitOfWork $unitOfWork,
    ): void {
        foreach ($entities as $entity) {
            if (!$entity instanceof RecordsEvents) {
                continue;
            }

            foreach ($entity->getRecordedEvents() as $event) {
                $outboxEvent = new LingodaDomainEvent($event);
                $this->outboxStore->append($outboxEvent);
                $this->restoreDomainEventType($unitOfWork, $outboxEvent, $event::class);
            }
            $entity->clearRecordedEvents();
        }
    }

    /** @param class-string $eventType */
    private function restoreDomainEventType(
        UnitOfWork $unitOfWork,
        LingodaDomainEvent $event,
        string $eventType,
    ): void {
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof OutboxRecord || $entity->getDomainEvent() !== $event) {
                continue;
            }

            self::$outboxEventType ??= new \ReflectionProperty(OutboxRecord::class, 'eventType');
            self::$outboxEventType->setValue($entity, $eventType);

            return;
        }
    }
}
