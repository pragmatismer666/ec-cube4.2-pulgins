<?php

namespace Plugin\komoju\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Repository\PaymentRepository;
use Eccube\Entity\Payment;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\PaymentOption;
use Eccube\Common\EccubeConfig;
use Plugin\komoju\Entity\KomojuPay;
use Plugin\komoju\Entity\LicenseKey;
use Plugin\komoju\Entity\KomojuConfig;
use Plugin\komoju\Service\Method\KomojuMultiPay;

class ConfigService{
    protected $container;
    protected $eccubeConfig;
    protected $entityManager;

    const LIC_URL = "https://subspire.co.jp/?wc-api=software-api&";
    // const LIC_URL = "http://wordpress-69479-1385070.cloudwaysapps.com/?wc-api=software-api&";
    const PROD_ID = "komoju_eccube4";

    const MAIL_TEMPLATE_REFUND_REDIRECT = "Komoju返金メール";

    public function __construct(ContainerInterface $container, EccubeConfig $eccubeConfig){
        $this->container = $container;
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
            
    }
    
    public function enablePlugin(){
        $this->createTokenPayment();
        $this->insertMailTemplate();
    }

    public function disablePlugin(){
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => KomojuMultiPay::class]);
        if(empty($Payment)){
            return;
        }
        $Payment->setVisible(false);
        $this->entityManager->persist($Payment);
        $this->entityManager->flush();
    }

    public function requestLicense(LicenseKey $key){
        $url = self::LIC_URL
                ."request=activation"
                ."&email=".$key->getEmail()
                ."&license_key=".$key->getLicenseKey()
                ."&product_id=".self::PROD_ID
                ."&instance=".$key->getInstance();
        
        $content = json_decode(\file_get_contents($url));        
        return isset($content->activated) && $content->activated === true;        
    }
    public function checkLicense(){
        $lic_repo = $this->entityManager->getRepository(LicenseKey::class);
        $key = $lic_repo->get();
        if($key){
            if($key->getKeyType() === LicenseKey::KEY_TYPE_TEST){
                return "test";
            }
            if($this->requestLicense($key)){
                return "real";
            }            
        }
        return "unauthed";        
    }
    public function saveConfig($config_data){
        $config_repo = $this->entityManager->getRepository(KomojuConfig::class);
        $config = $config_repo->get();
        if(empty($config)){
            $config = new KomojuConfig;
        }
        $config->setPublishableKey($config_data['publishable_key']);
        $config->setSecretKey($config_data['secret_key']);
        $config->setMerchantUuid($config_data['merchant_uuid']);
        $config->setWebhookSecret($config_data['webhook_secret']);
        $config->setCaptureOn( isset($config_data['capture_on']) ? $config_data['capture_on'] : true);
        
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $komoju_pays = $config_data['komoju_pays'];
        $komoju_pay_repo = $this->entityManager->getRepository(KomojuPay::class);        
        $all_komoju_pays = $komoju_pay_repo->findBy([]);        
        foreach($all_komoju_pays as $komoju_pay){
            if($komoju_pays->contains($komoju_pay)){
                $komoju_pay->setEnabled(true);
                $this->entityManager->persist($komoju_pay);
            }else{
                $komoju_pay->setEnabled(false);
                $this->entityManager->persist($komoju_pay);
            }
            $this->entityManager->flush();
        }
        $this->entityManager->commit();
        return;
    }
    public function setTestMode(){
        $test_key = new LicenseKey;
        $test_key
            ->setEmail("testemail@email.com")
            ->setLicenseKey("test_key")
            ->setInstance(111)
            ->setKeyType(LicenseKey::KEY_TYPE_TEST);
        $this->saveKey($test_key);
    }
    public function saveKey($key){
        $key_existing = $this->entityManager->getRepository(LicenseKey::class)->get();
        if($key_existing && $key != $key_existing){
            $this->entityManager->remove($key_existing);
            $this->entityManager->flush();
        }
        $this->entityManager->persist($key);
        $this->entityManager->flush();
        $this->entityManager->commit();
    }
    public function getConfigData($Order = null){
        $komoju_config_repo = $this->entityManager->getRepository(KomojuConfig::class);    
        $config = $komoju_config_repo->getConfigByOrder($Order);        
        return $config;
    }
    // ===============for enablePlugin===========
    protected function createTokenPayment(){
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => KomojuMultiPay::class]);
        if($Payment){
            return;
        }
        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        // $Payment->setMethod('Komoju MultiPay');
        $Payment->setMethod('マルチ決済');
        $Payment->setMethodClass(KomojuMultiPay::class);
        $this->entityManager->persist($Payment);
        $this->entityManager->flush($Payment);
        $this->entityManager->commit();
    }
    private function insertMailTemplate(){
        $template_list = [
            [
                'name'      =>  self::MAIL_TEMPLATE_REFUND_REDIRECT,
                'file_name' =>  'komoju\Resource\template\mail\refund_redirect.twig',
                'mail_subject'  => 'Komoju返金メール',                
            ],            
        ];
        //TODO: file name must update

    $em = $this->container->get('doctrine.orm.entity_manager');
    foreach($template_list as $template){
        $template1 = $em->getRepository(MailTemplate::class)->findOneBy(["name" => $template["name"]]);
        if ($template1){
            continue;
        }
        $item = new MailTemplate();
        $item->setName($template["name"]);
        $item->setFileName($template["file_name"]);
        $item->setMailSubject($template["mail_subject"]);
        $em->persist($item);            
        $em->flush();
    }
    }
}