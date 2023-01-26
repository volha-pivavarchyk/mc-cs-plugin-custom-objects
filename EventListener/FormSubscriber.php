<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Http\Discovery\Exception;
use Mautic\FormBundle\Crate\ObjectCrate;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Crate\FieldCrate;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    private CustomObjectModel $customObjectModel;
    private CustomItemModel $customItemModel;

    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel
    ) {
        $this->customObjectModel = $customObjectModel;
        $this->customItemModel   = $customItemModel;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::ON_OBJECT_COLLECT        => ['onObjectCollect', 0],
            FormEvents::ON_FIELD_COLLECT         => ['onFieldCollect', 0],
        ];
    }

    public function onObjectCollect(ObjectCollectEvent $event): void
    {
        foreach ($this->customObjectModel->fetchEntities() as $entity) {
            $event->appendObject(new ObjectCrate($entity->getAlias(), $entity->getName()));
        }
    }

    /**
     * @throws NotFoundException
     */
    public function onFieldCollect(FieldCollectEvent $event): void
    {
        try {
            $object = $this->customObjectModel->fetchEntityByAlias($event->getObject());
        } catch (NotFoundException $e) {
            // Do nothing if the custom object doesn't exist.
            return;
        }

        foreach ($object->getCustomFields() as $field) {
            $items = $this->customItemModel->fetchCustomItemsForObject($object);
            $list  = $this->getCustomFieldValues($field, $items);

            $event->appendField(new FieldCrate($field->getAlias(), $field->getName(), $field->getType(), ['list' => $list]));
        }
    }

    private function getCustomFieldValues(CustomField $field, array $items): ?array
    {
        $list = array_map(
            function ($item) use ($field) {
                $itemWithCustomFieldValues = $this->customItemModel->populateCustomFields($item);

                foreach ($itemWithCustomFieldValues->getCustomFieldValues() as $customFieldValue) {
                    if ($field->getAlias() === $customFieldValue->getCustomField()->getAlias()) {
                        $value = $customFieldValue->getValue();
                    }
                }

                return $value ?? null;
            },
            $items
        );

        return array_filter($list, fn ($elem) => isset($elem) ?? false);
    }
}