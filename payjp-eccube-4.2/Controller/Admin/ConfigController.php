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


namespace Plugin\PayJp\Controller\Admin;

use Plugin\PayJp\Entity\LicenseKey;
use Eccube\Controller\AbstractController;
use Plugin\PayJp\Entity\PayJp3dConfig;
use Plugin\PayJp\Form\Type\Admin\PayJpConfigType;
use Plugin\PayJp\Form\Type\Admin\LicenseInputType;
use Plugin\PayJp\Form\Type\Admin\PayJp3dConfigType;
use Plugin\PayJp\Repository\PayJpConfigRepository;
use Plugin\PayJp\Repository\LicenseRepository;
use Plugin\PayJp\Repository\PayJp3dConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigController extends AbstractController
{
    /**
     * @var PayJpConfigRepository
     */
    protected $payJpConfigRepository;

    /**
     * @var LicenseRepository
     */
    protected $licenseRepository;

    protected $payJp3dConfigRepository;

    /**
     * ConfigController constructor.
     *
     * @param PayJpConfigRepository $payJpConfigRepository
     * @param LicenseRepository $licenseRepository
     */
    public function __construct(PayJpConfigRepository $payJpConfigRepository, LicenseRepository $licenseRepository, PayJp3dConfigRepository $payJp3dConfigRepository)
    {
        $this->payJpConfigRepository = $payJpConfigRepository;
        $this->licenseRepository = $licenseRepository;
        $this->payJp3dConfigRepository = $payJp3dConfigRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/pay_jp/config", defaults={"licensed = 0", "product_licensed = 0"}, name="pay_jp_admin_config")
     * @Template("@PayJp/admin/pay_jp_config.twig")
     */
    public function index(Request $request)
    {
        $form_license = $this->createForm(LicenseInputType::class);
        $form_license->handleRequest($request);

        $PayJpConfig = $this->payJpConfigRepository->get();
        $form = $this->createForm(PayJpConfigType::class, $PayJpConfig);
        $form->handleRequest($request);

        if ($form_license->isSubmitted() && $form_license->isValid()) {           
            $email = $form_license['email']->getData();
            $license_key = $form_license['license_key']->getData();
            $rand_string = '0123456789abcdefghijklmnopqrstuvwxyz';
            $instance = str_shuffle($rand_string);
            $result = $this->isLicensed($email, $license_key, $instance);
            if ($result === true){           
                $array = array(
                    'email' => $email, 
                    'license_key' => $license_key,
                    'key_type' => 2,
                    'instance' => $instance,
                );
                $this->saveLicenseKey($array);
                $this->addSuccess('pay_jp.admin.license.success', 'admin');
                return [
                    'form' => $form->createView(),
                    'form_license' => $form_license->createView(),
                    'licensed' => 1,
                    'product_licensed' => 2,
                ];
            } else {    
                $this->addError('pay_jp.admin.license.failed', 'admin');
                return [
                    'form_license' => $form_license->createView(),
                    'licensed' => 0,
                ];
            }       
        }

        // if ($form->isSubmitted() && $form->isValid()) {
        //     $PayJpConfig = $form->getData();
        //     $this->entityManager->persist($PayJpConfig);
        //     $this->entityManager->flush($PayJpConfig);

        //     $this->addSuccess('pay_jp.admin.save.success', 'admin');

        //     return $this->redirectToRoute('pay_jp_admin_config');
        // }

        // return [
        //     'form' => $form->createView(),
        // ];

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isLicenseKeyExist()) {
                $license_key = $this->licenseRepository->get()->getLicenseKey();
                $email = $this->licenseRepository->get()->getEmail();
                $key_type = $this->licenseRepository->get()->getKeyType();
                $instance = $this->licenseRepository->get()->getInstance();
                if ($key_type == 1) {
                    $publishable_key = $form['public_api_key']->getData();
                    $secret_key = $form['api_key_secret']->getData();
                    $PayJpConfig = $form->getData();
                    if (substr($publishable_key, 0, 7) == 'pk_test' && substr($secret_key, 0, 7) == 'sk_test') {
                        $this->entityManager->persist($PayJpConfig);
                        $this->entityManager->flush($PayJpConfig);
                        $this->addSuccess('登録しました。', 'admin');
                    } else {
                        $this->addError('pay_jp.admin.license.payjp.testkey', 'admin');
                    }
                    return [
                        'form' => $form->createView(),
                        'licensed' => 1,
                        'product_licensed' => 1,
                    ];
                } else if ($key_type == 2) {
                    $result = $this->isLicensed($email, $license_key, $instance);
                    if ($result === true ) {  
                        //$publishable_key = $form['public_api_key']->getData();
                        //$secret_key = $form['api_key_secret']->getData();
                        $PayJpConfig = $form->getData();
                        $this->entityManager->persist($PayJpConfig);
                        $this->entityManager->flush($PayJpConfig);   
                        $this->addSuccess('pay_jp.admin.save.success', 'admin');
                        return $this->redirectToRoute('pay_jp_admin_config');
                    }else {
                        $this->addError('pay_jp.admin.license.failed', 'admin');
                        return [
                            'form_license' => $form_license->createView(),
                            'licensed' => 0,
                        ];
                    }    
                } else {
                    $this->addError('pay_jp.admin.license.notyet','admin');
                    return [
                        //'form' => $form->createView(),
                        'form_license' => $form_license->createView(),
                        'licensed' => 0,
                    ];
                }
                     
            } else {
                $this->addError('pay_jp.admin.license.notyet','admin');
                return [
                    //'form' => $form->createView(),
                    'form_license' => $form_license->createView(),
                    'licensed' => 0,
                ];
            }    
        }

        if ($this->isLicenseKeyExist()) {        
            $license_key = $this->licenseRepository->get()->getLicenseKey();
            $email = $this->licenseRepository->get()->getEmail();
            $key_type = $this->licenseRepository->get()->getKeyType();
            $instance = $this->licenseRepository->get()->getInstance();
            if ($key_type == 1) {
                return [
                    'form' => $form->createView(),
                    'licensed' => 1,
                    'product_licensed' => 1,
                ];
            } else if ($key_type == 2) {
                $result = $this->isLicensed($email, $license_key, $instance);
                if ($result === true ) {
                    return [
                        'form' => $form->createView(),
                        'licensed' => 1,
                        'product_licensed' => 2,
                    ];
                } else {
                    $this->addError('pay_jp.admin.license.failed', 'admin');
                    return [
                        'form' => $form->createView(),
                        'form_license' => $form_license->createView(),
                        'licensed' => 0,
                    ];
                }
            } else {
                return [
                    'form' => $form->createView(),
                    'form_license' => $form_license->createView(),
                    'licensed' => 0,
                ];
            }                              
        }         
        else {
            //$this->addError('pay_jp.admin.license.notyet','admin');
            return [
                //'form' => $form->createView(),
                'form_license' => $form_license->createView(),
                'licensed' => 0,
            ];
        } 
    }

    /**
     * @Route("/%eccube_admin_route%/pay_jp/3d_config", name="pay_jp_admin_3d_config")
     * @Template("@PayJp/admin/pay_jp_3d_config.twig")
     */
    public function tdConfig(Request $request)
    {
        $tdConfig = $this->payJp3dConfigRepository->get();
        if (!$tdConfig)
        {
            $tdConfig = new PayJp3dConfig;
            $tdConfig->setId(1);
            $this->entityManager->persist($tdConfig);
            $this->entityManager->flush();
        }
        $form = $this->createForm(PayJp3dConfigType::class, $tdConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $this->entityManager->persist($tdConfig);
            $this->entityManager->flush();
        }
        return [
            'form' => $form->createView()
        ];
    }

    public function isLicensed($email, $license_key, $instance){
        //$host = "http://wordpress-69479-1385070.cloudwaysapps.com/?wc-api=software-api&";
        $host = "https://subspire.co.jp/?wc-api=software-api&";
        $url = $host
                .'request=activation'
                .'&email='.$email
                .'&license_key='.$license_key
                .'&product_id=payjp_eccube4'
                .'&instance='.$instance;
        $content = json_decode(file_get_contents($url));
        if ($content->activated === true) {     
            return true;                    
        }
        return false;
    }
    public function saveLicenseKey($array){        
        $lic_config = $this->entityManager->getRepository(LicenseKey::class)->get();
        //$lic_config = $this->licenseRepository->get();
        if ($lic_config === null){
            $lic_config = new LicenseKey;
        }
        $lic_config->setEmail($array['email']);
        $lic_config->setLicenseKey($array['license_key']);
        $lic_config->setKeyType($array['key_type']);
        $lic_config->setInstance($array['instance']);
        $this->entityManager->persist($lic_config);
        $this->entityManager->flush($lic_config);
    }
    public function isLicenseKeyExist() {   
        if ($this->licenseRepository->get() !== null) {
           
            return true;
        }
        return false; 
    }
    public function readLicenseKey() {
        $content = $this->licenseRepository->get();
        return $content;
    }

    /**
     * @Route("/%eccube_admin_route%/pay_jp/config/test", name="pay_jp_admin_test_config")
     * @Template("@PayJp/admin/pay_jp_config.twig")
     */
    public function testEnvSetting() {
        $test_key = array(
            'email' => 'testemail@email.com',
            'license_key' => 'test_key',
            'key_type' => 1,
            'instance' => 111,
        ); 
        $this->saveLicenseKey($test_key);
        return $this->redirectToRoute('pay_jp_admin_config');
    }
}