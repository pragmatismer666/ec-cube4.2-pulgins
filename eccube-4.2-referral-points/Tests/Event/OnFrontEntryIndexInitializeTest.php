<?php

namespace Plugin\PointsOnReferral\Tests\Event;

use Eccube\Tests\EccubeTestCase;
use Plugin\PointsOnReferral\Entity\Referral;
use Plugin\PointsOnReferral\Repository\ReferralRepository;
use Plugin\PointsOnReferral\Service\ReferralService;

class OnFrontEntryIndexInitializeTest extends EccubeTestCase {

    /**
     * @var ReferralRepository
     */
    protected $referralRepository;

    /**
     * @var ReferralService
     */
    protected $referralService;

    public function setUp() {
        parent::setUp();
        $this->referralRepository = $this->entityManager->getRepository(Referral::class);
        $this->referralService = new ReferralService();
    }

    public function test() {
        $Referrer = $this->createCustomer();
        $Referrer = $this->referralRepository->findOrCreateByCustomer($Referrer);
        $this->client->request(
            'GET',
            $this->generateUrl('entry', [
                $this->referralService::QUERY_KEY => $Referrer->getReferralCode()
            ])
        );
        $this->expected = $Referrer->getReferralCode();
        $this->actual = $this->referralService->retreiveSessionReferralCode($this->client->getRequest()->getSession());
        $this->verify("should store referral code into session");
    }

}
