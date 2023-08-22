<?php

namespace Plugin\komoju\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Repository\PaymentRepository;
use Eccube\Entity\Payment;
use Eccube\Entity\PaymentOption;
use Eccube\Common\EccubeConfig;
use Plugin\komoju\Entity\KomojuPay;
use Plugin\komoju\Entity\LicenseKey;
use Plugin\komoju\Entity\KomojuConfig;
use Plugin\komoju\Entity\KomojuLog;
use Plugin\komoju\Service\Method\KomojuMultiPay;
use Plugin\komoju\Repository\KomojuOrderRepository;
use Plugin\komoju\Entity\KomojuOrder;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\OrderStateMachine;
use Eccube\Entity\ProductStock;
use Eccube\Entity\Order;
class WebhookService{
    protected $container;    
    protected $entityManager;
    protected $log_service;
    protected $komoju_order_repo;
    protected $order_state_machine;
    protected $productStockRepository;


    public function __construct(
        ContainerInterface $container,
        OrderStateMachine $orderStateMachine
        ){
        $this->container = $container;
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->komoju_order_repo = $this->entityManager->getRepository(KomojuOrder::class);
        $this->log_service = $container->get("plg_komoju.service.komoju_log");
        $this->order_state_machine = $orderStateMachine;
        $this->productStockRepository = $this->entityManager->getRepository(ProductStock::class);
    }
    public function paymentRefunded($object){
        $refunds = $object->data->refunds;
        if(empty($refunds)){
            return;
        }
        $refund_id = $refunds[0]->id;

        $qb = $this->komoju_order_repo->createQueryBuilder("ko");
        $komoju_orders = $qb->where($qb->expr()->like("ko.refund_id", ":refund_id"))
            ->setParameter("refund_id", "%$refund_id%")
            ->getQuery()
            ->getResult();
        if(empty($komoju_orders)){
            return;
        }                    
        $komoju_order = $komoju_orders[0];
        $Order = $komoju_order->getOrder();
        if($Order){
            $this->log_service->writeLog("webhook[refund]", $Order->getId(), "refund successfully");
        }
    }
    public function paymentCaptured($object){
        $komoju_payment_id = $object->data->id;
        $komoju_order = $this->komoju_order_repo->findOneBy(['komoju_payment_id' => $komoju_payment_id]);
        if(empty($komoju_order)){
            return;
        }
        $captured_at = new \DateTime($object->data->captured_at);
        $komoju_order->setCapturedAt($captured_at);
        $this->entityManager->persist($komoju_order);
        $this->entityManager->flush();

        $order = $komoju_order->getOrder();
        if(empty($order)){
            return ;
        }        
        $order->setPaymentDate($captured_at);
        $OrderStatus = $this->entityManager->getRepository(OrderStatus::class)->find(OrderStatus::PAID);
        $order->setOrderStatus($OrderStatus);
        $this->entityManager->persist($order);
        $this->entityManager->flush($order);
    }
    public function paymentCanceled($object){
        $komoju_payment_id = $object->data->id;
        $komoju_order = $this->komoju_order_repo->findOneBy(['komoju_payment_id' => $komoju_payment_id]);
        if(empty($komoju_order)){
            return;
        }
        $order = $komoju_order->getOrder();
        if(empty($order)){
            return;
        }
        // $OrderStatus = $this->entityManager->getRepository(OrderStatus::class)->find(OrderStatus::CANCEL);
        // $order->setOrderStatus($OrderStatus);
        // $this->entityManager->persist($order);
        // $this->entityManager->flush($order);
        $this->cancelOrder($komoju_order);
    }
    public function paymentExpired($object){
        $komoju_payment_id = $object->data->id;
        $komoju_order = $this->komoju_order_repo->findOneBy(['komoju_payment_id' => $komoju_payment_id]);
        if(empty($komoju_order)){
            return;
        }
        $order = $komoju_order->getOrder();
        if(empty($order)){
            return;
        }
        // $this->setOrderStatus($order, OrderStatus::CANCEL);
        $this->cancelOrder($komoju_order);
    }
    public function paymentFailed($object){
        $komoju_payment_id = $object->data->id;
        $komoju_order = $this->komoju_order_repo->findOneBy(['komoju_payment_id' => $komoju_payment_id]);
        if(empty($komoju_order)){
            return;
        }
        
        $order = $komoju_order->getOrder();
        if(empty($order)){
            return;
        }

        
        // $this->setOrderStatus($order, OrderStatus::CANCEL);
        $this->cancelOrder($komoju_order);
    }
    public function paymentUpdated($object){
        $komoju_payment_id = $object->data->id;
        $komoju_order = $this->komoju_order_repo->findOneBy(['komoju_payment_id' => $komoju_payment_id]);
        if(empty($komoju_order)){
            return;
        }
        $status = $object->data->status;
        if(in_array($status, ["expired", "cancelled"])){
            $order = $komoju_order->getOrder();
            // $this->setOrderStatus($order, OrderStatus::CANCEL);
            $this->cancelOrder($komoju_order);
        }
    }
    private function setOrderStatus($order, $status){
        $OrderStatus = $this->entityManager->getRepository(OrderStatus::class)->find($status);
        $order->setOrderStatus($OrderStatus);
        $this->entityManager->persist($order);
        $this->entityManager->flush($order);
    }
    private function cancelOrder($komoju_order){
        if($komoju_order->getCanceledAt()){
            return;
        }
        $komoju_order->setCanceledAt(new \DateTime());
        $this->entityManager->persist($komoju_order);
        $this->entityManager->flush($komoju_order);
        $this->entityManager->commit();

        $Order = $komoju_order->getOrder();
        if(empty($Order)){
            return;
        }

        if ($Order->getOrderStatus()->getId() == OrderStatus::CANCEL) {
            return;
        }
        $OrderStatus = $this->entityManager->find(OrderStatus::class, OrderStatus::CANCEL);
        if ($this->order_state_machine->can($Order, $OrderStatus)) {
            if ($OrderStatus->getId() == OrderStatus::DELIVERED) {
                
                $allShipped = true;
                foreach ($Order->getShippings() as $Ship) {
                    if (!$Ship->isShipped()) {
                        $allShipped = false;
                        break;
                    }
                }
                if ($allShipped) {
                    $this->order_state_machine->apply($Order, $OrderStatus);
                }
            } else {
                $this->order_state_machine->apply($Order, $OrderStatus);
            }

            foreach ($Order->getOrderItems() as $OrderItem) {
                $ProductClass = $OrderItem->getProductClass();
                if ($OrderItem->isProduct() && !$ProductClass->isStockUnlimited()) {
                    $this->entityManager->flush($ProductClass);
                    $ProductStock = $this->productStockRepository->findOneBy(['ProductClass' => $ProductClass]);
                    $this->entityManager->flush($ProductStock);
                }
            }
            $this->entityManager->flush($Order);

            // 会員の場合、購入回数、購入金額などを更新
            if ($Customer = $Order->getCustomer()) {
                $this->entityManager->getRepository(Order::class)->updateOrderSummary($Customer);
                $this->entityManager->flush($Customer);
            }
        }
    }

}