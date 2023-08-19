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
namespace Plugin\PayJp\Repository;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\AbstractRepository;
use Plugin\PayJp\Entity\PayJpConfig;
// use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
class PayJpConfigRepository extends AbstractRepository
{

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(RegistryInterface $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct($registry, PayJpConfig::class);
    }
    public function get($id = 1)
    {
        return $this->find($id);
    }

    public function getConfigByOrder($Order) {
        $PayJpConfig = $this->get();

        $PayJpConfig = (object)[
            'public_api_key' => $PayJpConfig->getPublicApiKey(),
            'api_key_secret' => $PayJpConfig->getApiKeySecret(),
            'is_auth_and_capture_on' => $PayJpConfig->getIsAuthAndCaptureOn(),
            'payjp_fees_percent' => $PayJpConfig->getPayJpFeesPercent(),
            //'prod_detail_ga_enable' => $PayJpConfig->getProdDetailGaEnable(),
            // 'cart_ga_enable' => $PayJpConfig->getCartGaEnable(),
            // 'checkout_ga_enable' => $PayJpConfig->getCheckGaEnable(),
        ];

        $event = new EventArgs(
            [
                'Order' => $Order,
                'PayJpConfig' => $PayJpConfig
            ]
        );
        $this->eventDispatcher->dispatch($event, "PayJp/Config/Load");
        return $PayJpConfig;
    }
}