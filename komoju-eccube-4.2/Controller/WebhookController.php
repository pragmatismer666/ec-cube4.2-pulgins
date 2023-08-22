<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Plugin\komoju\Controller;
include_once dirname(__FILE__) . '/../Resource/komoju_lib/init.php';

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\komoju\Repository\KomojuConfigRepository;
use Plugin\komoju\Form\Type\KomojuConfigType;
use Komoju\WebhookEvent;


class WebhookController extends AbstractController
{
    protected $container;
    protected $log_service;
    protected $config_service;
    protected $webhook_service;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
        $this->log_service = $this->container->get("plg_komoju.service.komoju_log");
        $this->config_service = $this->container->get("plg_komoju.service.config");        
        $this->webhook_service = $this->container->get("plg_komoju.service.komoju_webhook");
    }

    /**
     * @Route("/plugin/komoju/webhook", name="komoju_webhook")    
     */
    public function webhook(Request $request){
        log_info("===========webhook is called=======");
        try{
            log_info("content" . $request->getContent());
            log_info("sig_header" . $request->headers->get('X-Komoju-Signature'));

            $config_data = $this->config_service->getConfigData();
            $webhook_secret = $config_data['webhook_secret'];
            $data = WebhookEvent::constructEvent(
                $request->getContent(),
                $request->headers->get('X-Komoju-Signature'),
                $webhook_secret);
        }catch(Exception $ex){
            $this->log_service->writeLog("webhook", "", "content: ". $request->getContent() . " exception: " . $ex->getMessage());
        }
        $type = $data->type;
        log_info("type : $type");
        log_info("object : ");
        log_info(\json_encode($data));

        $this->log_service->writeLog("webhook[$type]", "", \json_encode($data));
        switch($type){
            case "payment.refunded":
                $this->webhook_service->paymentRefunded($data);
                break;
            case "payment.captured":
                $this->webhook_service->paymentCaptured($data);
            break;
            case "payment.expired":
                $this->webhook_service->paymentExpired($data);
            break;
            case "payment.failed":
                $this->webhook_service->paymentFailed($data);
            break;
            case "payment.cancelled":
                $this->webhook_service->paymentCanceled($data);
            break;
            case "payment.updated":
                $this->webhook_service->paymentUpdated($data);
            break;

        }
        return $this->json(['status'    =>  'success']);
    }
}