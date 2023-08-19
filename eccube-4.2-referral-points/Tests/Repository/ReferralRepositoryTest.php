<?php

namespace Plugin\PointsOnReferral\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Plugin\PointsOnReferral\Entity\Referral;
use Plugin\PointsOnReferral\Repository\ReferralRepository;

class ReferralRepositoryTest extends EccubeTestCase {

    /**
     * @var ReferralRepository
     */
    protected $referralRepository;

    public function setUp() {
        parent::setUp();
        $this->referralRepository = $this->entityManager->getRepository(Referral::class);
    }

    public function testGenerateReferralCode() {
        $referral_code =  $this->referralRepository->generateReferralCode();
        $this->assertNotEmpty($referral_code, "should generate a referral code");
    }

    public function testCreateFromCustomer() {
        $Customer = $this->createCustomer();
        $Referral = $this->referralRepository->createFromCustomer($Customer);
        $this->assertNotEmpty($Referral->getId(), "should persist referral into db");
        $this->assertEquals($Customer->getId(), $Referral->getCustomerId(), "should have a customer id");
        $this->assertEmpty($Referral->getReferrerId(), "should not have a referrer id");
        $this->assertNotEmpty($Referral->getReferralCode(), "should have a referral code");
    }

    public function testFindOrCreateByCustomer() {
        $Customer = $this->createCustomer();
        $Referral = $this->referralRepository->findOneBy(['customer_id' => $Customer]);
        $this->assertNull($Referral, "should return null for a new customer");
        $Referral = $this->referralRepository->createFromCustomer($Customer);
        $Persisted = $this->referralRepository->findOrCreateByCustomer($Customer);
        $this->assertEquals($Referral->getId(), $Persisted->getId(), "should return created referral");
    }

}
