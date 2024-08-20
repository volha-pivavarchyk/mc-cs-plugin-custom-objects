<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Test\Session\FixedMockFileSessionStorage;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class CampaignConditionTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    protected function setUp(): void
    {
        // Disable API just for specific test.
        $this->configParams['api_enabled'] = 'testDisabledApi' !== $this->getName();

        static::getContainer()->set(
            'session',
            new Session(
                new class() extends FixedMockFileSessionStorage {
//                    public function start()
//                    {
//                        Assert::fail('Session cannot be started during API call. It must be stateless.');
//                    }
                }
            )
        );

        parent::setUp();
    }

    public function testConditionForm(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Campaign test object');
        $crawler      = $this->client->request(
            Request::METHOD_GET,
            's/campaigns/events/new',
            [
                'campaignId'      => 'mautic_041b2a401f680fb0b644654af5ba0892f31f0697',
                'type'            => "custom_item.{$customObject->getId()}.fieldvalue",
                'eventType'       => 'condition',
                'anchor'          => 'leadsource',
                'anchorEventType' => 'source',
            ],
            [],
            $this->createAjaxHeaders()
        );

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $html = json_decode($this->client->getResponse()->getContent(), true)['newContent'];

        $crawler->addHtmlContent($html);

        $textField = $customObject->getCustomFields()->filter(
            fn (CustomField $customField) => 'text' === $customField->getTypeObject()->getKey()
        )->first();

        $saveButton = $crawler->selectButton('campaignevent[buttons][save]');
        $form       = $saveButton->form();
        $form['campaignevent[properties][value]']->setValue('unicorn');
        $form['campaignevent[properties][operator]']->setValue('=');
        $form['campaignevent[properties][field]']->setValue($textField->getId());

        $this->client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), $form->getPhpFiles(), $this->createAjaxHeaders());
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $clientResponse = $this->client->getResponse();
        $body           = json_decode($clientResponse->getContent(), true);

        Assert::assertSame(1, $body['success']);
        Assert::assertEquals($textField->getId(), $body['event']['properties']['properties']['field']);
        Assert::assertSame('=', $body['event']['properties']['properties']['operator']);
        Assert::assertSame('unicorn', $body['event']['properties']['properties']['value']);
    }
}
