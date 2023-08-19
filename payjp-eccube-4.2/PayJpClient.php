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

$currentPluginDir=dirname(__FILE__);
$vendorDir = $currentPluginDir."/vendor";
require_once($vendorDir.'/payjp-php/init.php');

use Exception;
use Payjp\Charge;
use Payjp\Payjp;
use Payjp\Token;
use Plugin\PayJp\Service\PayJpLib\TdCharge;

class PayJpClient
{
    public $zeroDecimalCurrencies = ["BIF", "CLP", "DJF", "GNF", "JPY", "KMF", "KRW", "MGA", "PYG", "RWF", "UGX", "VND", "VUV", "XAF", "XOF", "XPF"];

    public static $errorMessages = array(
        'invalid_number' => '不正なカード番号',
        'invalid_cvc' => '不正なCVC',
        'invalid_expiry_month' => '不正な有効期限月',
        'invalid_expiry_year' => '不正な有効期限年',
        'expired_card' => '有効期限切れ',
        'card_declined' => 'カード会社によって拒否されたカード',
        'processing_error' => '決済ネットワーク上で生じたエラー',
        'missing_card' => '顧客がカードを保持していない',
        'invalid_id' => '不正なID',
        'no_api_key' => 'APIキーがセットされていない',
        'invalid_api_key' => '不正なAPIキー',
        'invalid_plan' => '不正なプラン',
        'invalid_expiry_days' => '不正な失効日数',
        'unnecessary_expiry_days' => '失効日数が不要なパラメーターである場合',
        'invalid_flexible_id' => '不正なID指定',
        'invalid_timestamp' => '不正なUnixタイムスタンプ',
        'invalid_trial_end' => '不正なトライアル終了日',
        'invalid_string_length' => '不正な文字列長',
        'invalid_country' => '不正な国名コード',
        'invalid_currency' => '不正な通貨コード',
        'invalid_address_zip' => '不正な郵便番号',
        'invalid_amount' => '不正な支払い金額',
        'invalid_plan_amount' => '不正なプラン金額',
        'invalid_card' => '不正なカード',
        'invalid_customer' => '不正な顧客',
        'invalid_boolean' => '不正な論理値',
        'invalid_email' => '不正なメールアドレス',
        'no_allowed_param' => 'パラメーターが許可されていない場合',
        'no_param' => 'パラメーターが何もセットされていない',
        'invalid_querystring' => '不正なクエリー文字列',
        'missing_param' => '必要なパラメーターがセットされていない',
        'invalid_param_key' => '指定できない不正なパラメーターがある',
        'no_payment_method' => '支払い手段がセットされていない',
        'payment_method_duplicate' => '支払い手段が重複してセットされている',
        'payment_method_duplicate_including_customer' => '支払い手段が重複してセットされている(顧客IDを含む)',
        'failed_payment' => '指定した支払いが失敗している場合',
        'invalid_refund_amount' => '不正な返金額',
        'already_refunded' => 'すでに返金済み',
        'cannot_refund_by_amount' => '返金済みの支払いに対して部分返金ができない',
        'invalid_amount_to_not_captured' => '確定されていない支払いに対して部分返金ができない',
        'refund_amount_gt_net' => '返金額が元の支払い額より大きい',
        'capture_amount_gt_net' => '支払い確定額が元の支払い額より大きい',
        'invalid_refund_reason' => '不正な返金理由',
        'already_captured' => 'すでに支払いが確定済み',
        'cant_capture_refunded_charge' => '返金済みの支払いに対して支払い確定ができない',
        'charge_expired' => '認証が失効している支払い',
        'alerady_exist_id' => 'すでに存在しているID',
        'token_already_used' => 'すでに使用済みのトークン',
        'already_have_card' => '指定した顧客がすでに保持しているカード',
        'dont_has_this_card' => '顧客が指定したカードを保持していない',
        'doesnt_have_card' => '顧客がカードを何も保持していない',
        'invalid_interval' => '不正な課金周期',
        'invalid_trial_days' => '不正なトライアル日数',
        'invalid_billing_day' => '不正な支払い実行日',
        'exist_subscribers' => '購入者が存在するプランは削除できない',
        'already_subscribed' => 'すでに定期課金済みの顧客',
        'already_canceled' => 'すでにキャンセル済みの定期課金',
        'already_pasued' => 'すでに停止済みの定期課金',
        'subscription_worked' => 'すでに稼働している定期課金',
        'test_card_on_livemode' => '本番モードのリクエストにテストカードが使用されている',
        'not_activated_account' => '本番モードが許可されていないアカウント',
        'too_many_test_request' => 'テストモードのリクエストリミットを超過している',
        'invalid_access' => '不正なアクセス',
        'payjp_wrong' => 'PAY.JPのサーバー側でエラーが発生している',
        'pg_wrong' => '決済代行会社のサーバー側でエラーが発生している',
        'not_found' => 'リクエスト先が存在しないことを示す',
        'not_allowed_method' => '許可されていないHTTPメソッド',
    );

    public function __construct($secret_key)
    {
        Payjp::setApiKey($secret_key);
    }

