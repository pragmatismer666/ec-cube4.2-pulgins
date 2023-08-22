<?php

namespace Plugin\PayJp\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Eccube\Service\MailService;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Controller\ShoppingController;
use Plugin\PayJp\Entity\PayJpConfig;
use Plugin\PayJp\PayJpClient;
use Plugin\PayJp\Entity\PayJpCustomer;
use Eccube\Controller\AbstractController;

$currentPluginDir=dirname(dirname(__FILE__));
$vendorDir = $currentPluginDir."/vendor";
require_once($vendorDir.'/payjp-php/init.php');

//BOC add files from folder
require_once $vendorDir.'/php-jws-master/src/Exception/JWSException.php';
foreach (glob($vendorDir.'/php-jws-master/src/**/*.php') as $filename) {
    require_once $filename;
}
require_once $vendorDir.'/php-jws-master/src/JWS.php';
//EOC add files from folder

use Payjp;
use Payjp\Card;
use Payjp\Charge;
use Plugin\PayJp\Entity\PayJp3dConfig;
use Plugin\PayJp\Entity\PayJpOrder;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PayJpPaymentController extends AbstractController {

    protected $container;
    private $payjp_config;
    protected $entityManager;
    protected $orderHelper;
    protected $cartService;

    public function __construct(
        ContainerInterface $container,
        CartService $cartService,
        OrderHelper $orderHelper
    ) {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->payjp_config = $this->entityManager->getRepository(PayJpConfig::class)->get();
        $this->orderHelper = $orderHelper;
        $this->cartService = $cartService;
    }

    /**
     * @Route("/plugin/pay_jp_payment_gateway/credit_card", name="plugin_payjp_credit_card")
     * @Template("@PayJp/default/Shopping/pay_jp_credit_card.twig")
     */
    public function credit_payment(Request $request)
    {
        // ログイン状態のチェック
        if ($this->orderHelper->isLoginRequired()){
            log_info('[注文処理] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');

            return $this->redirectToRoute('shopping_login');
        }
        // 受注の存在チェック
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            log_info('[注文処理] 購入処理中の受注が存在しません.', [$preOrderId]);

            return $this->redirectToRoute('shopping_error');
        }
        $PayJpConfig = $this->entityManager->getRepository(PayJpConfig::class)->getConfigByOrder($Order);
        $Customer = $Order->getCustomer();
        // フォームの生成
        $form = $this->createForm(OrderType::class, $Order,[
            'skip_add_form' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($this->isGranted('ROLE_USER')){
                $payJpPaymentMethodObj = $this->checkSaveCardOn($Customer, $PayJpConfig);
                if($payJpPaymentMethodObj) {
                    $exp = \sprintf("%02d/%d", $payJpPaymentMethodObj->exp_month, $payJpPaymentMethodObj->exp_year);
                } else {
                    $exp = "";
                }
            } else {
                $payJpPaymentMethodObj = false;
                $exp = "";
            }

            $nonmem = false;
            if (!$this->getUser() && $this->orderHelper->getNonMember()) {
                $nonmem = true;
            }

            return [
                'payJpConfig' => $PayJpConfig,
                'Order' => $Order,
                'payJpPaymentMethodObj' => $payJpPaymentMethodObj,
                'exp' => $exp,
                'nonmem' => $nonmem,
                'form' => $form->createView(),
            ];
        }
        return $this->redirectToRoute('shopping');
    }

    /**
     * @Route("/plugin/pay_jp_payment_gateway/credit_card_td_return", name="plugin_payjp_credit_card_td_return")
     * @Template("@PayJp/default/Shopping/pay_jp_credit_card_td_return.twig")
     */
    public function tdReturn(Request $request)
    {
        // ログイン状態のチェック
        if ($this->orderHelper->isLoginRequired()){
            log_info('[注文処理] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');
            
            return $this->redirectToRoute('shopping_login');
        }
        // 受注の存在チェック
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            log_info('[注文処理] 購入処理中の受注が存在しません.', [$preOrderId]);
            return $this->redirectToRoute('shopping_error');
        }
        
        $PayJpConfig = $this->entityManager->getRepository(PayJpConfig::class)->getConfigByOrder($Order);
        
        $payJpOrderRespository = $this->entityManager->getRepository(PayJpOrder::class);
        $payJpOrder = $payJpOrderRespository->findOneBy(['Order' => $Order]);

        $payJpClient = new PayJpClient($PayJpConfig->api_key_secret);

        if ($payJpOrder)
        {
            $chargeId = $payJpOrder->getPayJpChargeId();
            $charge = $payJpClient->tdsFinish($chargeId);
            if (!($charge instanceof Charge) || $charge->three_d_secure_status !== 'verified') {
                return $this->redirectToRoute('shopping_error');
            }
            $form = $this->createForm(OrderType::class, $Order,[
                'skip_add_form' => true,
            ]);
            return [
                'form' => $form->createView()
            ];
        } else {
            return $this->redirectToRoute('shopping_error');
        }
    }

    /**
     * @Route("/plugin/pay_jp_payment_gateway/method_detach", name="plugin_payjp_payment_method_detach")
     */
    public function detachMethod(Request $request){
        $method_id = $request->request->get('payment_method_id');
        $Order = $this->getOrder();
        if($Order){
            $Customer = $Order->getCustomer();
            $PayJpConfig = $this->entityManager->getRepository(PayJpConfig::class)->getConfigByOrder($Order);
            $PayJpCustomer=$this->entityManager->getRepository(PayJpCustomer::class)->findOneBy(array('Customer'=>$Customer));
            $PayJpCustomerId = $PayJpCustomer->getPayJpCustomerId();
            $payJpPaymentMethodObj = $this->checkSaveCardOn($Customer, $PayJpConfig);
            if($payJpPaymentMethodObj && $payJpPaymentMethodObj->id == $method_id){
                $payJpClient = new PayJpClient($PayJpConfig->api_key_secret);
                $res = $payJpClient->detachMethod($method_id, $PayJpCustomerId);
                //$PayJpCustomer=$this->entityManager->getRepository(PayJpCustomer::class)->findOneBy(array('Customer'=>$Customer));
                $PayJpCustomer->setIsSaveCardOn(false);
                $this->entityManager->persist($PayJpCustomer);
                $this->entityManager->flush();
                return $this->json([
                    'success'   =>  true,
                ]);
            }
        }
        return $this->json([
            'success'   =>  false,
            'error'     =>  "そのような済みはありません"
        ]);
    }

    /**
     * Here is normal payment
     * @Route("/plugin/pay_jp_payment_gateway/payment", name="plugin_payjp_payment_gateway_payment")
     */
    public function payment(Request $request){
        $preOrderId = $this->cartService->getPreOrderId();
        /** @var Order $Order */
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            return $this->json(['error' => 'true', 'message' => trans('pay_jp.admin.order.invalid_request')]);
        }

        $PayJpConfig = $this->entityManager->getRepository(PayJpConfig::class)->getConfigByOrder($Order);

        $payJpOrderRespository = $this->entityManager->getRepository(PayJpOrder::class);
        $payJpOrder = $payJpOrderRespository->findOneBy(['Order' => $Order]);

        $payJpClient = new PayJpClient($PayJpConfig->api_key_secret);

        if ($payJpOrder)
        {
            if ($payJpOrder->getPaidAt()) {
                return $this->json([
                    'captured'  =>  $payJpOrder->getIsChargeCaptured(),
                    'id'        =>  $payJpOrder->getPayJpChargeId()
                ]);
            }
            $chargeId = $payJpOrder->getPayJpChargeId();
            $charge = $payJpClient->retrieveCharge($chargeId);

            if (!($charge instanceof Charge))
            {
                return $this->json([
                    'error'     =>  true,
                    'message'   =>  $charge
                ]);
            }
            if ($charge->card->three_d_secure_status === 'unverified' || $charge->card->three_d_secure_status === 'failed' || $charge->card->three_d_secure_status === 'error')
            {
                return $this->json([
                    'error'     =>  true,
                    'message'   =>  '認証失敗'
                ]);
            } else {
                return $this->json([
                    'captured' =>   $charge->captured,
                    'id'       =>   $charge->id,
                ]);
            }
        } else {
            return $this->redirectToRoute('shopping_complete');
        }
    }

    /**
     * @Route("/plugin/pay_jp_payment_gateway/td_check", name="plugin_payjp_payment_td_check")
     */
    public function tdCheck(Request $request)
    {
        $preOrderId = $this->cartService->getPreOrderId();
        /** @var Order $Order */
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            return $this->json(['error' => 'true', 'message' => trans('pay_jp.admin.order.invalid_request')]);
        }

        $PayJpConfig = $this->entityManager->getRepository(PayJpConfig::class)->getConfigByOrder($Order);
        $payJpClient = new PayJpClient($PayJpConfig->api_key_secret);
        //$tokenId = $request->request->get('token_id');
        $cardId = $request->request->get('card_id');
        $isSaveCardOn = $request->request->get('is_save_on') === "true" ? true: false;
        $isExisting = $request->request->get('is_existing') === "true" ? true: false;
        $payJpCustomerId = $this->procPayJpCustomer($payJpClient, $Order, $isSaveCardOn);
        if(is_array($payJpCustomerId)) { // エラー
            return $this->json($payJpCustomerId);
        }
        $payJp3dConfig = $this->entityManager->getRepository(PayJp3dConfig::class)->get();
        $tdsFlag = $payJp3dConfig && $payJp3dConfig->getTdEnabled() && $payJp3dConfig->getTdMinValue() < $Order->getTotal();
        $amount = $Order->getPaymentTotal();
        $charge = $payJpClient->createPaymentIntentWithCustomer(
            $amount, 
            $cardId, 
            $Order->getId(), 
            $isSaveCardOn, 
            $isExisting, 
            $payJpCustomerId,
            $Order->getCurrencyCode(), 
            $tdsFlag
        );
        if(isset($charge->error)) {
            $errorMessage=PayJpClient::getErrorMessageFromCode($charge->error, $this->eccubeConfig['locale']);
            return $this->json([
                'error' =>  true,
                'message'=> $errorMessage
            ]);
        } else if (!($charge instanceof Charge)) {
            return $this->json([
                'error' => true,
                'message'=> 'Unexpected Error'
            ]);
        }
        $payJpOrder = $this->entityManager->getRepository(PayJpOrder::class)->findOneBy(['Order' => $Order]);
        if (!$payJpOrder) 
        {
            $payJpOrder = new PayJpOrder;
        }
        $payJpOrder->setOrder($Order);
        $payJpOrder->setPayJpToken($cardId);
        $payJpOrder->setPayJpChargeId($charge->id);
        $payJpOrder->setIsChargeCaptured($charge->captured);
        $payJpOrder->setCreatedAt(new \DateTime());
        if ($charge->paid) {
            $payJpOrder->setPaidAt(new \DateTime());
        }
        $this->entityManager->persist($payJpOrder);
        $this->entityManager->flush();

        
        if (!$tdsFlag)
        {
            return $this->json([
                'success'   =>  true,
            ]);
        }
        if ($charge->paid) {
            return $this->json([
                'success'   =>  true,
            ]);
        } else {
            $returnUrl = $this->container->get('router')->generate('plugin_payjp_credit_card_td_return', [], UrlGeneratorInterface::ABSOLUTE_URL);
            
            // JWS encoding return url
            $headers = array(
                'alg'   =>  'HS256',
                'typ'   =>  'JWT'
            );
            $payload = array(
                'url'   =>  $returnUrl
            );
            $jws = new \Gamegos\JWS\JWS();
            $backUrl = $jws->encode($headers, $payload, $PayJpConfig->api_key_secret);

            $redirectUrl = 'https://api.pay.jp/v1/tds/' . $charge->id . '/start?publickey=' . $PayJpConfig->public_api_key . '&back_url=' . $backUrl;
            return $this->json([
                'success'   =>  true,
                'td_check'  =>  true,
                'redirect_url'  =>  $redirectUrl
            ]);
        }
    }

    private static function getFormErrorsTree(FormInterface $form): array
    {
        $errors = [];

        if (count($form->getErrors()) > 0) {
            foreach ($form->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }
        } else {
            foreach ($form->all() as $child) {
                $childTree = self::getFormErrorsTree($child);

                if (count($childTree) > 0) {
                    $errors[$child->getName()] = $childTree;
                }
            }
        }

        return $errors;
    }

    private function procPayJpCustomer(PayJpClient $payJpClient, $Order, $isSaveCardOn) {

        $Customer = $Order->getCustomer();
        $isEcCustomer=false;
        $isPayJpCustomer=false;
        $PayJpCustomer = false;
        $payJpCustomerId = false;

        if($Customer instanceof Customer ){
            $isEcCustomer=true;
            $PayJpCustomer=$this->entityManager->getRepository(PayJpCustomer::class)->findOneBy(array('Customer'=>$Customer));
            if($PayJpCustomer instanceof PayJpCustomer){
                $payJpLibCustomer = $payJpClient->retrieveCustomer($PayJpCustomer->getPayJpCustomerId());
                if(is_array($payJpLibCustomer) || isset($payJpLibCustomer['error'])) {
                    if(isset($payJpLibCustomer['error']['code']) && $payJpLibCustomer['error']['code'] == 'resource_missing') {
                        $isPayJpCustomer = false;
                    }
                } else {
                    $isPayJpCustomer=true;
                }
            }
        }
        if($isEcCustomer) {//Create/Update customer
            if($isSaveCardOn) {
                //BOC check if is PayJpCustomer then update else create one
                if($isPayJpCustomer) {
                    $payJpCustomerId=$PayJpCustomer->getPayJpCustomerId();
                    //BOC save is save card
                    $PayJpCustomer->setIsSaveCardOn($isSaveCardOn);
                    $this->entityManager->persist($PayJpCustomer);
                    $this->entityManager->flush($PayJpCustomer);
                    //EOC save is save card

                    $updateCustomerStatus = $payJpClient->updateCustomer($payJpCustomerId, $Customer->getEmail());
                    if (is_array($updateCustomerStatus) && isset($updateCustomerStatus['error'])) {//In case of update fail
                        $errorMessage=PayJpClient::getErrorMessageFromCode($updateCustomerStatus['error'], $this->eccubeConfig['locale']);
                        return ['error' => true, 'message' => $errorMessage];
                    }
                } else {
                    $payJpCustomerId=$payJpClient->createCustomer($Customer->getEmail(),$Customer->getId());
                    if (is_array($payJpCustomerId) && isset($payJpCustomerId['error'])) {//In case of fail
                        $errorMessage=PayJpClient::getErrorMessageFromCode($payJpCustomerId['error'], $this->eccubeConfig['locale']);
                        return ['error' => true, 'message' => $errorMessage];
                    } else {
                        if(!$PayJpCustomer) {
                            $PayJpCustomer = new PayJpCustomer();
                            $PayJpCustomer->setCustomer($Customer);
                        }
                        $PayJpCustomer->setPayJpCustomerId($payJpCustomerId);
                        $PayJpCustomer->setIsSaveCardOn($isSaveCardOn);
                        $PayJpCustomer->setCreatedAt(new \DateTime());
                        $this->entityManager->persist($PayJpCustomer);
                        $this->entityManager->flush($PayJpCustomer);
                    }
                }
                //EOC check if is PayJpCustomer then update else create one
                return $payJpCustomerId;
            }
        }
        //Create temp customer
        $payJpCustomerId=$payJpClient->createCustomer($Order->getEmail(),$Order->getId());
        if (is_array($payJpCustomerId) && isset($payJpCustomerId['error'])) {//In case of fail
            $errorMessage=PayJpClient::getErrorMessageFromCode($payJpCustomerId['error'], $this->eccubeConfig['locale']);
            return ['error' => true, 'message' => $errorMessage];
        }
        return $payJpCustomerId;
    }

    private function genPaymentResponse($paymentResult) {
        if($paymentResult instanceof Charge ) {
            log_info("genPaymentResponse: " . $paymentResult->captured);
            $captured = $paymentResult->captured;
            if ($captured) {
                return [
                    'captured' => true,
                    'id' => $paymentResult->id,
                ];
            } else {
                return [
                    'captured' => false,
                    'id' => $paymentResult->id,
                ];
            }
        }
        if(isset($paymentResult->error)) {
            $errorMessage=PayJpClient::getErrorMessageFromCode($paymentResult->error, $this->eccubeConfig['locale']);
        } else {
            $errorMessage = trans('pay_jp.front.unexpected_error');
        }
        return ['error' => true, 'message' => $errorMessage];
    }

    private function getOrder(){
        // BOC validation checking
        $preOrderId = $this->cartService->getPreOrderId();
        /** @var Order $Order */
        return $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
    }
    /**
     * PaymentMethodをコンテナから取得する.
     * 
     * @param Order $Order
     * @param FormInterface $form
     * 
     * @return PaymentMethodInterface
     */
    private function createPaymentMethod(Order $Order){
        $PaymentMethod = $this->container->get($Order->getPayment()->getMethodClass());
        $PaymentMethod->setOrder($Order);

        return $PaymentMethod;
    }

    private function checkSaveCardOn($Customer, $PayJpConfig){
        $isPayJpCustomer = false;
        $PayJpCustomer = $this->entityManager->getRepository(PayJpCustomer::class)->findOneBy(array('Customer'=>$Customer));
        $payJpClient = new PayJpClient($PayJpConfig->api_key_secret);
        if($PayJpCustomer instanceof PayJpCustomer){
            $payJpLibCustomer = $payJpClient->retrieveCustomer($PayJpCustomer->getPayJpCustomerId());
            if(is_array($payJpLibCustomer) || isset($payJpLibCustomer['error'])) {
                if(isset($payJpLibCustomer['error']['code']) && $payJpLibCustomer['error']['code'] == 'resource_missing') {
                    $isPayJpCustomer = false;
                }
            } else {
                $isPayJpCustomer=true;
            }
        }
        if(!$isPayJpCustomer) return false;
        if($PayJpCustomer->getIsSaveCardOn()){
            $payJpPaymentMethodObj = $payJpClient->retrieveLastPaymentMethodByCustomer($PayJpCustomer->getPayJpCustomerId());
            
            if( !($payJpPaymentMethodObj instanceof Card) || !$payJpClient->isPaymentMethodId($payJpPaymentMethodObj->id) ) {
                return false;
            } else {
                return $payJpPaymentMethodObj;
            }
        } else {
            return false;
        }
    }
}
