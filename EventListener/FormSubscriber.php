<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\FormBundle\Crate\ObjectCrate;
use Mautic\FormBundle\Event\FieldAssignEvent;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\Event\FieldValueEvent;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Crate\FieldCrate;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CustomObjectModel $customObjectModel,
        private CustomItemModel $customItemModel,
        private CustomItemXrefContactRepository $customItemXrefContactRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::ON_OBJECT_COLLECT  => ['onObjectCollect', 0],
            FormEvents::ON_FIELD_COLLECT   => ['onFieldCollect', 0],
            FormEvents::ON_FIELD_ASSIGN    => ['onFieldAssign', 0],
            FormEvents::FORM_ON_SUBMIT     => ['onFormSubmit', 0],
            FormEvents::ON_FIELD_VALUE     => ['onDisplayItemLink', 0],
        ];
    }

    public function onObjectCollect(ObjectCollectEvent $event): void
    {
        foreach ($this->customObjectModel->fetchEntities() as $entity) {
            $event->appendObject(new ObjectCrate($entity->getAlias(), $entity->getName()));
        }
    }

    public function onFieldCollect(FieldCollectEvent $event): void
    {
        try {
            $object = $this->customObjectModel->fetchEntityByAlias($event->getObject());
        } catch (NotFoundException $e) {
            // Do nothing if the custom object doesn't exist.
            return;
        }

        $items = $this->customItemModel->fetchCustomItemsForObject($object);

        if (count($items) > 0) {
            foreach ($items as $item) {
                $list[] = [
                    'label' => $item->getName(),
                    'value' => $item->getId(),
                ];
//                $list[$item->getId()] = $item->getName();
            }

            $event->appendField(new FieldCrate($object->getAlias(), 'Name', 'text', ['list' => $list ?? []]));
        }

        foreach ($object->getCustomFields()->getValues() as $field) {
            $list = $this->getCustomFieldValues($field, $items);
            $event->appendField(new FieldCrate($field->getAlias(), $field->getName(), $field->getType(), ['list' => $list]));
        }
    }

    public function onFieldAssign(FieldAssignEvent $event): void
    {
        if (null === $event->getLead()) {
            return;
        }

        try {
            $object = $this->customObjectModel->fetchEntityByAlias($event->getObject());
        } catch (NotFoundException $e) {
            // Do nothing if the custom object doesn't exist.
            return;
        }

        $items     = $this->customItemModel->fetchCustomItemsForObject($object);
        $contactId = $event->getLead()?->getId();

        $items = array_filter(
            $items,
            function ($item) use ($contactId) {
                $ids = $this->customItemXrefContactRepository->getContactIdsLinkedToCustomItem((int)$item->getId(), 200, 0);
                $ids = array_column($ids, 'contact_id');
                return in_array($contactId, $ids);
            }
        );

        $value = array_map(
            fn ($item) => $item->getId(),
            $items
        );
        $event->setValue($value);
    }

    public function onFormSubmit(SubmissionEvent $event): void
    {
        if (null === $event->getLead()) {
            return;
        }

        $results = $event->getResults();
        $fields  = $event->getForm()->getFields();

        foreach ($fields as $field) {
            if ($field->getMappedObject() === null || $field->getMappedObject() === 'contact' || $field->getMappedObject() === 'company') {
                continue;
            }

            try {
                $object = $this->customObjectModel->fetchEntityByAlias($field->getMappedObject());
            } catch (NotFoundException $e) {
                // Do nothing if the custom object doesn't exist.
                continue;
            }

            if (isset($results[$field->getAlias()])) {
                $itemIds    = explode(',', $results[$field->getAlias()]);
                $properties = $field->getProperties();
                $lead       = $event->getLead();

                foreach ($itemIds as $itemId) {
                    $customItem = $this->customItemModel->fetchEntity((int) $itemId);

                    switch ($properties['saveFieldOption']) {
                        case 1:
                            $this->customItemModel->linkEntity($customItem, 'contact', $lead->getId());
                            break;
                        case 2:
                            $this->customItemModel->unlinkEntity($customItem, 'contact', $lead->getId());
                            break;
                        default:
                            //overwrite
                    }
                }

            }
        }
    }

    // @aivie Display a user-friendly custom object data in the form result page
    // @todo Create Mautic PR
    public function onDisplayItemLink(FieldValueEvent $event): void
    {
        try {
            $object = $this->customObjectModel->fetchEntityByAlias($event->getObject());
        } catch (NotFoundException $e) {
            // Do nothing if the custom object doesn't exist.
            return;
        }

        $item       = $this->customItemModel->getEntity($event->getValue());
        $properties = [
            'link'           => CustomItemRouteProvider::ROUTE_VIEW,
            'linkParameters' => [
                'objectId'   => $object->getId(),
                'itemId'     => isset($item) ? $item->getId() : '%alias%',
            ],
        ];

        $event->setProperties($properties);

        if (isset($item)) {
            $event->setValue($item->getName());

            if (!empty($event->getField()) && $event->getObject() !== $event->getField()) {
                $fields = $this->customItemModel->populateCustomFields($item)->getCustomFieldValues();
                foreach ($fields as $field) {
                    if ($field->getCustomField()->getAlias() === $event->getField()) {
                        $event->setValue($field->getValue());
                    }
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getCustomFieldValues(CustomField $field, array $items): array
    {
        $list = [];

        array_walk(
            $items,
            function ($item) use ($field, &$list) {
                $itemWithCustomFieldValues = $this->customItemModel->populateCustomFields($item);
                $itemCustomFieldsValues    = $itemWithCustomFieldValues->getCustomFieldValues();

                foreach ($itemCustomFieldsValues as $customFieldValue) {
                    if ($field->getAlias() === $customFieldValue->getCustomField()->getAlias()) {
                        $list[$item->getId()] = $customFieldValue->getValue();
                    }
                }
            }
        );

        return $list ?? [];
    }
}
