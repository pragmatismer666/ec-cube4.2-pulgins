<?php
/*
* Plugin Name : PointsOnFirstOrder
*
* Copyright (C) 2020 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\PointsOnFirstOrder;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PageRepository;
use Plugin\PointsOnFirstOrder\Entity\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // プラグイン設定を追加
        $Config = $this->createConfig($em);
    }

    protected function createConfig(EntityManagerInterface $em)
    {
        $Config = $em->find(Config::class, 1);
        if ($Config) {
            return $Config;
        }
        $Config = new Config();
        $Config->setPoints(0);

        $em->persist($Config);
        $em->flush($Config);

        return $Config;
    }
}
