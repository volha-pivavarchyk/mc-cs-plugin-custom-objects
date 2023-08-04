<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignConditionLinkOnContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'customItemId',
            CustomItemListType::class,
            [
                'label'      => 'custom.item.link.contact',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip'                => 'custom.item.link.contact.descr',
                    'data-toggle'            => 'typeahead',
                    'class'                  => 'form-control',
                ],
                'multiple' => false,
                'expanded' => false,
                'required' => true,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'link_to_contact_action';
    }
}
