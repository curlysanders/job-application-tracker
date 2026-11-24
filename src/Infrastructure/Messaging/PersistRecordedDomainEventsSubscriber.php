<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Lingoda\DomainEventsBundle\Domain\Model\OutboxStore;
use Lingoda\DomainEventsBundle\Infra\Doctrine\Entity\OutboxRecord;

#[AsDoctrineListener(event: 'onFlush', priority: -999, connection: 'default')]
final readonly class PersistRecordedDomainEventsSubscriber
{
    public function __construct(private OutboxStore $outboxStore)
    {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entities) {
            $this->persistEvents($entities);
        }
        $this->persistEvents($unitOfWork->getScheduledEntityInsertions());

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof OutboxRecord) {
                $unitOfWork->computeChangeSet($entityManager->getClassMetadata(OutboxRecord::class), $entity);
            }
        }
    }

    /** @param array<array-key, object> $entities */
    private function persistEvents(array $entities): void
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof RecordsEvents) {
                continue;
            }

            foreach ($entity->getRecordedEvents() as $event) {
                $outboxEvent = new LingodaDomainEvent($event);
                $this->outboxStore->append($outboxEvent);
            }
            $entity->clearRecordedEvents();
        }
    }
}
