<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\FormBundle\Crate\ObjectCrate;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\Event\FieldDisplayEvent;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Crate\FieldCrate;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class FormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CustomObjectModel $customObjectModel,
        private CustomItemModel $customItemModel,
        private CustomItemXrefContactRepository $customItemXrefContactRepository,
        private RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::ON_OBJECT_COLLECT      => ['onObjectCollect', 0],
            FormEvents::ON_FIELD_COLLECT       => ['onFieldCollect', 0],
            FormEvents::ON_FIELD_DISPLAY       => [
                ['onDisplayAssignedToContactFieldValue', 0],
//                ['onDisplayLinkToField', 0],
            ]
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
                $list[$item->getId()] = $item->getName();
            }

            $event->appendField(new FieldCrate($object->getAlias(), 'Name', 'text', ['list' => $list ?? []]));
        }

        foreach ($object->getCustomFields()->getValues() as $field) {
            $list = $this->getCustomFieldValues($field, $items);
            $event->appendField(new FieldCrate($field->getAlias(), $field->getName(), $field->getType(), ['list' => $list]));
        }
    }

    // @Aivie Add posibility to use only assigned to the contact fields
    // @todo Mautic PR
    public function onDisplayAssignedToContactFieldValue(FieldDisplayEvent $event): void
    {
        if (null === $event->getLead() || !$event->getValue() instanceof FieldCrate) {
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

        if (count($items) > 0) {
            foreach ($items as $item) {
                $list[$item->getId()] = $item->getName();
            }

            $value = new FieldCrate($object->getAlias(), 'Name', 'text', ['list' => $list ?? []]);
        }

        $event->setValue($value ?? []);
    }

    // @todo Mautic PR
    public function onDisplayLinkToField(FieldDisplayEvent $event): void
    {
        try {
            $object = $this->customObjectModel->fetchEntityByAlias($event->getObject());
        } catch (NotFoundException $e) {
            // Do nothing if the custom object doesn't exist.
            return;
        }

        $ids   = explode(',', $event->getValue());
        $value = '';

        foreach ($ids as $id) {
            $item = $this->customItemModel->getEntity($id);
            if ($item) {
                $viewParameters = [
                    'objectId' => $object->getId(),
                    'itemId' => $item->getId(),
                ];
                $route = $this->router->generate(CustomItemRouteProvider::ROUTE_VIEW, $viewParameters);
                $value .= '<a href="' . $route . '" class="label label-success mr-5"> '.$item->getId().'</a>';
            }
        }
        $event->setValue($value);
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
