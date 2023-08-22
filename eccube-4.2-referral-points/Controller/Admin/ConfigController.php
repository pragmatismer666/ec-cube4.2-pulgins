<?php

namespace Plugin\PointsOnReferral\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PointsOnReferral\Form\Type\Admin\ConfigType;
use Plugin\PointsOnReferral\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ConfigController extends AbstractController {

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/points_on_referral/config", name="points_on_referral_admin_config")
     * @Template("@PointsOnReferral/admin/config.twig")
     */
    public function index(Request $request) {
        $Config = $this->configRepository->getConfig();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->configRepository->save($Config);
            $this->entityManager->flush();
            $this->addSuccess('points_on_referral.admin.config.save.success', 'admin');
            return $this->redirectToRoute('points_on_referral_admin_config');
        }

        return [
            'form' => $form->createView()
        ];
    }
}
