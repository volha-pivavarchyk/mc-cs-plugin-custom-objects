<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomObjectValueEvent;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomItemDisplayValueSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CustomObjectModel $customObjectModel,
        private CustomItemModel $customItemModel
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
//            CoreEvents::VIEW_INJECT_CUSTOM_OBJECT_VALUE => ['onDisplayItemLink', 0],
        ];
    }

    // @aivie Display a user-friendly custom object data in the form result page
    // @todo Create Mautic PR
    public function onDisplayItemLink(CustomObjectValueEvent $event): void
    {
        // try {
        //     $object = $this->customObjectModel->fetchEntityByAlias($event->getObject());
        // } catch (NotFoundException $e) {
        //     // Do nothing if the custom object doesn't exist.
        //     return;
        // }

        // $item       = $this->customItemModel->getEntity($event->getValue());
        // $properties = [
        //     'link'           => CustomItemRouteProvider::ROUTE_VIEW,
        //     'linkParameters' => [
        //         'objectId'   => $object->getId(),
        //         'itemId'     => isset($item) ? $item->getId() : '%alias%',
        //     ],
        // ];

        // $event->setProperties($properties);

        // if (isset($item)) {
        //     $event->setValue($item->getName());

        //     if (!empty($event->getField()) && $event->getObject() !== $event->getField()) {
        //         $fields = $this->customItemModel->populateCustomFields($item)->getCustomFieldValues();
        //         foreach ($fields as $field) {
        //             if ($field->getCustomField()->getAlias() === $event->getField()) {
        //                 $event->setValue($field->getValue());
        //             }
        //         }
        //     }
        // }
    }
}
