<?php

namespace Plugin\PointsOnFirstOrder\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PointsOnFirstOrder\Form\Type\Admin\ConfigType;
use Plugin\PointsOnFirstOrder\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/%eccube_admin_route%/points_on_first_order/config", name="points_on_first_order_admin_config")
     * @Template("@PointsOnFirstOrder/admin/config.twig")
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
            $this->addSuccess('points_on_first_order.admin.save.complete', 'admin');
            return $this->redirectToRoute('points_on_first_order_admin_config');
        }

        return [
            'form' => $form->createView()
        ];
    }
}
