<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\FormBundle\Crate\ObjectCrate;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Crate\FieldCrate;
use Mautic\FormBundle\Event\MappedObjectColumnEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Report\ReportColumnsBuilder;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormSubscriber implements EventSubscriberInterface
{
    public const CHILD_CUSTOM_OBJECT_NAME_PREFIX = '&nbsp;&nbsp;&nbsp;&nbsp;';

    private CustomObjectModel $customObjectModel;
    private CustomItemModel $customItemModel;
    private CustomObjectRepository $customObjectRepository;
    private TranslatorInterface $translator;

    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        CustomObjectRepository $customObjectRepository,
        TranslatorInterface $translator
    ) {
        $this->customObjectModel      = $customObjectModel;
        $this->customItemModel        = $customItemModel;
        $this->customObjectRepository = $customObjectRepository;
        $this->translator             = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::ON_OBJECT_COLLECT                  => ['onObjectCollect', 0],
            FormEvents::ON_FIELD_COLLECT                   => ['onFieldCollect', 0],
            FormEvents::ON_GENERATE_MAPPED_OBJECT_COLUMNS  => ['onGenerateMappedObjectColumn', 0],
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

        foreach ($object->getCustomFields() as $field) {
            $list = $this->getCustomFieldValues($field, $items);
            $event->appendField(new FieldCrate($field->getAlias(), $field->getName(), $field->getType(), ['object-id' => $object->getId(), 'list' => $list]));
        }
    }

    public function onGenerateMappedObjectColumn(MappedObjectColumnEvent $event): void
    {
        $mappedObjects = $event->getMappedObjects();

        foreach ($mappedObjects as $mappedObject) {
            if ($this->customObjectRepository->checkAliasExists($mappedObject['mappedObject'])) {
                $customObject        = $this->customObjectRepository->findOneBy(['alias' => $mappedObject['mappedObject']]);
                $customFieldsColumns = (new ReportColumnsBuilder($customObject))->getColumns();

                array_walk(
                    $customFieldsColumns,
                    function (&$item, $index) use ($customObject, $mappedObject) {
                        $item['idCustomObject'] = $customObject->getId();
                        $item['fieldAlias']     = $mappedObject['fieldAlias'];
                    }
                );

                $columns = array_merge(
                    $columns ?? [],
                    $this->addPrefixToColumnLabel($customFieldsColumns, $customObject->getNameSingular()),
                    $parentCustomObjectColumns ?? []
                );
            }
        }

        $event->appendMappedObjectColumns($columns ?? []);
    }

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
                        $list[$item->getName()] = $customFieldValue->getValue();
                    }
                }
            }
        );

        return $list ?? [];
    }

    private function addPrefixToColumnLabel(array $columns, string $prefix): array
    {
        foreach ($columns as $alias => $column) {
            $columnLabel = $this->translator->trans($columns[$alias]['label']);
            if (0 === strpos($columnLabel, $prefix)) {
                $columns[$alias]['label'] = $columnLabel;
                // Don't add the prefix twice
                continue;
            }

            $columns[$alias]['label'] = sprintf(
                '%s %s',
                str_replace(self::CHILD_CUSTOM_OBJECT_NAME_PREFIX, '', $prefix),
                $columnLabel
            );
        }

        return $columns;
    }
}