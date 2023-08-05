<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomItemListType extends AbstractType
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    public function __construct(
        private CustomObjectModel $customObjectModel,
        private CustomItemModel $customItemModel
    ) {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                $choices = [];
                $customObjects = $this->customObjectModel->getEntities();

                foreach ($customObjects as $customObject) {
                    $items = $this->customItemModel->fetchCustomItemsForObject($customObject);
                    if (count($items) > 0) {
                        $choices[$customObject->getAlias()]['Any'] = 'object_'.$customObject->getId();
                    }
                    foreach ($items as $item) {
                        $choices[$customObject->getAlias()][$item->getName()] = $item->getId();
                    }
                }

                return $choices;
            },
            'required'               => false,
        ]);
    }

    /**
     * @return string|FormTypeInterface|null
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'custom_item_choices';
    }
}
