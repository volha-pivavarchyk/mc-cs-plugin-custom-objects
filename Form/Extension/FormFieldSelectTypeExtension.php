<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Extension;

use Mautic\FormBundle\Form\Type\FormFieldGroupType;
use Mautic\FormBundle\Form\Type\FormFieldSelectType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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

    /**
     * @param array<int|string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

//        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
//            $form       = $event->getForm();
//            $object     = $form->getParent()->get('mappedObject');
//
//            if (isset($object)) {
//                $objectName = $object->getData();
//
//                if ('contact' !== $objectName && 'contact' !== $objectName) {
//                    $form->add(
//                        'saveRemove',
//                        ChoiceType::class,
//                        [
//                            'attr' => [
//                                'data-show-on' => '{"formfield_mappedField:data-list-type": "1"}',
//                                'tooltip'      => 'custom.item.select.mapped_field.action.descr',
//                            ],
//                            'label'    => 'custom.item.select.mapped_field.action',
//                            'choices'  => [
//                                'Add'    => 1,
//                                'Remove' => 2,
//                            ],
//                        ]
//                    );
//                }
//            }
//        });

        $builder->add(
            'saveRemove',
            ChoiceType::class,
            [
                'attr' => [
                    'data-show-on'            => '{"formfield_mappedField:data-list-type": "1"}',
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