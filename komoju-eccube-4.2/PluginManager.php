<?php

namespace Plugin\komoju;

use Eccube\Entity\Payment;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\komoju\Entity\KomojuConfig;
use Plugin\komoju\Entity\KomojuPay;

class PluginManager extends AbstractPluginManager{

    /**
     * プラグインアップデート時の処理
     *
     * @param array              $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        // $this->registerPageForUpdate($container);
        try{
            $entityManager = $container->get('doctrine')->getManager();
            if(\method_exists($this, 'migration')){
                $this->migration($entityManager->getConnection(), $meta['code']);
            }
            $this->registerMethods($container);
        }catch(\Exception $e){}
    }

    /**
     * 支払方法を登録
     *
     * @param ContainerInterface $container
     */
    protected function registerMethods(ContainerInterface $container){
        $methods_arr = [
            [
                'id'        =>  1,
                'name'      =>  'credit_card'   ,
                'disp_name' =>  'クレジットカード',
                'sort_no'   =>  1,
            ],
            [
                'id'        =>  2,
                'name'      =>  'konbini'   ,
                'disp_name' =>  'コンビニ決済',
                'sort_no'   =>  2,
            ],
            [
                'id'        =>  3,
                'name'      =>  'bank_transfer'   ,
                'disp_name' =>  '銀行振込',
                'sort_no'   =>  3,
            ],
            [
                'id'        =>  4,
                'name'      =>  'pay_easy'   ,
                'disp_name' =>  'ペイジー',
                'sort_no'   =>  4,
            ],
            [
                'id'        =>  5,
                'name'      =>  'web_money'   ,
                'disp_name' =>  'ウェブマネー',
                'sort_no'   =>  5,
            ],
            [
                'id'        =>  6,
                'name'      =>  'bit_cash'   ,
                'disp_name' =>  'ビットキャッシュ',
                'sort_no'   =>  6,
            ],
            [
                'id'        =>  7,
                'name'      =>  'net_cash'   ,
                'disp_name' =>  'NET CASH',
                'sort_no'   =>  7,
            ],
            [
                'id'        =>  8,
                'name'      =>  'japan_mobile'   ,
                'disp_name' =>  'キャリア決済',
                'sort_no'   =>  8,
            ],
            [
                'id'        =>  9,
                'name'      =>  'paypay'   ,
                'disp_name' =>  'PayPay',
                'sort_no'   =>  9,
            ],
            [
                'id'        =>  10,
                'name'      =>  'linepay'   ,
                'disp_name' =>  'LINE Pay',
                'sort_no'   =>  10,
            ],
            [
                'id'        =>  11,
                'name'      =>  'merpay'   ,
                'disp_name' =>  'メルペイ',
                'sort_no'   =>  11,
            ],
//            [
//                'id'        =>  12,
//                'name'      =>  'nanaco'   ,
//                'disp_name' =>  'Nanaco',
//                'sort_no'   =>  12,
//            ],
//            [
//                'id'        =>  13,
//                'name'      =>  'dospara'   ,
//                'disp_name' =>  'Dospara',
//                'sort_no'   =>  13,
//            ],
//            [
//                'id'        =>  14,
//                'name'      =>  'steam_prepaid_card'   ,
//                'disp_name' =>  'Steam Prepaid Card',
//                'sort_no'   =>  14,
//            ],
        ];
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $method_repo = $entityManager->getRepository(KomojuPay::class);
        foreach($methods_arr as $method_data){
            $komoju_pay = $method_repo->findOneBy(['name' => $method_data['name']]);
            if($komoju_pay){
                continue;
            }
            $komoju_pay = new KomojuPay;
            $komoju_pay->setId($method_data['id']);
            $komoju_pay->setName($method_data['name']);
            $komoju_pay->setDispName($method_data['disp_name']);
            $komoju_pay->setSortNo($method_data['sort_no']);
            $komoju_pay->setEnabled(true);
            $entityManager->persist($komoju_pay);
            $entityManager->flush();
        }
    }

    public function enable(array $meta, ContainerInterface $container){
        $this->createConfig($container);
        $this->registerMethods($container);
    }

    private function createConfig(ContainerInterface $container){
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $config = $entityManager->find(KomojuConfig::class, 1);
        if($config){
            return;
        }
        $config = new KomojuConfig();
        $config->setPublishableKey('パブリックAPIキー');
        $config->setSecretKey('秘密キー');
        $config->setMerchantUuid("クライアント UUID");
        $config->setWebhookSecret(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'));

        $entityManager->persist($config);
        $entityManager->flush();
    }
}