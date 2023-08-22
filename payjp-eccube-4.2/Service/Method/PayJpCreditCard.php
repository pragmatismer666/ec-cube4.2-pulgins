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

namespace Plugin\PayJp\Service\Method;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\Form\FormInterface;
use Plugin\PayJp\Entity\PayJpConfig;
use Plugin\PayJp\Repository\PayJpConfigRepository;
use Plugin\PayJp\Entity\PayJpLog;
use Plugin\PayJp\Repository\PayJpLogRepository;
use Plugin\PayJp\Entity\PayJpOrder;
use Plugin\PayJp\Repository\PayJpOrderRepository;
use Plugin\PayJp\PayJpClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Eccube\Entity\Payment;

/**
 * クレジットカード(トークン決済)の決済処理を行う.
 */
class PayJpCreditCard implements PaymentMethodInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PayJpConfigRepository
     */
    private $payJpConfigRepository;

    /**
     * @var PayJpLogRepository
     */
    private $payJpLogRepository;

    /**
     * @var PayJpOrderRepository
     */
    private $payJpOrderRepository;


    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * CreditCard constructor.
     *
     * EccubeConfig $eccubeConfig
     * @param EntityManagerInterface $entityManager
     * @param OrderStatusRepository $orderStatusRepository
     * @param PayJpConfigRepository $payJpConfigRepository
     * @param PayJpLogRepository $payJpLogRepository
     * @param PayJpOrderRepository $payJpOrderRepository
     * @param ContainerInterface $containerInterface,
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        EntityManagerInterface $entityManager,
        OrderStatusRepository $orderStatusRepository,
        PayJpConfigRepository $payJpConfigRepository,
        PayJpLogRepository $payJpLogRepository,
        PayJpOrderRepository $payJpOrderRepository,
        ContainerInterface $containerInterface,
        PurchaseFlow $shoppingPurchaseFlow
    ) {
        $this->eccubeConfig=$eccubeConfig;
        $this->entityManager = $entityManager;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->payJpConfigRepository = $payJpConfigRepository;
        $this->payJpLogRepository = $payJpLogRepository;
        $this->payJpOrderRepository = $payJpOrderRepository;
        $this->container = $containerInterface;
        $this->purchaseFlow = $shoppingPurchaseFlow;
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * クレジットカードの有効性チェックを行う.
     *
     * @return PaymentResult
     *
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function verify()
    {
        // $payJpToken = $this->Order->getPayJpToken();

        // if (!empty($payJpToken)) {
        //     $result = new PaymentResult();
        //     $result->setSuccess(true);
        // } else {
        //     $result = new PaymentResult();
        //     $result->setSuccess(false);
        //     $result->setErrors([trans('pay_jp.front.unexpected_error')]);
        // }

        // return $result;
        $result = new PaymentResult();
        $payment_repo = $this->entityManager->getRepository(Payment::class);
        $payJp_payment = $payment_repo->findOneBy(['method_class' => PayJpCreditCard::class]);
        $min = $payJp_payment->getRuleMin();
        $max = $payJp_payment->getRuleMax();

        $total = $this->Order->getPaymentTotal();

        if($min !== null && $total < $min) {
            $result->setSuccess(false);
            $result->setErrors(['stripe.shopping.verify.error.payment_total.too_small']);
            return $result;
        }
        if($max !== null && $total > $max) {
            $result->setSuccess(false);
            $result->setErrors(['stripe.shopping.verify.error.payment_total.too_much']);
            return $result;
        }
        $result->setSuccess(true);
        return $result;
    }

    /**
     * 注文時に呼び出される.
     *
     * 受注ステータス, 決済ステータスを更新する.
     * ここでは決済サーバとの通信は行わない.
     *
     * @return PaymentDispatcher|null
     */
    public function apply()
    {
        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

    }

    /**
     * 注文時に呼び出される.
     *
     * クレジットカードの決済処理を行う.
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $payjp_order = $this->entityManager->getRepository(PayJpOrder::class)->findOneBy(['Order' => $this->Order]);
        $chargeId = $payjp_order->getPayJpChargeId();

        $payjp_config = $this->payJpConfigRepository->getConfigByOrder($this->Order);
        $payjp_client = new PayJpClient($payjp_config->api_key_secret);
        $is_auth_capture = $payjp_config->is_auth_and_capture_on;

        $charge = $payjp_client->retrievePaymentIntent($chargeId);

        if ($charge->card->three_d_secure_status === 'unverified' 
            || $charge->card->three_d_secure_status === 'failed' 
            || $charge->card->three_d_secure_status === 'error'
        ) {
            $response = new RedirectResponse($this->generateUrl('shopping', array('payjp_card_error' => '3d verification failed')));
            $result->setResponse($response);
            return $result;
        }
        
        //BOC capture payment if auth & pay is off
        if ($is_auth_capture && !$charge->captured) {//Capture if on
            $this->writeRequestLog($this->Order, 'capturePaymentIntent');
            $charge = $payjp_client->capturePaymentIntent($chargeId, $this->Order->getPaymentTotal(), $this->Order->getCurrencyCode());
            $this->writeResponseLog($this->Order, 'capturePaymentIntent', $charge);
        }

        // 支払いを作成できなかったらエラー
        if (is_array($charge) && isset($charge['error'])) {
            $errorMessage = PayJpClient::getErrorMessageFromCode($charge['error'], $this->eccubeConfig['locale']);
            $this->purchaseFlow->rollback($this->Order, new PurchaseContext());
            $result->setSuccess(false);
            $result->setErrors([$errorMessage]);
            $response = new RedirectResponse($this->generateUrl('shopping', array('payjp_card_error' => $errorMessage)));
            $result->setResponse($response);
            return $result;
        }
        //EOC capture payment if auth & pay is off
        if ($is_auth_capture){
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
            $this->Order->setOrderStatus($OrderStatus);
            $this->entityManager->persist($this->Order);
            $this->entityManager->flush();
        }

        //BOC create payJp Order
        // 注文と関連付ける
        
        $payjp_order->setPayJpToken($chargeId);

        if(!empty($chargeId)) {
            $payjp_order->setPayJpChargeId($chargeId);
        }
        // if ($is_auth_capture) {
        //     foreach($charge->charges as $charge) {
        //         $payjp_order->setPayJpChargeId($charge->id);
        //         break;
        //     }
        // }
        $payjp_order->setIsChargeCaptured($charge->captured);
        $Customer = $this->Order->getCustomer();
        $isEcCustomer=false;
        if($Customer instanceof Customer ) {
            $isEcCustomer = true;
        }
        if (!$isEcCustomer) {
            $payjp_order->setPayJpCustomerIdForGuestCheckout($charge->customer);
        } else {
            $payjp_order->setPayJpCustomerIdForGuestCheckout('');
        }
        $payjp_order->setCreatedAt(new \DateTime());
        $this->entityManager->persist($payjp_order);
        // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        $result->setSuccess(true);
        //EOC create payJp Order
        return $result;
    }


    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }

    private function writeRequestLog(Order $order, $api) {
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] リクエスト実行';
        log_info($logMessage);

        $payJpLog = new PayJpLog();
        $payJpLog->setMessage($logMessage);
        $payJpLog->setCreatedAt(new \DateTime());
        $this->entityManager->persist($payJpLog);
    }

    private function writeResponseLog(Order $order, $api, $result) {
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

    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
}