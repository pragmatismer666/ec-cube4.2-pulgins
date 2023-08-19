<?php

namespace Plugin\PointsOnReferral\Tests\Event;

use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Repository\CustomerRepository;
use Eccube\Tests\EccubeTestCase;
use Plugin\PointsOnReferral\Entity\Config;
use Plugin\PointsOnReferral\Entity\History;
use Plugin\PointsOnReferral\Entity\Referral;
use Plugin\PointsOnReferral\Repository\ConfigRepository;
use Plugin\PointsOnReferral\Repository\HistoryRepository;
use Plugin\PointsOnReferral\Repository\ReferralRepository;
use Plugin\PointsOnReferral\Service\ReferralService;

class OnFrontEntryCompleteAndActivateTest extends EccubeTestCase {

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ReferralRepository
     */
    protected $referralRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var HistoryRepository
     */
    protected $historyRepository;

    /**
     * @var ReferralService
     */
    protected $referralService;

    public function setUp() {
        parent::setUp();
        $this->customerRepository = $this->entityManager->getRepository(Customer::class);
        $this->referralRepository = $this->entityManager->getRepository(Referral::class);
        $this->configRepository = $this->entityManager->getRepository(Config::class);
        $this->historyRepository = $this->entityManager->getRepository(History::class);
        $this->referralService = new ReferralService();
    }

    public function testReferral() {
        $Referrer = $this->createCustomer();
        $ReferrerRef = $this->referralRepository->findOrCreateByCustomer($Referrer);
        $RefereeRef = $this->createRefereeRef($Referrer, $ReferrerRef);
        $this->expected = $Referrer->getId();
        $this->actual = $RefereeRef->getReferrerId();
        $this->verify("should be referred by the customer created earlier");
    }

    public function testRewards() {
        $Config = $this->configRepository->getConfig();
        $Config->setReferrerRewardsEnabled(true)
            ->setRefereeRewardsEnabled(true)
            ->setReferrerRewards(500)
            ->setRefereeRewards(2000);
        $this->configRepository->save($Config);
        $this->entityManager->flush();
        $Referrer = $this->createCustomer();
        $referrerOldPoint = $Referrer->getPoint();
        $ReferrerRef = $this->referralRepository->findOrCreateByCustomer($Referrer);
        $RefereeRef = $this->createRefereeRef($Referrer, $ReferrerRef);
        $Referee = $RefereeRef->getCustomer();
        $refereeOldPoint = $Referee->getPoint();
        $this->activateCustomer($Referee);
        $History = $this->historyRepository->findOneBy([], ['id' => 'DESC']);
        $this->assertEquals($Config->getReferrerRewards(), $History->getReferrerRewards(), "should store referrer rewards");
        $this->assertEquals($Config->getRefereeRewards(), $History->getRefereeRewards(), "should store referee rewards");
        $this->entityManager->detach($Referrer);
        $this->entityManager->detach($Referee);
        $Referrer = $this->customerRepository->find($Referrer->getId());
        $Referee = $this->customerRepository->find($Referee->getId());
        $this->assertEquals($referrerOldPoint + $Config->getReferrerRewards(), $Referrer->getPoint(), "should increase referrer point");
        $this->assertEquals($refereeOldPoint + $Config->getRefereeRewards(), $Referee->getPoint(), "should increase referee point");
    }

    public function testReferrerOnlyRewards() {
        $Config = $this->configRepository->getConfig();
        $Config->setReferrerRewardsEnabled(true)
            ->setRefereeRewardsEnabled(false)
            ->setReferrerRewards(1500)
            ->setRefereeRewards(500);
        $this->configRepository->save($Config);
        $this->entityManager->flush();
        $Referrer = $this->createCustomer();
        $referrerOldPoint = $Referrer->getPoint();
        $ReferrerRef = $this->referralRepository->findOrCreateByCustomer($Referrer);
        $RefereeRef = $this->createRefereeRef($Referrer, $ReferrerRef);
        $Referee = $RefereeRef->getCustomer();
        $refereeOldPoint = $Referee->getPoint();
        $this->activateCustomer($Referee);
        $History = $this->historyRepository->findOneBy([], ['id' => 'DESC']);
        $this->assertEquals($Config->getReferrerRewards(), $History->getReferrerRewards(), "should store referrer rewards");
        $this->assertEquals(0, $History->getRefereeRewards(), "should be zero when referee rewards is disabled");
        $this->entityManager->detach($Referrer);
        $this->entityManager->detach($Referee);
        $Referrer = $this->customerRepository->find($Referrer->getId());
        $Referee = $this->customerRepository->find($Referee->getId());
        $this->assertEquals($referrerOldPoint + $Config->getReferrerRewards(), $Referrer->getPoint(), "should increase referrer point");
        $this->assertEquals($refereeOldPoint, $Referee->getPoint(), "should not increase referee point when referee rewards is disabled");
    }

