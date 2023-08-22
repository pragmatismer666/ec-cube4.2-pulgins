<?php

namespace Plugin\PointsOnFirstOrder;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Event\EventArgs;
use Eccube\Event\EccubeEvents;
use Eccube\Repository\OrderRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\PointsOnFirstOrder\Repository\ConfigRepository;
use Plugin\PointsOnFirstOrder\Repository\CustomerPointRepository;
use Plugin\PointsOnFirstOrder\Entity\CustomerPoint;
use Symfony\Component\Workflow\Event\Event as SymfonyEvent;

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
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var CustomerPointRepository
     */
    protected $customerPointRepository;

    /**
     * ConfigController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ConfigRepository $configRepository
     * @param OrderRepository $orderRepository
     * @param CustomerPointRepository $customerPointRepository
     */
    public function __construct(EntityManagerInterface $entityManager, ConfigRepository $configRepository, OrderRepository $orderRepository, CustomerPointRepository $customerPointRepository)
    {
        $this->entityManager = $entityManager;
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->customerPointRepository = $customerPointRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
//            EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE => 'onFrontShoppingCompleteInitialize',
            'workflow.order.transition.ship' => ['onTransitionShip'],
//            'workflow.order.transition.return' => ['onTransitionReturn'],
        ];

    }

    public function onFrontShoppingCompleteInitialize(EventArgs $event)
    {
    }

    private function addPoint(Order $Order)
    {
        $app = \Eccube\Application::getInstance();
        $Config = $this->configRepository->get();
        $points_on_first_order = 0;
        if ($Config) {
            $points_on_first_order = $Config->getPoints();
        }
        if ($points_on_first_order == 0) {
            return;
        }
        $Customer = $Order->getCustomer();
        if (!($Customer instanceof Customer)) {
            return;
        }

        $CustomerId = $Customer->getId();
        if (!$CustomerId) {
            return;
        }
        $customerPoint = $this->customerPointRepository->findOneBy(['Customer' => $Customer]);
        if($customerPoint && $customerPoint->isAdded()) {
            return;
        }
        $qb = $this->orderRepository->getQueryBuilderByCustomer($Customer);
        $qbResult=$qb->select('o.id')
            ->andWhere($qb->expr()->notIn('o.OrderStatus', ':status'))
            ->setParameter('status', [OrderStatus::PROCESSING, OrderStatus::PENDING])
            ->getQuery()
            ->getResult();
        $count=count($qbResult);

        log_info( sprintf('Order count: %d', $count));
        if ($count == 1) {
            $Customer->setPoint(intval($Customer->getPoint()) + intval($points_on_first_order));
            $this->customerPointRepository->setPoint($Customer, $points_on_first_order);
            log_info('Points added on to Customer id: ' . $CustomerId . " on first order.", ['status' => 'Success']);
        }

    }

    /**
     * 会員に付与した初回購入ポイントを取り消す.
     *
     * @param SymfonyEvent $event
     */
    public function onTransitionShip(SymfonyEvent $event)
    {
        $Order = $event->getSubject()->getOrder();
        $this->addPoint($Order);
    }

    private function rollbackAddPoint(Order $Order)
    {
        $Config = $this->configRepository->get();
        $points_on_first_order = 0;
        if ($Config) {
            $points_on_first_order = $Config->getPoints();
        }
        if ($points_on_first_order == 0) {
            return;
        }
        $Customer = $Order->getCustomer();
        if (!($Customer instanceof Customer)) {
            return;
        }
        $CustomerId = $Customer->getId();
        if ($CustomerId > 0) {
            $qb = $this->orderRepository->getQueryBuilderByCustomer($Customer);
            $qb->andWhere($qb->expr()->notIn('o.OrderStatus', ':status'))
                ->setParameter('status', [OrderStatus::PROCESSING, OrderStatus::PENDING]);
            $count = $qb->select('COALESCE(COUNT(o.id), 0)')
                ->getQuery()
                ->getSingleScalarResult();
            if ($count == 0) {
                $point = intval($Customer->getPoint()) - intval($points_on_first_order);
                $point = $point > 0 ? $point : 0;
                $Customer->setPoint($point);
                log_info('Points added on to Customer id: ' . $CustomerId . " on first order.", ['status' => 'Success']);
            }
        }
    }

//    public function onTransitionReturn(SymfonyEvent $event)
//    {
//        $Order = $event->getSubject()->getOrder();
//        $this->rollbackAddPoint($Order);
//    }
}