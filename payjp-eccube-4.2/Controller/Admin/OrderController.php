<?php
/*
* Plugin Name : PayJp
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Plugin\PayJp\Controller\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\PayJp\Repository\PayJpLogRepository;
use Plugin\PayJp\Repository\PayJpConfigRepository;
use Plugin\PayJp\Repository\PayJpOrderRepository;
use Plugin\PayJp\Entity\PayJpOrder;
use Plugin\PayJp\Entity\PayJpCustomer;
use Plugin\PayJp\Repository\PayJpCustomerRepository;
use Plugin\PayJp\Service\Method\PayJpCreditCard;
use Plugin\PayJp\Entity\PayJpLog;
use Plugin\PayJp\PayJpClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\RouterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PayJpConfigRepository
     */
    protected $payJpConfigRepository;

    /**
     * @var PayJpOrderRepository
     */
    private $payJpOrderRepository;

    /**
     * @var PayJpCustomerRepository
     */
    private $payJpCustomerRepository;

    /**
     * ConfigController constructor
     * 
     * @param EccubeConfig $eccubeConfig
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param PayJpConfigRepository $payJpConfigRepository
     * @param PayJpOrderRepository $payJpOrderRepository
     * @param PayJpCustomerRepository $payJpCustomerRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        PayJpConfigRepository $payJpConfigRepository,
        PayJpOrderRepository $payJpOrderRepository,
        PayJpCustomerRepository $payJpCustomerRepository
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->payJpConfigRepository = $payJpConfigRepository;
        $this->payJpOrderRepository = $payJpOrderRepository;
        $this->payJpCustomerRepository = $payJpCustomerRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/pay_jp_payment_gateway/order_payment/{id}/capture_transaction", requirements={"id" = "\d+"}, name="pay_jp_admin_order_capture")
     */
    public function charge(Request $request, $id = null, RouterInterface $router)
    {
        //BOC check if order exist
        /** @var Order $Order */
        $Order = $this->orderRepository->find($id);
        if (null === $Order) {
            $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }

        $PayJpConfig = $this->payJpConfigRepository->getConfigByOrder($Order);
        //EOC check if order exist

        //BOC check if PayJp Order
        /** @var PayJpOrder $payJpOrder **/
        $payJpOrder = $this->payJpOrderRepository->findOneBy(array('Order' => $Order));
        if (null === $payJpOrder) {
            $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
        }
        //EOC check if PayJp Order

        //BOC check if refunded
        if ($payJpOrder->getIsChargeRefunded()) {
            $this->addError('pay_jp.admin.order.error.refunded', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);   
        }
        //EOC check if refunded

        //BOC check if already captured
        if ($payJpOrder->getIsChargeCaptured()) {
            $this->addError('pay_jp.admin.order.error.alreay_captured', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
        }
        //EOC check if alreay captured

        //BOC retrieve and check if captured for order_id alreay
        $payJpClient = new PayJpClient($PayJpConfig->api_key_secret);
        if ($payJpClient->isChargeId($payJpOrder->getPayJpChargeId())){
            $paymentIntent = $payJpClient->retrievePaymentIntent($payJpOrder->getPayJpChargeId());    
            if( is_array($paymentIntent) && isset($paymentIntent['error']) ) {
                $this->addError(PayJpClient::getErrorMessageFromCode($paymentIntent['error'], $this->eccubeConfig['locale']), 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }

            if($paymentIntent->metadata->order == $Order->getId() && $paymentIntent->captured==true){
                //BOC update charge id and capture status
                // foreach($paymentIntent->charges as $charge) {
                //     $payJpOrder->setPayJpChargeId($charge->id);
                //     break;
                // }
                $payJpOrder->setPayJpChargeId($paymentIntent->id);
                $payJpOrder->setIsChargeCaptured(true);
                $this->entityManager->persist($payJpOrder);
                $this->entityManager->flush($payJpOrder);
                //EOC update charge id and capture status

                //BOC update payment status
                $payJpChargeID = $payJpOrder->getPayJpChargeId();
                $chargeCaptured = $payJpOrder->getIsChargeCaptured();
                if (!empty($payJpChargeID) && $chargeCaptured) {
                    $Today = new \DateTime();
                    $Order->setPaymentDate($Today);
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);
                }
                //EOC update payment status
                
                $this->addError('pay_jp.admin.order.error.already_captured', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC retrieve and check if captured for order_id already

            //BOC capture payment
            $this->writeRequestLog($Order, 'capturePaymentIntent');
            $paymentIntent = $payJpClient->capturePaymentIntent($paymentIntent->id, $Order->getPaymentTotal(), $Order->getCurrencyCode());
            $this->writeResponseLog($Order, 'capturePaymentIntent', $paymentIntent);
            //EOC capture payment

            //BOC check if error
            if (is_array($paymentIntent) && isset($paymentIntent['error'])) {
                $errorMessage = PayJpClient::getErrorMessageFromCode($paymentIntent['error'], $this->eccubeConfig['locale']);

                $this->addError($errorMessage, 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }//EOC check if error
            else {
                //BOC update charge id and capture status
                // foreach($paymentIntent->charges as $charge) {
                //     $payJpOrder->setPayJpChargeId($charge->id);
                //     break;
                // }
                
                $payJpOrder->setPayJpChargeId($paymentIntent->id);
                $payJpOrder->setIsChargeCaptured(true);
                $this->entityManager->persist($payJpOrder);
                $this->entityManager->flush($payJpOrder);
                //EOC update charge id and capture status
                
                //BOC update payment status
                $payJpChargeID = $payJpOrder->getPayJpChargeId();
                $chargeCaptured = $payJpOrder->getIsChargeCaptured();
                if (!empty($payJpChargeID) && $chargeCaptured) {
                    $Today = new \DateTime();
                    $Order->setPaymentDate($Today);
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);
                }
                //EOC update payment status
                
                $this->addSuccess('pay_jp.admin.order.success.capture', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
        } else if ($payJpClient->isPayJpToken($payJpOrder->getPayJpChargeId())) {
            //BOC check if PayJp Customer
            $Customer = $Order->getCustomer();
            $isEcCustomer = false;
            $isPayJpCustomer = false;
            if ($Customer instanceof Customer) {
                $isEcCustomer = true;
                $PayJpCustomer = $this->payJpCustomerRepository->findOneBy(array('Customer' => $Customer));
                if ($PayJpCustomer instanceof PayJpCustomer) {
                    $isPayJpCustomer = true;
                }
            }
            //EOC check if PayJp Customer

            //BOC retrieve payjp customer id
            if ($isPayJpCustomer) {
                $payJpCustomerId = $PayJpCustomer->getPayJpCustomerId();
            } else if (!$isEcCustomer && $payJpOrder->getPayJpCustomerIdForGuestCheckout()) {
                $payJpCustomerId = $payJpOrder->getPayJpCustomerIdForGuestCheckout();
            } else {
                $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC retrieve payjp customer id

            //BOC capture payment
            $this->writeRequestLog($Order, 'createChargeWithCustomer');
            $chargeResult = $payJpClient->createChargeWithCustomer($Order->getPaymentTotal(), $payJpCustomerId, $Order->getId(), true);
            $this->writeResponseLog($Order, 'createChargeWithCustomer', $chargeResult);
            //EOC capture payment

            //BOC check if error
            if (is_array($chargeResult) && isset($chargeResult['error'])) {
                $errorMessage = PayJpClient::getErrorMessageFromCode($chargeResult['error'], $this->eccubeConfig['locale']);

                $this->addError($errorMessage, 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            } //EOC check if error
            else {

                //BOC update charge id and capture status
                $payJpOrder->setPayJpChargeId($chargeResult->__get('id'));
                $payJpOrder->setIsChargeCaptured(true);
                $this->entityManager->persist($payJpOrder);
                $this->entityManager->flush($payJpOrder);
                //EOC update charge id and capture status

                //BOC update payment status
                $payJpChargeID = $payJpOrder->getPayJpChargeId();
                if (!empty($payJpChargeID)) {
                    $Today = new \DateTime();
                    $Order->setPaymentDate($Today);
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);

                }
                //EOC update payment status

                $this->addSuccess('pay_jp.admin.order.success.capture', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
        } else {
            $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }

    }

    /**
     * @Route("/%eccube_admin_route%/pay_jp_payment_gateway/order_payment/{id}/refund_transaction", requirements={"id" = "\d+"}, name="pay_jp_admin_order_refund")
     */
    public function refund(Request $request, $id = null, RouterInterface $router){
        //BOC check if order exist
        $Order = $this->orderRepository->find($id);
        if (null == $Order) {
            $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }
        //EOC check if order exist

        $PayJpConfig = $this->payJpConfigRepository->getConfigByOrder($Order);

        if ($request->getMethod() == 'POST'){

            //BOC check if PayJp Order
            $payJpOrder = $this->payJpOrderRepository->findOneBy(array('Order' => $Order));
            if (null === $payJpOrder) {
                $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC check if PayJp Order

            //BOC check if refunded
            if ($payJpOrder->getIsChargeRefunded()) {
                $this->addError('pay_jp.admin.order.error.refunded', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC check if refunded

            //BOC retrieve and check if valid charge id and not already refunded
            $payJpClient = new PayJpClient($PayJpConfig->api_key_secret);
            $chargeForOrder = $payJpClient->retrieveCharge($payJpOrder->getPayJpChargeId());
            if (isset($chargeForOrder)) {
                if ($chargeForOrder->refunded) {

                    //BOC update charge id and capture status
                    $payJpOrder->setIsChargeRefunded(true);
                    $this->entityManager->persist($payJpOrder);
                    $this->entityManager->flush($payJpOrder);
                    //EOC update charge id and capture status

                    //BOC update Order Status
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);
                    //EOC update Order Status

                    $this->addError('pay_jp.admin.order.error.refunded', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                }
            } else {
                $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC retrieve and check if valid charge id and not already refunded

            //BOC refund option and amount calculation
            $refund_option = $request->request->get('refund_option');
            $refund_amount = 0;
            //BOC partial refund
            if ($refund_option == 3) {
                $refund_amount = $request->request->get('refund_amount');
                if (empty($refund_amount) || !is_int($refund_amount+0)) {
                    $this->addError('pay_jp.admin.order.refund_amount.error.invalid', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                } else if($refund_amount>$Order->getPaymentTotal()){
                    $this->addError('pay_jp.admin.order.refund_amount.error.exceeded', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                }
            }
            //EOC partial refund
            //BOC calculate refund amount based on fees entered
            if($refund_option==2){
                if($PayJpConfig->payjp_fees_percent == 0){
                    $this->addError('pay_jp.admin.order.refund_option.error.invalid', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                }
                $refund_amount=floor($Order->getPaymentTotal()-($Order->getPaymentTotal()*($PayJpConfig->payjp_fees_percent/100)));
            }
            //EOC calculate refund amount based on fees entered
            //BOC full refund option
            if($refund_option==1){
                $refund_amount=floor($Order->getPaymentTotal());
            }
            //EOC full refund option
            //BOC refund option and amount calculation

            //BOC refund payment
            $this->writeRequestLog($Order, 'createRefundForCharge');
            $chargeResult = $payJpClient->createRefund($payJpOrder->getPayJpChargeId(),$refund_amount,$Order->getCurrencyCode());
            $this->writeResponseLog($Order, 'createRefundForCharge', $chargeResult);
            //EOC refund payment

            //BOC check if error
            if (is_array($chargeResult) && isset($chargeResult['error'])) {
                $errorMessage = PayJpClient::getErrorMessageFromCode($chargeResult['error'], $this->eccubeConfig['locale']);

                $this->addError($errorMessage, 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC check if error

            //BOC update charge id and capture status
            $payJpOrder->setIsChargeRefunded(true);
            $payJpOrder->setSelectedRefundOption($refund_option);
            $payJpOrder->setRefundedAmount($refund_amount);
            $this->entityManager->persist($payJpOrder);
            $this->entityManager->flush($payJpOrder);
            //EOC update charge id and capture status

            //BOC update Order Status
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
            $Order->setOrderStatus($OrderStatus);
            $this->entityManager->persist($Order);
            $this->entityManager->flush($Order);
            //EOC update Order Status

            $this->addSuccess('pay_jp.admin.order.success.capture', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
        } else {
            $this->addError('pay_jp.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }
    }

    private function writeRequestLog(Order $order, $api) {
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] リクエスト実行';
        log_info($logMessage);

        $payJplog = new PayJpLog();
        $payJplog->setMessage($logMessage);
        $payJplog->setCreatedAt(new \DateTime());
        $this->entityManager->persist($payJplog);
    }

    private function writeResponseLog(Order $order, $api, $result){
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] ';
        if (is_object($result)) {
            $logMessage .= '成功';
        } elseif (! is_array($result)) {
            $logMessage .= print_r($result, true);
        } elseif (isset($result['error'])) {
            $logMessage .= $result['error']['message'];
        } else {
            $logMessage .= '成功';
        }
        log_info($logMessage);
        $payJpLog = new PayJpLog();
        $payJpLog->setMessage($logMessage);
        $payJpLog->setCreatedAt(new \DateTime());
        $this->entityManager->persist($payJpLog);
    }
}