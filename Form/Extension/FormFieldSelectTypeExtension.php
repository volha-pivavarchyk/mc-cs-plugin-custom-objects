<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Extension;

use Mautic\FormBundle\Form\Type\FormFieldGroupType;
use Mautic\FormBundle\Form\Type\FormFieldSelectType;
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

    /**
     * @param array<int|string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'saveRemove',
            ChoiceType::class,
            [
                'attr' => [
                    'data-show-on' => '{"formfield_mappedField:data-list-type": "1"}',
                    'tooltip'      => 'custom.item.select.link_unlink.contact.descr',
                ],
                'label'    => 'custom.item.select.link_unlink.contact',
                'choices'  => [
//                    'Overwrite' => 0,
                    'Link'      => 1,
                    'Unlink'    => 2,
                ],
            ]
        );
    }
}