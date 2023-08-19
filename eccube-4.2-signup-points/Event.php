<?php
/*
* Plugin Name : PointsOnSignUp
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\PointsOnSignUp;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Event\EventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\PointsOnSignUp\Repository\ConfigRepository;

class Event implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ConfigRepository $configRepository
     */
    public function __construct(EntityManagerInterface $entityManager,ConfigRepository $configRepository)
    {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'front.entry.activate.complete' => 'onFrontEntryActivateComplete'
        ];
    }

    /**
     * @param EventArgs $event
     */
    public function onFrontEntryActivateComplete(EventArgs $event)
    {
        $Config = $this->configRepository->get();
        $points_on_sign_up=0;
        if($Config){
            $points_on_sign_up=$Config->getPoints();
        }

        if($points_on_sign_up>0) {
            $Customer = $event->getArgument('Customer');

            if ($Customer && $Customer->getId() > 0) {
                $CustomerId = $Customer->getId();

                $Customer->setPoint($points_on_sign_up);
                $this->entityManager->persist($Customer);
                $this->entityManager->flush($Customer);

                log_info('Points added on to Customer id: '.$CustomerId." on signup.", ['status' => 'Success']);
            }
        }

    }
}
