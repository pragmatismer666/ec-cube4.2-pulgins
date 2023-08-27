<?php

namespace Plugin\komoju;

use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Payment;
use Eccube\Entity\Master\OrderStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\komoju\Service\Method\KomojuMultiPay;
use Plugin\komoju\Entity\KomojuConfig;
use Plugin\komoju\Entity\KomojuPay;
use Plugin\komoju\Entity\KomojuOrder;
use Eccube\Event\EventArgs;
use Eccube\Event\EccubeEvents;
use Plugin\komoju\KomojuClient;

class KomojuEvent implements EventSubscriberInterface{

    private $container;
    private $entityManager;
    private $errorMessage;
    private $eccubeConfig;
    private $base_info;
    protected $config_service;

    const EVENT_KOMOJU_CONFIG_LOAD = "PLUGIN.KOMOJU.CONFIG.LOAD";

    public function __construct(
        EccubeConfig $eccubeConfig,
        ContainerInterface $container,
        EntityManagerInterface $entityManager
    ){
        $this->eccubeConfig = $eccubeConfig;
        $this->container = $container;
        $this->entityManager = $entityManager;        
        $this->base_info = $this->entityManager->getRepository(BaseInfo::class)->get();
        $this->config_service = $this->container->get("plg_komoju.service.config");
                
    }
    /**
     * @return array
     */
    public static function getSubscribedEvents(){
        return [
            'Shopping/confirm.twig'   =>  'onShoppingConfirmTwig',
            'Shopping/index.twig'   =>  'onShoppingIndexTwig',
            'front.shopping.complete.initialize'    =>  'onFrontShoppingCompleteInitialize',
            '@admin/Order/index.twig'   =>  'onAdminOrderIndexTwig',
            '@admin/Order/edit.twig'    =>  'onAdminOrderEditTwig',
            // EccubeEvents::ADMIN_ORDER_EDIT_INDEX_COMPLETE   =>  'onOrderEditIndexComplete',
        ];
    }

