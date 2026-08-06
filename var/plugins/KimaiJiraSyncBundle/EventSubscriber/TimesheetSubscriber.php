<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetDeletePreEvent;
use App\Event\TimesheetStopPostEvent;
use App\Event\TimesheetUpdatePostEvent;
use KimaiPlugin\KimaiJiraSyncBundle\Service\WorklogSyncServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TimesheetSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorklogSyncServiceInterface $syncService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetCreatePostEvent::class => ['onTimesheetCreate', 10],
            TimesheetUpdatePostEvent::class => ['onTimesheetUpdate', 10],
            TimesheetStopPostEvent::class   => ['onTimesheetStop', 10],
            TimesheetDeletePreEvent::class  => ['onTimesheetDelete', 10],
        ];
    }

    public function onTimesheetCreate(TimesheetCreatePostEvent $event): void
    {
        $this->syncService->syncCreated($event->getTimesheet());
    }

    public function onTimesheetUpdate(TimesheetUpdatePostEvent $event): void
    {
        $this->syncService->syncUpdated($event->getTimesheet());
    }

    public function onTimesheetStop(TimesheetStopPostEvent $event): void
    {
        $this->syncService->syncCreated($event->getTimesheet());
    }

    public function onTimesheetDelete(TimesheetDeletePreEvent $event): void
    {
        $this->syncService->syncDeleted($event->getTimesheet());
    }
}
