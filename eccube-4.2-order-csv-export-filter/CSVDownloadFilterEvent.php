<?php
/*
* Plugin Name : CSVDownloadFilter
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\CSVDownloadFilter;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CSVDownloadFilterEvent implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Order/index.twig' => 'onRenderAdminOrderIndex'
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function onRenderAdminOrderIndex(TemplateEvent $event)
    {
        $twig_base = '@CSVDownloadFilter/csv_download_filter.twig';
        $event->addAsset($twig_base);

    }
}
