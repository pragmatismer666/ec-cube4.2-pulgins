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

use Eccube\Entity\Payment;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\PayJp\Entity\PayJpConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\PayJp\Service\Method\PayJpCreditCard;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createTokenPayment($container);
        $this->createConfig($container);
    }

    private function createTokenPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;
        $Payment = $paymentRepository->findOneBy(['method_class' => PayJpCreditCard::class]);
        if ($Payment) {
            return;
        }
        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('クレジットカード');
        $Payment->setMethodClass(PayJpCreditCard::class);
        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    private function createConfig(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Config = $entityManager->find(PayJpConfig::class, 1);
        if ($Config) {
            return;
        }
        $Config = new PayJpConfig();
        $Config->setPublicApiKey('パブリックAPIキー');
        $Config->setApiKeySecret('秘密キー');
        $Config->setPayJpFeesPercent('0');
        $entityManager->persist($Config);
        $entityManager->flush($Config);
    }

    public function install(array $meta, ContainerInterface $container){
        $this->createConfig($container);
        try{
            $this->noWarmUpRouteCache($container);
        }catch(\Exception $e){
            
        }
    }
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
            $this->noWarmUpRouteCache($container);
            $entityManager = $container->get('doctrine')->getManager();
            
            if(\method_exists($this, 'migration')){
                $this->migration($entityManager->getConnection(), $meta['code']);
            }
        }catch(\Exception $e){

        }
    }

    protected function noWarmUpRouteCache($container) {
        $router = $container->get('router');
        $filesystem = $container->get('filesystem');
        $kernel = $container->get('kernel');
        $cacheDir = $kernel->getCacheDir();
        
        foreach (array('matcher_cache_class', 'generator_cache_class') as $option) {

            $className = $router->getOption($option);

            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $className . '.php';            

            $filesystem->remove($cacheFile);
        }
        
        // $router->warmUp($cacheDir);    
    }
}