<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefContactEvent;

class CustomItemXrefContactEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $xref  = $this->createMock(CustomItemXrefContact::class);
        $event = new CustomItemXrefContactEvent($xref);

        $this->assertSame($xref, $event->getXref());
    }
}
