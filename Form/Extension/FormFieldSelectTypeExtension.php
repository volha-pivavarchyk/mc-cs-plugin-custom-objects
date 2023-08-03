<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Extension;

use Mautic\FormBundle\Form\Type\FormFieldGroupType;
use Mautic\FormBundle\Form\Type\FormFieldSelectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class FormFieldSelectTypeExtension extends AbstractTypeExtension
{
    /**
     * Returns an array of extended types.
     *
     * @return array<int, string>
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormFieldSelectType::class, FormFieldGroupType::class];
    }

    public function __construct(private CustomObjectModel $customObjectModel)
    {
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $objects = $this->customObjectModel->getEntities()->getIterator();
        foreach ($objects as $object) {
            $objectsArr[] = '"'.$object->getAlias().'"';
        }

        $objectStr = implode(',', $objectsArr ?? []);

        $builder->add(
            'saveRemove',
            ChoiceType::class,
            [
                'attr' => [
                    'data-show-on'            => '{"formfield_mappedObject": ['.$objectStr.']}',
                    'data-custom-object-prop' => 'true',
                    'tooltip'                 => 'custom.item.select.mapped_field.action.descr',
                ],
                'label'    => 'custom.item.select.mapped_field.action',
                'choices'  => [
                    'Add'      => 1,
                    'Remove'   => 2,
                ],
            ]
        );
    }
}