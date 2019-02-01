<?php
declare(strict_types=1);
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic Inc., Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SegmentFiltersDictionarySubscriber
 *
 * @package MauticPlugin\CustomObjectsBundle\EventListener
 */
class SegmentFiltersDictionarySubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onGenerateSegmentDictionary'];
    }

    public function onGenerateSegmentDictionary(SegmentDictionaryGenerationEvent $event)
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('f.id, f.label, f.type, o.id as custom_object_id')
            ->from(MAUTIC_TABLE_PREFIX . "custom_field", 'f')
            ->innerJoin('f', MAUTIC_TABLE_PREFIX . "custom_object", 'o', 'f.custom_object_id = o.id and o.is_published = 1');

        $registeredObjects = [];

        foreach ($queryBuilder->execute()->fetchAll() as $field) {
            if (!in_array($COId = $field['custom_object_id'], $registeredObjects)) {
                $event->addTranslation('cmo_' . $COId, [
                    'type'  => CustomItemFilterQueryBuilder::getServiceId(),
                    'field' => $COId,
                ]);
                $registeredObjects[] = $COId;
            }
            $event->addTranslation('cmf_' . $field['id'], $this->createTranslation($field));
        }
    }

    /**
     * @param array $fieldAttributes
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function createTranslation(array $fieldAttributes)
    {
        if (!in_array($type = $fieldAttributes['type'], ['number', 'text', 'datetime'])) {
            throw new InvalidArgumentException('Given custom field type does not exist: ' . $type);
        }

        $segmentValueType = 'custom_field_value_' . $type;

        $translation = [
            'type'  => CustomFieldFilterQueryBuilder::getServiceId(),
            'table' => $segmentValueType,
            'field' => $fieldAttributes['id'],
        ];

        return $translation;
    }
}