    public function testRefereeOnlyRewards() {
        $Config = $this->configRepository->getConfig();
        $Config->setReferrerRewardsEnabled(false)
            ->setRefereeRewardsEnabled(true)
            ->setReferrerRewards(2400)
            ->setRefereeRewards(1800);
        $this->configRepository->save($Config);
        $this->entityManager->flush();
        $Referrer = $this->createCustomer();
        $referrerOldPoint = $Referrer->getPoint();
        $ReferrerRef = $this->referralRepository->findOrCreateByCustomer($Referrer);
        $RefereeRef = $this->createRefereeRef($Referrer, $ReferrerRef);
        $Referee = $RefereeRef->getCustomer();
        $refereeOldPoint = $Referee->getPoint();
        $this->activateCustomer($Referee);
        $History = $this->historyRepository->findOneBy([], ['id' => 'DESC']);
        $this->assertEquals(0, $History->getReferrerRewards(), "should be zero when referrer rewards is disabled");
        $this->assertEquals($Config->getRefereeRewards(), $History->getRefereeRewards(), "should store referee rewards");
        $this->entityManager->detach($Referrer);
        $this->entityManager->detach($Referee);
        $Referrer = $this->customerRepository->find($Referrer->getId());
        $Referee = $this->customerRepository->find($Referee->getId());
        $this->assertEquals($referrerOldPoint, $Referrer->getPoint(), "should not increase referrer point when referrer rewards is disabled");
        $this->assertEquals($refereeOldPoint + $Config->getRefereeRewards(), $Referee->getPoint(), "should increase referee rewards");
    }

    public function testDisabledRewards() {
        $Config = $this->configRepository->getConfig();
        $Config->setReferrerRewardsEnabled(false)
            ->setRefereeRewardsEnabled(false)
            ->setReferrerRewards(1400)
            ->setRefereeRewards(700);
        $this->configRepository->save($Config);
        $this->entityManager->flush();
        $Referrer = $this->createCustomer();
        $referrerOldPoint = $Referrer->getPoint();
        $ReferrerRef = $this->referralRepository->findOrCreateByCustomer($Referrer);
        $RefereeRef = $this->createRefereeRef($Referrer, $ReferrerRef);
        $Referee = $RefereeRef->getCustomer();
        $refereeOldPoint = $Referee->getPoint();
        $this->activateCustomer($Referee);
        $History = $this->historyRepository->findOneBy([], ['id' => 'DESC']);
        $this->assertEquals(0, $History->getReferrerRewards(), "should be zero when referrer rewards is disabled");
        $this->assertEquals(0, $History->getRefereeRewards(), "should be zero when referee rewards is disabled");
        $this->entityManager->detach($Referrer);
        $this->entityManager->detach($Referee);
        $Referrer = $this->customerRepository->find($Referrer->getId());
        $Referee = $this->customerRepository->find($Referee->getId());
        $this->assertEquals($referrerOldPoint, $Referrer->getPoint(), "should not increase referrer point when referrer rewards is disabled");
        $this->assertEquals($refereeOldPoint, $Referee->getPoint(), "should not increase referee point when referrer rewards is disabled");
    }

    /**
     * @param Customer $Referrer
     * @param Referral $ReferrerRef
     * @return Referral
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function createRefereeRef(Customer $Referrer, Referral $ReferrerRef) {
        $this->client->request(
            'GET',
            $this->generateUrl('entry', [
                $this->referralService::QUERY_KEY => $ReferrerRef->getReferralCode()
            ])
        );
        $userData = $this->createFormData();
        $this->client->request(
            'POST',
            $this->generateUrl('entry'),
            [
                'mode' => 'complete',
                'entry' => $userData
            ]
        );
        $this->assertEmpty($this->referralService->retreiveSessionReferralCode(
            $this->client->getRequest()->getSession()
        ), "should remove referral code stored in session");
        $Referee = $this->customerRepository->findOneBy([
            'email' => $userData['email']['first']
        ]);
        $RefereeRef = $this->referralRepository->findOrCreateByCustomer($Referee);
        return $RefereeRef;
    }

    protected function activateCustomer(Customer $Customer) {
        $this->client->request(
            'GET',
            $this->generateUrl('entry_activate', [
                'secret_key' => $Customer->getSecretKey()
            ])
        );
    }

    protected function createFormData() {
        $faker = $this->getFaker();
        $email = $faker->safeEmail;
        $password = $faker->lexify('????????');
        $birth = $faker->dateTimeBetween;

        $form = [
            'name' => [
                'name01' => $faker->lastName,
                'name02' => $faker->firstName,
            ],
            'kana' => [
                'kana01' => $faker->lastKanaName,
                'kana02' => $faker->firstKanaName,
            ],
            'company_name' => $faker->company,
            'postal_code' => $faker->postcode,
            'address' => [
                'pref' => '5',
                'addr01' => $faker->city,
                'addr02' => $faker->streetAddress,
            ],
            'phone_number' => $faker->phoneNumber,
            'email' => [
                'first' => $email,
                'second' => $email,
            ],
            'password' => [
                'first' => $password,
                'second' => $password,
            ],
            'birth' => [
                'year' => $birth->format('Y'),
                'month' => $birth->format('n'),
                'day' => $birth->format('j'),
            ],
            'sex' => 1,
            'job' => 1,
            'user_policy_check' => 1,
            Constant::TOKEN_NAME => 'dummy',
        ];

        return $form;
    }

}
