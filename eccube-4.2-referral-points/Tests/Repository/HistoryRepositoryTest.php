<?php

namespace Plugin\PointsOnReferral\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Plugin\PointsOnReferral\Entity\Config;
use Plugin\PointsOnReferral\Entity\History;
use Plugin\PointsOnReferral\Repository\ConfigRepository;
use Plugin\PointsOnReferral\Repository\HistoryRepository;

class HistoryRepositoryTest extends EccubeTestCase {
    
    /**
     * @var HistoryRepository
     */
    private $historyRepository;

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    
    public function setUp() {
        parent::setUp();
        $this->historyRepository = $this->entityManager->getRepository(History::class);
        $this->configRepository = $this->entityManager->getRepository(Config::class);
    }
    
    public function test() {
        $Referrer = $this->createCustomer();
        $Referee = $this->createCustomer();
        $Config = $this->configRepository->getConfig();
        $History = $this->historyRepository->create($Referrer, $Referee, $Config);
        
        $spec = "should store exact information";
        $this->assertEquals($Referrer->getId(), $History->getReferrerId(), $spec);
        $this->assertEquals($Referrer->getEmail(), $History->getReferrerEmail(), $spec);
        $this->assertEquals($Referrer->getName01() . " " . $Referrer->getName02(), $History->getReferrerFullName(), $spec);
        $this->assertEquals($Referrer->getKana01() . " " . $Referrer->getKana02(), $History->getReferrerFullKana(), $spec);
        $this->assertEquals($Referee->getId(), $History->getRefereeId(), $spec);
        $this->assertEquals($Referee->getEmail(), $History->getRefereeEmail(),$spec);
        $this->assertEquals($Referee->getName01() . " " . $Referee->getName02(), $History->getRefereeFullName(), $spec);
        $this->assertEquals($Referee->getKana01() . " " . $Referee->getKana02(), $History->getRefereeFullKana(), $spec);
        // when referrer reward is disabled
        $Config->setReferrerRewardsEnabled(false)
            ->setReferrerRewards(1000)
            ->setRefereeRewardsEnabled(true)
            ->setRefereeRewards(333);
        $History = $this->historyRepository->create($Referrer, $Referee, $Config);
        $this->assertEquals(0, $History->getReferrerRewards(), "should be zero when disabled");
        $this->assertEquals($Config->getRefereeRewards(), $History->getRefereeRewards(), "should return same rewards point");
        // when referee reward is disabled
        $Config->setReferrerRewardsEnabled(true)
            ->setReferrerRewards(500)
            ->setRefereeRewardsEnabled(false)
            ->setRefereeRewards(400);
        $History = $this->historyRepository->create($Referrer, $Referee, $Config);
        $this->assertEquals($Config->getReferrerRewards(), $History->getReferrerRewards(), "should return same rewards point");
        $this->assertEquals(0, $History->getRefereeRewards(), "should be zero when disabled");
        // when both rewards are enabled
        $Config->setReferrerRewardsEnabled(true)
            ->setReferrerRewards(2000)
            ->setRefereeRewardsEnabled(true)
            ->setRefereeRewards(1500);
        $History = $this->historyRepository->create($Referrer, $Referee, $Config);
        $this->assertEquals($Config->getReferrerRewards(), $History->getReferrerRewards(), "should return same rewards point");
        $this->assertEquals($Config->getRefereeRewards(), $History->getRefereeRewards(), "should return same rewards point");
    }

}
