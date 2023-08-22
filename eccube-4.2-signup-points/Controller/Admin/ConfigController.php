<?php
/*
* Plugin Name : PointsOnSignUp
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Plugin\PointsOnSignUp\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PointsOnSignUp\Form\Type\Admin\ConfigType;
use Plugin\PointsOnSignUp\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }
    /**
     * @Route("/%eccube_admin_route%/points_on_sign_up/admin_config", name="points_on_sign_up_admin_config")
     * @Template("@PointsOnSignUp/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('points_on_sign_up.admin.save.complete', 'admin');
            return $this->redirectToRoute('points_on_sign_up_admin_config');
        }

        return [
            'form' => $form->createView()
        ];
    }
}
