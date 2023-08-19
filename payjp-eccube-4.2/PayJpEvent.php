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

namespace Plugin\PayJp;

use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Eccube\Entity\Payment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\PayJp\Repository\PayJpConfigRepository;
use Plugin\PayJp\Service\Method\PayJpCreditCard;
use Plugin\PayJp\Entity\PayJpOrder;
use Plugin\PayJp\Repository\PayJpOrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PayJpEvent implements EventSubscriberInterface
{

    /**
     * @var エラーメッセージ
     */
    private $errorMessage = null;

    /**
     * @var 国際化
     */
    private static $i18n = array();

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var PayJpConfigRepository
     */
    protected $payJpConfigRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PayJpOrderRepository
     */
    private $payJpOrderRepository;

    /**
     * @var string ロケール（jaかenのいずれか）
     */
    private $locale = 'en';

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    protected $container;

    public function __construct(
        EccubeConfig $eccubeConfig,
        PayJpConfigRepository $payJpConfigRepository,
        PayJpOrderRepository $payJpOrderRepository,
        OrderStatusRepository $orderStatusRepository,
        EntityManagerInterface $entityManager,
        ContainerInterface $container
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->locale=$this->eccubeConfig['locale'];
        $this->payJpConfigRepository = $payJpConfigRepository;
        $this->payJpOrderRepository = $payJpOrderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'onShoppingIndexTwig',
            'Shopping/confirm.twig' => 'onShoppingConfirmTwig',
            'front.shopping.complete.initialize'=>'onFrontShoppingCompleteInitialize',

            '@admin/Order/index.twig' => 'onAdminOrderIndexTwig',
            '@admin/Order/edit.twig' => 'onAdminOrderEditTwig'
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function onShoppingIndexTwig(TemplateEvent $event)
    {
        $Order=$event->getParameter('Order');
        //dump($Order); die;
        if($Order) {

            if ($Order->getPayment()->getMethodClass() === PayJpCreditCard::class && $this->isEligiblePaymentMethod($Order->getPayment(),$Order->getPaymentTotal())) {
                /*if(isset($_REQUEST['card_error'])){
                    $this->errorMessage=$_REQUEST['card_error'];
                }*/

                $PayJpConfig = $this->payJpConfigRepository->get();
                $payJpCSS = 'PayJp/Resource/assets/css/pay_jp.css.twig';
                $event->addAsset($payJpCSS);

                $payJpOfficialJS = 'PayJp/Resource/assets/js/pay_jp_official.js.twig';
                $event->addAsset($payJpOfficialJS);

                // JSファイルがなければオンデマンドで生成
                if (!file_exists($this->getScriptDiskPath())) {
                    $this->makeScript();
                }
                $event->setParameter('payJpLocale', $this->locale);
                $event->setParameter('payJpConfig', $PayJpConfig);
                $event->setParameter('payJpErrorMessage', $this->errorMessage);
                $event->setParameter('payJpCreditCardPaymentId', $Order->getPayment()->getId());

                // $payJpJS= 'PayJp/Resource/assets/js/pay_jp_' . $this->locale . '.js.twig';
                // $event->addAsset($payJpJS);

                // $event->addSnippet('@PayJp/default/Shopping/pay_jp_credit_card.twig');
            }
        }
    }

    /**
     * @param TemplateEvent $event
     */
    public function onShoppingConfirmTwig(TemplateEvent $event)
    {
        $Order=$event->getParameter('Order');
        if($Order) {

            if ($Order->getPayment()->getMethodClass() === PayJpCreditCard::class) {

                $PayJpConfig = $this->payJpConfigRepository->get();

                $event->setParameter('payJpConfig', $PayJpConfig);
                $event->setParameter('payJpErrorMessage', $this->errorMessage);
                //$event->addSnippet('@PayJp/default/Shopping/pay_jp_credit_card_confirm.twig');
                $event->addSnippet('@PayJp/default/Shopping/shopping.js.twig');
            }
        }
    }

    /**
     * @param EventArgs $event
     */
    public function onFrontShoppingCompleteInitialize(EventArgs $event){
        $Order=$event->getArgument('Order');
        if($Order) {
            if ($Order->getPayment()->getMethodClass() === PayJpCreditCard::class) {
                $payJpOrder = $this->payJpOrderRepository->findOneBy(array('Order'=>$Order));
                if($payJpOrder) {
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
                }
            }
        }
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminOrderIndexTwig(TemplateEvent $event)
    {
        // 表示対象の受注一覧を取得
        $pagination = $event->getParameter('pagination');

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

        $PayJpOrders = $this->payJpOrderRepository->findBy(array('Order'=>$OrderToSearch));

        if (!$PayJpOrders)
        {
            return;
        }

        $PayJpOrdersMapping = array();
        foreach($PayJpOrders as $payJpOrder) {
            $Order = $payJpOrder->getOrder();
            $OrderId = $Order->getId();
            $dashboard_url = $this->getPayJpChargeDashboardLink() . $payJpOrder->getPayJpChargeId();
            $order_edit_url = $this->container->get('router')->generate('admin_order_edit', array('id' => $OrderId), UrlGeneratorInterface::ABSOLUTE_URL);
            $PayJpOrdersMapping[] = (object)[
                'order_edit_url' => $order_edit_url,
                'charge_id' => $payJpOrder->getPayJpChargeId(),
                'dashboard_url' => $dashboard_url,
                'is_charge_captured' => $payJpOrder->getIsChargeCaptured(),
                'Order'   =>  $Order,
            ];
        }
        //$event->setParameter('PayJpOrders', $PayJpOrders);
        $event->setParameter('PayJpOrdersMapping', $PayJpOrdersMapping);
        //$event->setParameter('payJpChargeDashboardLink',$this->getPayJpChargeDashboardLink());

        $asset = 'PayJp/Resource/assets/js/admin/order_index.js.twig';
        $event->addAsset($asset);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminOrderEditTwig(TemplateEvent $event)
    {
        // 表示対象の受注情報を取得
        $Order = $event->getParameter('Order');

        if (!$Order)
        {
            return;
        }
        $PayJpConfig = $this->payJpConfigRepository->getConfigByOrder($Order);
        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            return;
        }

        if ($Order->getPayment()->getMethodClass() === PayJpCreditCard::class) {

            $PayJpOrder = $this->payJpOrderRepository->findOneBy(array('Order'=>$Order));

            if (!$PayJpOrder)
            {
                return;
            }
            if($PayJpOrder->getIsChargeRefunded()==1 && $PayJpOrder->getSelectedRefundOption()==0 && $PayJpOrder->getRefundedAmount()==0) {
                $PayJpOrder->setSelectedRefundOption(1);
                $PayJpOrder->setRefundedAmount($Order->getPaymentTotal());
                $this->entityManager->persist($PayJpOrder);
                $this->entityManager->flush($PayJpOrder);
            }

            $publishableKey=$PayJpConfig->public_api_key;
            if(strpos($publishableKey, 'live') !== false) {
                $isLive = true;
            } else {
                $isLive = false;
            }
            $event->setParameter('PayJpConfig', $PayJpConfig);
            $event->setParameter('PayJpOrder', $PayJpOrder);
            $event->setParameter('PayJpChargeDashboardLink',$this->getPayJpChargeDashboardLink($isLive));

            $event->addSnippet('@PayJp/admin/Order/edit.twig');
            //$asset = 'PayJp/Resource/assets/js/admin/order_edit.js.twig';
            //$event->addAsset($asset);
        }
    }

    private function getScriptDiskPath() {
        return dirname(__FILE__).'/Resource/assets/js/pay_jp_' . $this->locale . '.js.twig';
    }

    private function makeScript() {
        $buff = file_get_contents(dirname(__FILE__) . '/Resource/assets/js/pay_jp.js.twig');
        $out_path = $this->getScriptDiskPath();
        $m = array();
        preg_match_all('/\{\{ (\w+) \}\}/', $buff, $m);
        for ($i = 0; $i < sizeof($m[0]); $i++) {
            //$buff = str_replace($m[0][$i], self::getLocalizedString($m[1][$i], $this->locale), $buff);
            if($m[1][$i]=='locale'){
                $buff = str_replace($m[0][$i], $this->locale, $buff);
            }
        }
        file_put_contents($out_path, $buff);
    }

    private function getPayJpChargeDashboardLink(){
        $chargeDashboardLink='https://pay.jp/d/charges/';
        return $chargeDashboardLink;
    }

    public static function getLocalizedString($id, $locale) {
        if (! isset(self::$i18n[$locale])) {
            $tmp_loader = new \Symfony\Component\Translation\Loader\YamlFileLoader();
            $catalogue = $tmp_loader->load(dirname(__FILE__) . "/Resource/locale/messages.$locale.yml", 'ja', 'pay_jp');
            self::$i18n[$locale] = $catalogue->all('pay_jp');
        }
        if (isset(self::$i18n[$locale][$id])) {
            return self::$i18n[$locale][$id];
        }
        return '--';
    }

    private function isEligiblePaymentMethod(Payment $Payment,$total){
        $min = $Payment->getRuleMin();
        $max = $Payment->getRuleMax();
        if (null !== $min && $total < $min) {
            return false;
        }
        if (null !== $max && $total > $max) {
            return false;
        }
        return true;
    }

}