    /**
     * @param TemplateEvent
     */
    public function onShoppingIndexTwig(TemplateEvent $event){
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => KomojuMultiPay::class]);
        if($Payment){
            $payment_id = $Payment->getId();
            $event->setParameter("komoju_id", $payment_id);
            $event->addSnippet('@komoju/default/shopping/shopping.twig');
        }
    }
    /**
     * @param EventArgs
     */
    public function onOrderEditIndexComplete(EventArgs $args){
        // $OriginOrder = $args->getArgument('OriginOrder');
        // $TargetOrder = $args->getArgument('TargetOrder');

        // if($OriginOrder->getPayment()->getId() == 
        //     $this->entityManager->getRepository(Payment::class)->findOneBy(['method_class' => KomojuMultiPay::class])->getId()){
            
        //     $OldStatus = $OriginOrder->getOrderStatus();
        //     $NewStatus = $TargetOrder->getOrderStatus();
    
        //     if ($TargetOrder->getId() && $OldStatus->getId() != $NewStatus->getId()) {
        //         if($NewStatus->getId() == OrderStatus::CANCEL){
        //             $komoju_order = $this->entityManager->getRepository(KomojuOrder::class)->findOneBy(['Order' => $TargetOrder]);
        //             if(empty($komoju_order) || $komoju_order->isCaptured()){
        //                 return;
        //             }
        //             $payment_id = $komoju_order->getKomojuPaymentId();
        //             $config_service = $this->container->get("plg_komoju.service.config");
        //             $config_data = $config_service->getConfigData($TargetOrder);
        //             $komoju_client = new KomojuClient($config_data['secret_key']);
        //             $payment_obj = $komoju_client->getPayment($payment_id);
        //             if($komoju_client->getStatusCode() != 200 || empty($payment_obj)){
        //                 return;
        //             }
        //             if($payment_obj['status'] == "pending" || $payment_obj['status'] == "authorized"){
        //                 $res = $komoju_client->cancelPayment($payment_id);
        //             }
        //         }
        //     }
        // }

    }

    /**
     * @param TemplateEvent $event
     */
    public function onShoppingConfirmTwig(TemplateEvent $event){
        $Order = $event->getParameter("Order");
        if($Order){
            if($Order->getPayment()->getMethodClass() === KomojuMultiPay::class){
                // $config_repo = $this->entityManager->getRepository(KomojuConfig::class);
                // $config = $config_repo->getConfigByOrder($Order);
                $config = $this->config_service->getConfigData($Order);
                $total_amount = $Order->getPaymentTotal();
                
                $order_items = $Order->getProductOrderItems();
                
                $first_prod_name = $order_items[0]->getProduct()->getName();
                $cnt = count($order_items);
                if($cnt > 1){
                    $title = $first_prod_name . " and " . ($cnt - 1) . " more";
                }else{
                    $title = $first_prod_name;
                }
                $description = $this->base_info->getShopName();
                $methods = $this->entityManager->getRepository(KomojuPay::class)->getEnabledMethodsString();
                // $currency = $Order->getCurrencyCode();


                $event->setParameter("publishable_key", $config['publishable_key']);
                $event->setParameter("total_amount", $total_amount);
                $event->setParameter("title", $title);
                $event->setParameter("description", $description);
                $event->setParameter("methods", $methods);
                // $event->setParameter("currency", $currency);
                $event->addSnippet('@komoju/default/shopping/komoju_multipay.twig');
            }
        }
    }

    /**
     * @param EventArgs $event
     */
    public function onFrontShoppingCompleteInitialize(EventArgs $event){
        $Order=$event->getArgument('Order');
        if($Order) {
            if ($Order->getPayment()->getMethodClass() === KomojuMultiPay::class) {
                $komoju_order_repo = $this->entityManager->getRepository(KomojuOrder::class);
                $komoju_order = $komoju_order_repo->findOneBy(array('Order'=>$Order));
                if($komoju_order) {
                    $payment_id = $komoju_order->getKomojuPaymentId();
                    if (!empty($payment_id) && $komoju_order->isCaptured()) {
                        $Today = new \DateTime();
                        $Order->setPaymentDate($Today);
                        $OrderStatus = $this->entityManager->getRepository(OrderStatus::class)->find(OrderStatus::PAID);
                        $Order->setOrderStatus($OrderStatus);
                        $this->entityManager->persist($Order);
                        $this->entityManager->flush($Order);
                    }
                }
            }
        }
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminOrderIndexTwig(TemplateEvent $event){
        $pagination = $event->getParameter("pagination");
        if (empty($pagination) || count($pagination) == 0)
        {
            return;
        }

        $OrderToSearch=array();
        foreach ($pagination as $Order){
            $OrderToSearch[] = $Order;
        }
        if (empty($OrderToSearch)) {
            return;
        }

        
        $komoju_order_repo = $this->entityManager->getRepository(KomojuOrder::class);
        $komoju_orders = $komoju_order_repo->findBy(['Order'    =>  $OrderToSearch]);

        if(empty($komoju_orders)){
            return;
        }
        $komoju_orders_mapping = [];

        $komoju_order_mapping = array();
        foreach($komoju_orders as $komoju_order){
            $Order = $komoju_order->getOrder();
            
            if($komoju_order->getKomojuPaymentId()){
                $dashboard_url = $this->getKomojuDashboardLink($komoju_order->getKomojuPaymentId());
            }else{
                $dashboard_url = null;
            }
            $order_edit_url = $this->container->get('router')->generate('admin_order_edit', ['id' => $Order->getId(), UrlGeneratorInterface::ABSOLUTE_URL ]);
            $komoju_order_mapping[] = (object)['order_edit_url' => str_replace("?0=0", "", $order_edit_url), 'payment_id' => $komoju_order->getKomojuPaymentId(), 'dashboard_url' => $dashboard_url];            
            
        }
        
        $event->setParameter('komoju_order_mapping', $komoju_order_mapping);
        $event->addAsset('@komoju\admin\order_index.js.twig');
    }
    
    /**
     * @param TemplateEvent
     */
    public function onAdminOrderEditTwig(TemplateEvent $event){
        $Order = $event->getParameter("Order");

        if(!$Order || empty($Order->getPayment())){
            return;
        }
        if ($Order->getPayment()->getMethodClass() === KomojuMultiPay::class) {
            $komoju_order = $this->entityManager->getRepository(KomojuOrder::class)->findOneBy(['Order' => $Order]);
            if(empty($komoju_order)  
                || empty($komoju_order->getKomojuPaymentId())
                // || $komoju_order->getType() !== "credit_card" 
                ){
                return ;
            }
            if(!$komoju_order->getIsChargeRefunded() && $komoju_order->getSelectedRefundOption() === 0 && $komoju_order->getRefundedAmount() == 0){
                $komoju_order->setRefundedAmount($Order->getPaymentTotal());
                $this->entityManager->persist($komoju_order);
                $this->entityManager->flush();
            }
            $refund_full_option = KomojuOrder::REFUND_FULL;
            $refund_partial_option = KomojuOrder::REFUND_PARTIAL;

            $order_canceled = $Order->getOrderStatus()->getId() == OrderStatus::CANCEL;
            
            $event->setParameter("komoju_order", $komoju_order);
            $event->setParameter("order_canceled", $order_canceled);
            $event->setParameter("komoju_dashboard_link", $this->getKomojuDashboardLink($komoju_order->getKomojuPaymentId()));
            $event->setParameter('REFUND_FULL_OPTION',  $refund_full_option);
            $event->setParameter('REFUND_PARTIAL_OPTION',  $refund_partial_option);
            $event->addSnippet("@komoju/admin/order_edit.twig");
        }
    }

    private function getKomojuDashboardLink($komoju_payment_id){
        return "https://komoju.com/admin/payments/$komoju_payment_id";
    }
}