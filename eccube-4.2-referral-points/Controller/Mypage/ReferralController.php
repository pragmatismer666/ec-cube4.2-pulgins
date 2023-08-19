<?php

namespace Plugin\PointsOnReferral\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Event\EventArgs;
use Plugin\PointsOnReferral\Event\PointsOnReferralEvents;
use Plugin\PointsOnReferral\Service\ReferralService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Knp\Component\Pager\PaginatorInterface as Paginator;
use Plugin\PointsOnReferral\Repository\HistoryRepository;
use Plugin\PointsOnReferral\Repository\ReferralRepository;

class ReferralController extends AbstractController {

    /**
     * @var ReferralRepository
     */
    private $referralRepository;

    /**
     * @var HistoryRepository $historyRepository
     */
    private $historyRepository;

    public function __construct(
        ReferralRepository $referralRepository,
        HistoryRepository $historyRepository
    ) {
        $this->referralRepository = $referralRepository;
        $this->historyRepository = $historyRepository;
    }

    /**
     * Mypage referral
     *
     * @Route("/mypage/referral", name="mypage_referral")
     * @Template("@PointsOnReferral/default/Mypage/referral.twig")
     */
    public function index(Request $request, Paginator $paginator) {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('mypage_login');
        }
        $Customer = $this->getUser();
        if (!$Customer) {
            return $this->redirectToRoute('mypage_login');
        }
        $Referral = $this->referralRepository->findOrCreateByCustomer($Customer);

        $event = new EventArgs([
           'Customer' => $Customer,
           'Referral' => $Referral
        ], $request);
        // $this->eventDispatcher->dispatch(PointsOnReferralEvents::FRONT_MYPAGE_REFERRAL_INDEX_INITIALIZE, $event);
        $this->eventDispatcher->dispatch($event, PointsOnReferralEvents::FRONT_MYPAGE_REFERRAL_INDEX_INITIALIZE);

        $qb = $this->historyRepository->getQueryBuilderByCustomer($Customer);
        $pageno = $request->get('pageno', 1);
        $pagesize = $this->eccubeConfig['eccube_search_pmax'];
        $pagination = $paginator->paginate(
            $qb,
            $pageno,
            $pagesize,
            ['wrap-queries' => true]
        );

        $pageData = [
            'Customer' => $Customer,
            'Referral' => $Referral,
            'qb' => $qb,
            'pageno' => $pageno,
            'pagesize' => $pagesize,
            'pagination' => $pagination,
            'query_key' => ReferralService::QUERY_KEY
        ];

        $event = new EventArgs($pageData);
        // $this->eventDispatcher->dispatch(PointsOnReferralEvents::FRONT_MYPAGE_REFERRAL_INDEX_COMPLETE, $event);
        $this->eventDispatcher->dispatch($event, PointsOnReferralEvents::FRONT_MYPAGE_REFERRAL_INDEX_COMPLETE);

        return $pageData;
    }

}
