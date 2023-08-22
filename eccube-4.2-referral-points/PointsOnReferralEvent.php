<?php

namespace Plugin\PointsOnReferral;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\CustomerRepository;
use Plugin\PointsOnReferral\Repository\ConfigRepository;
use Plugin\PointsOnReferral\Repository\HistoryRepository;
use Plugin\PointsOnReferral\Repository\ReferralRepository;
use Plugin\PointsOnReferral\Service\ReferralService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointsOnReferralEvent implements EventSubscriberInterface {

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var ReferralRepository
     */
    protected $referralRepository;

    /**
     * @var HistoryRepository
     */
    protected $historyRepository;

    /**
     * @var ReferralService
     */
    protected $referralService;

    /**
     * PointsOnReferralEvent constructor
     * @param ContainerInterface $container
     * @param EntityManagerInterface $entityManager
     * @param CustomerRepository $customerRepository
     * @param ConfigRepository $configRepository
     * @param ReferralRepository $referralRepository
     * @param HistoryRepository $historyRepository
     * @param ReferralService $referralService
     */
    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        CustomerRepository $customerRepository,
        ConfigRepository $configRepository,
        ReferralRepository $referralRepository,
        HistoryRepository $historyRepository,
        ReferralService $referralService
    ) {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->customerRepository = $customerRepository;
        $this->configRepository = $configRepository;
        $this->referralRepository = $referralRepository;
        $this->historyRepository = $historyRepository;
        $this->referralService = $referralService;
    }

    public static function getSubscribedEvents() {
        return [
            // hooks for entry
            'front.entry.index.initialize'                      =>  'onFrontEntryIndexInitialize',
            'front.entry.index.complete'                        =>  'onFrontEntryIndexComplete',
            'front.entry.activate.complete'                     =>  ['onFrontEntryActivateComplete', -99],
            // hooks for My page templates
            'Mypage/change.twig'                                =>  'onRenderMyPageBefore',
            'Mypage/change_complete.twig'                       =>  'onRenderMyPageBefore',
            'Mypage/delivery.twig'                              =>  'onRenderMyPageBefore',
            'Mypage/delivery_edit.twig'                         =>  'onRenderMyPageBefore',
            'Mypage/index.twig'                                 =>  'onRenderMyPageBefore',
            'Mypage/history.twig'                               =>  'onRenderMyPageBefore',
            'Mypage/favorite.twig'                              =>  'onRenderMyPageBefore',
            'Mypage/withdraw.twig'                              =>  'onRenderMyPageBefore',
            'Mypage/withdraw_confirm.twig'                      =>  'onRenderMyPageBefore',
            '@PointsOnReferral/default/Mypage/referral.twig'    =>  'onRenderMyPageBefore'
        ];
    }

    public function onFrontEntryIndexInitialize(EventArgs $event) {
        $request = $event->getRequest();
        $referral_code = $this->referralService->getRequestReferralCode($request);
        if ($referral_code) {
            $this->referralService->storeSessionReferralCode($request->getSession(), $referral_code);
        }
    }

    public function onFrontEntryIndexComplete(EventArgs $event) {
        log_info("--- referral start ---");
        $Referee = $event->getArgument('Customer');
        $Referral = $this->referralRepository->findOrCreateByCustomer($Referee);
        log_info("referee id: " . $Referral->getCustomerId());
        $session = $event->getRequest()->getSession();
        if (!$this->referralService->hasSessionReferralCode($session)) {
            log_info("referral code empty");
            log_info("--- referral end ---");
            return;
        }
        $referral_code = $this->referralService->retreiveSessionReferralCode($session);
        log_info("referral code: " . $referral_code);
        $ReferrerReferral = $this->referralRepository->findOneBy([
            'referral_code' => $referral_code
        ]);
        if (!$ReferrerReferral) {
            log_info("referrer not found");
            log_info("--- referral end ---");
            return;
        }
        $Referrer = $this->customerRepository->find($ReferrerReferral->getCustomerId());
        if (!$Referrer) {
            log_info("referrer customer entity not found");
            log_info("--- referral end ---");
            return;
        }
        log_info("referrer id: " . $Referrer->getId());
        $Referral->setReferrerId($Referrer->getId());
        $this->referralRepository->save($Referral);
        $this->entityManager->flush();
        log_info("referral saved. id: " . $Referral->getId());
        log_info("--- referral end ---");

    }

    public function onFrontEntryActivateComplete(EventArgs $event) {
        log_info("--- referral rewards start ---");
        $Referee = $event->getArgument("Customer");
        log_info("referee id: " . $Referee->getId());
        $RefereeRef = $this->referralRepository->findOrCreateByCustomer($Referee);
        if (!$RefereeRef->getReferrerId()) {
            log_info("referrer id empty");
            log_info("--- referral rewards end ---");
            return;
        }
        $Referrer = $this->customerRepository->find($RefereeRef->getReferrerId());
        if (!$Referrer) {
            log_info("referrer not found");
            log_info("--- referral rewards end ---");
            return;
        }
        log_info("referrer id: " . $Referrer->getId());
        // save history
        $Config = $this->configRepository->getConfig();
        $History = $this->historyRepository->create($Referrer, $Referee, $Config);
        log_info("referral history id: " . $History->getId());
        log_info("referee rewards: " . $History->getRefereeRewards());
        if ($History->getRefereeRewards()) {
            log_info("referee point before rewards: " . $Referee->getPoint());
            $Referee->setPoint(floatval($Referee->getPoint()) + floatval($History->getRefereeRewards()));
            $this->customerRepository->save($Referee);
            $this->entityManager->flush();
            log_info("referee point after rewards: " . $Referee->getPoint());
        }
        log_info("referrer rewards: " . $History->getReferrerRewards());
        if ($History->getReferrerRewards()) {
            log_info("referrer point before rewards: " . $Referrer->getPoint());
            $Referrer->setPoint(floatval($Referrer->getPoint()) + floatval($History->getReferrerRewards()));
            $this->customerRepository->save($Referrer);
            $this->entityManager->flush();
            log_info("referrer point after rewards: " . $Referrer->getPoint());
        }
        log_info("--- referral rewards end ---");
    }

    public function onRenderMyPageBefore(TemplateEvent $event) {
        $event->addSnippet('@PointsOnReferral/default/Mypage/navi_add.twig');
    }

}
