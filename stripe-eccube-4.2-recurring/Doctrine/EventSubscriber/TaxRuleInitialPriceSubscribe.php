<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StripeRec\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use Doctrine\ORM\Events;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Order;
use Eccube\Service\TaxRuleService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxRuleInitialPriceSubscribe implements EventSubscriber
{
    /**
     * @var TaxRuleService
     */
    protected $container;

    /**
     * TaxRuleEventSubscriber constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getTaxRuleService()
    {
        return $this->container->get(TaxRuleService::class);
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::postLoad,
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function prePersist(PrePersistEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            if($entity->isInitialPriced()){
                $entity->setInitialPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getInitialPrice(),
                    $entity->getProduct(), $entity));            
            }
        }
    }

    public function postLoad(PostLoadEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            if($entity->isInitialPriced()){
                $entity->setInitialPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getInitialPrice(),
                    $entity->getProduct(), $entity));
            }
        }
    }

    public function postPersist(PostPersistEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            if($entity->isInitialPriced()){
                $entity->setInitialPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getInitialPrice(),
                    $entity->getProduct(), $entity));
            }
        }
    }

    public function postUpdate(PostUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductClass) {
            if($entity->isInitialPriced()){
                $entity->setInitialPriceIncTax($this->getTaxRuleService()->getPriceIncTax($entity->getInitialPrice(),
                    $entity->getProduct(), $entity));
            }
        }
    }
}