    public function getAmountToSentInPayJp($amount, $currency) 
    {
        if(!in_array($currency, $this->zeroDecimalCurrencies)) {
            return (int)$amount*100;
        }
        return (int)$amount;
    }
    public function retrieveCustomer($customerId) 
    {
        try {
            return \Payjp\Customer::retrieve($customerId);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    public function retrieveToken($tokenId) 
    {
        return Token::retrieve($tokenId);
    }

    public function createChargeWithCustomer($amount, $payJpCustomerId, $orderId, $capture, $expiry_days = 1) {
        $params = array(
            'amount' => $amount,
            'currency' => 'jpy',
            'customer' => $payJpCustomerId,
            'metadata' => array(
                'order' => $orderId

            ),
            'capture' => $capture,
        );
        if (! $capture) {
            $params['expiry_days'] = $expiry_days;
        }
        try {
            return Charge::create($params);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function createCustomer($customer_email,$customer_id=0,$order_id=0) {
        $params['email'] = $customer_email;
        //$params['card'] = $payJpToken;
        if($customer_id) {
            $params['metadata'] = array('customer_id' => $customer_id);
        } else if($order_id){
            $params['metadata'] = array('order_id' => $order_id);
        }

        try {
            $payJpLibCustomer=\Payjp\Customer::create($params);
            return $payJpLibCustomer->id;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function updateCustomer($payJpCustomerId, $customer_email) {
        try {
            $customerToUpdate=\Payjp\Customer::retrieve($payJpCustomerId);
            $customerToUpdate->email = $customer_email;
            // $customerToUpdate->source = $payJpToken;
            $customerToUpdate->save();
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function retrieveChargeByCustomer($payJpCustomerId) {
        try {
            return Charge::all(["customer"=>$payJpCustomerId]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function createChargeWithToken($amount, $payJpTokenId, $orderId, $capture, $expiry_days = 1) {
        $params = array(
            'amount' => $amount,
            'currency' => 'jpy',
            'card' => $payJpTokenId,
            'metadata' => array(
                'order' => $orderId
            ),
            'capture' => $capture,
        );
        if (! $capture) {
            $params['expiry_days'] = $expiry_days;
        }
        try {
            return Charge::create($params);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function tdsFinish($chargeId) {
        try {
            return TdCharge::tdsFinish($chargeId);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function retrieveCharge($chargeId) {
        try {
            return Charge::retrieve($chargeId);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function createRefund($chargeId,$refund_amount=0,$currency='JPY') {
        try {
            $ch = \Payjp\Charge::retrieve($chargeId);
            if($refund_amount>0){
                return $ch->refund(['amount' =>$this->getAmountToSentInPayJp($refund_amount,$currency)]);
            } else {
                return $ch->refund();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function getErrorMessageFromCode($error, $locale) {
        if (isset(self::$errorMessages[$error->code])) {
            $message = self::$errorMessages[$error->code];
            $out_message = $locale == 'ja' ? $message : str_replace('_', ' ', $error->code);
            return $out_message;
        }
        return PayJpEvent::getLocalizedString('unexpected_error', $locale);
    }

    public function isChargeId($chargeId) {
        if( !empty($chargeId) && substr($chargeId, 0, 3) == "ch_" ) {
            return true;
        } else {
            return false;
        }
    }
    //for 3ds2
    public function createPaymentIntentWithCustomer($amount, $cardId, $orderId, $isSaveCardOn, $isExisting, $payJpCustomerId, $currency='JPY', $tdSecure = false) {
        try {
            
            //$cardId = $this->createCard($payJpCustomerId, $cardId);
            if ($isSaveCardOn && !$isExisting){
                $cardId = $this->createCard($payJpCustomerId, $cardId);
            }
            $params = [
                'amount' => $this->getAmountToSentInPayJp($amount,$currency),
                'currency' => $currency,
                //'customer' => $payJpCustomerId,
                'card' => $cardId,
                'capture' => false,
                'metadata' => array(
                    'order' => $orderId,
                    'isSaveCardOn' => $isSaveCardOn
                ),
                'description' => '' . $orderId
            ];
            if ($isSaveCardOn){
                $params['customer'] = $payJpCustomerId;
            }
            if ($tdSecure) 
            {
                $params['three_d_secure'] = true;
            }
            //$params['customer'] = $payJpCustomerId;
            log_info("payjp: createPaymentIntentWithCustomer", $params);
            return \Payjp\Charge::create($params);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function createCard($payJpCustomerId, $tokenId){
        $cu = $this->retrieveCustomer($payJpCustomerId);
        try {
            $params = [
                'card' => $tokenId,
            ];
            return $cu->cards->create($params);
        } catch (Exception $e){
            return $e->getMessage();
        }
 
    }
    //for 3ds2
    public function retrieveLastPaymentMethodByCustomer($PayJpCustomerId) {
        try {
            $cards = \Payjp\Customer::retrieve($PayJpCustomerId)->cards->all(array(
                'limit' => 1,
                'offset' => 0,
            ));
            foreach($cards->data as $card) {
                return $card;
            }
            return false;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function isPaymentMethodId($paymentMethodId) {
        if( !empty($paymentMethodId) && substr($paymentMethodId, 0, 4) == "car_" ) {
            return true;
        } else {
            return false;
        }
    }
    public function detachMethod($method_id, $PayJpCustomer){
        try{
            $cu = \Payjp\Customer::retrieve($PayJpCustomer);
            $method = $cu->cards->retrieve($method_id);
            if($method){
                return $method->delete();
            } else {
                return false;
            }
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    public function capturePaymentIntent($paymentIntentId, $amount, $currency='JPY') {
        try {
            $amount = $this->getAmountToSentInPayJp($amount, $currency);
            // if( $paymentIntentId instanceof PaymentIntent ) {
            //     $paymentIntent = $paymentIntentId;
            // }  else {
            //     $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            // }
            $ch = \Payjp\Charge::retrieve($paymentIntentId);
            $params = array('amount' => (int)$amount);
            log_info("payJp: capturePaymentIntent", $params);
            return $ch->capture($params);
        
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function retrievePaymentIntent($paymentIntentId) {
        try {
            return \Payjp\Charge::retrieve($paymentIntentId);
        } catch (Exception $e) {
            // return $e->getJsonBody();
            log_info("PayJpClient---retrievePaymentIntent");
            log_error($e);
        }
    }
}