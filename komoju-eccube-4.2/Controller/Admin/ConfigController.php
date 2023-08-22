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


namespace Plugin\komoju\Controller\Admin;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\komoju\Repository\KomojuConfigRepository;
use Plugin\komoju\Form\Type\LicenseInputType;
use Plugin\komoju\Form\Type\KomojuConfigType;
use Plugin\komoju\Entity\LicenseKey;

class ConfigController extends AbstractController
{
    protected $container;
    protected $config_service;
    protected $komoju_config_repo;

    public function __construct(ContainerInterface $container, KomojuConfigRepository $komoju_config_repo){
        $this->container = $container;
        $this->komoju_config_repo = $komoju_config_repo;
        $this->config_service = $container->get("plg_komoju.service.config");
    }
    /**
     * @Route("/%eccube_admin_route%/komoju/config", name="komoju_admin_config")
     * @Template("@komoju/admin/komoju_config.twig")
     */
    public function index(Request $request){
        $form_license = $this->createForm(LicenseInputType::class);
        $form_license->handleRequest($request);
        $config_data = $this->config_service->getConfigData();

        if($form_license->isSubmitted() && $form_license->isValid()){
            $key = $form_license->getData();
            $key->setInstance(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'));
            $result = $this->config_service->requestLicense($key);
            if($result){
                $key->setKeyType(LicenseKey::KEY_TYPE_REAL);
                $this->config_service->saveKey($key);
                $this->addSuccess("komoju_multipay.admin.license.success", "admin");
                $this->config_service->enablePlugin();
                $form = $this->createForm(KomojuConfigType::class, $config_data);
                return [
                    'form'          =>  $form->createView(),
                    'license_mode'  =>  'real',
                ];
            } else {
                $this->addError('komoju_multipay.admin.license.failed', 'admin');
                return [
                    'form_license'  =>  $form_license->createView(),
                    'license_mode'  =>  'unauthed'
                ];
            }
        }
        $license_res = $this->config_service->checkLicense();
        $form = $this->createForm(KomojuConfigType::class, $config_data);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            if($license_res === "unauthed"){
                $this->addError("komoju_multipay.admin.license.failed", "admin");
                return [
                    'form_license'  =>  $form_license->createView(),
                    'license_mode'  =>  'unauthed'
                ];
            }
            $config_data = $form->getData();
            if($license_res === "test"){
                $pub_key = $config_data['publishable_key'];
                $sec_key = $config_data['secret_key'];
                if (substr($pub_key, 0, 7) != 'pk_test' || substr($sec_key, 0, 7) != 'sk_test') {
                    $this->addError("komoju_multipay.admin.license.error.not_test_key", "admin");
                    return [
                        'form'  =>  $form->createView(),
                        'license_mode'  =>  'test'
                    ];
                }
            }
            $this->config_service->saveConfig($config_data);
        }

        if($license_res === "unauthed"){
            return [
                'form_license'  =>  $form_license->createView(),
                'license_mode'  =>  'unauthed',
            ];
        }
        $this->config_service->enablePlugin();

        if($license_res === "test"){
            return [
                'form'  =>  $form->createView(),
                'license_mode'  =>  "test",
            ];
        }
        if($license_res === "real"){
            return [
                'form'  =>  $form->createView(),
                'license_mode'  =>  'real',
            ];
        }
    }
    /**
     * @Route("/%eccube_admin_route%/komoju/config/test", name="komoju_admin_test_config")
     * @Template("@komoju/admin/komoju_config.twig")
     */
    public function testEnvSetting() {
        $this->config_service->setTestMode();
        return $this->redirectToRoute('komoju_admin_config');
    }
    private function getErrorMessages(\Symfony\Component\Form\Form $form) {
        $errors = array();

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }
}