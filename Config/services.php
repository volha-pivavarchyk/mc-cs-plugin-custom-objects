<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CountryType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\EmailType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\HiddenType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\PhoneType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextareaType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\UrlType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldOptionModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemExportSchedulerModel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Provider/SessionProvider.php',
        'Report/ReportColumnsBuilder.php',
        'Serializer/ApiNormalizer.php',
        'Extension/CustomItemListeningExtension.php'
    ];

    $services->load('MauticPlugin\\CustomObjectsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\CustomObjectsBundle\\Repository\\', '../Repository/*Repository.php');

    $services->set('mautic.custom.model.field', CustomFieldModel::class);
    $services->set('mautic.custom.model.field.value', CustomFieldValueModel::class);
    $services->set('mautic.custom.model.item', CustomItemModel::class);
    $services->set('mautic.custom.model.import.item', CustomItemImportModel::class);
    $services->set('mautic.custom.model.import.xref.contact', CustomItemXrefContactModel::class);
    $services->set('mautic.custom.model.field.option', CustomFieldOptionModel::class);
    $services->set('mautic.custom.model.object', CustomObjectModel::class);
    $services->set('mautic.custom.model.export_scheduler', CustomItemExportSchedulerModel::class);

    $services->set('custom.field.type.country', CountryType::class)->tag('custom.field.type');
    $services->set('custom.field.type.date', DateType::class)->tag('custom.field.type');
    $services->set('custom.field.type.datetime', DateTimeType::class)->tag('custom.field.type');
    $services->set('custom.field.type.email', EmailType::class)->tag('custom.field.type');
    $services->set('custom.field.type.hidden', HiddenType::class)->tag('custom.field.type');
    $services->set('custom.field.type.int', IntType::class)->tag('custom.field.type');
    $services->set('custom.field.type.phone', PhoneType::class)->tag('custom.field.type');
    $services->set('custom.field.type.select', SelectType::class)->tag('custom.field.type');
    $services->set('custom.field.type.multiselect', MultiselectType::class)->tag('custom.field.type');
    $services->set('custom.field.type.text', TextType::class)->tag('custom.field.type');
    $services->set('custom.field.type.textarea', TextareaType::class)->tag('custom.field.type');
    $services->set('custom.field.type.url', UrlType::class)->tag('custom.field.type');
};
