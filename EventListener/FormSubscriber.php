<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\FormBundle\Crate\ObjectCrate;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Crate\FieldCrate;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    private CustomObjectRepository $customObjectRepository;
    private CustomFieldRepository $customFieldRepository;

    public function __construct(
        CustomObjectRepository $customObjectRepository,
        CustomFieldRepository $customFieldRepository
    ) {
        $this->customObjectRepository = $customObjectRepository;
        $this->customFieldRepository  = $customFieldRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::ON_OBJECT_COLLECT        => ['onObjectCollect', 0],
            FormEvents::ON_FIELD_COLLECT         => ['onFieldCollect', 0],
//            FormEvents::ON_EXECUTE_SUBMIT_ACTION => [
//                ['onFormSubmitActions', 0],
//            ],
        ];
    }

    public function onObjectCollect(ObjectCollectEvent $event): void
    {
        foreach ($this->customObjectRepository->getEntities() as $entity) {
            $event->appendObject(new ObjectCrate($entity->getId(), $entity->getName()));
        }
    }

    public function onFieldCollect(FieldCollectEvent $event): void
    {
        foreach ($this->customObjectRepository->getEntities() as $entity) {
            if ($event->getObject() === $entity->getId()) {
                foreach ($entity->getCustomFields() as $field) {
                    $event->appendField(new FieldCrate($field->getId(), $field->getName(), $field->getType(), []));
                }
            }
        }
    }

//    public function onFormSubmitActions(SubmissionEvent $event): void
//    {
//    }
}