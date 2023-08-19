<?php


namespace Plugin\PointsOnReferral\Tests\Entity;


use Eccube\Tests\EccubeTestCase;
use Plugin\PointsOnReferral\Entity\Config;
use Plugin\PointsOnReferral\Entity\History;

class HistoryTest extends EccubeTestCase {

    public function testGetOwnerShip() {
        $Referrer = $this->createCustomer();
        $Referee = $this->createCustomer();
        $Config = $this->entityManager->getRepository(Config::class)->getConfig();
        $History = $this->entityManager->getRepository(History::class)->create($Referrer, $Referee, $Config);
        $this->assertEquals(History::REFERRER, $History->getOwnerShip($Referrer), "should be referrer ownership");
        $this->assertEquals(History::REFEREE, $History->getOwnerShip($Referee), "should be referee ownership");
        $this->assertEquals(History::UNKNOWN, $History->getOwnerShip($this->createCustomer()), "should be unknown ownership");
    }

    public function testSetReadByAndIsReadyBy() {
        $Referrer = $this->createCustomer();
        $Referee = $this->createCustomer();
        $Config = $this->entityManager->getRepository(Config::class)->getConfig();
        $History = $this->entityManager->getRepository(History::class)->create($Referrer, $Referee, $Config);
        $this->assertNull($History->getReferrerReadDate(), "should be null when referrer has not read history");
        $this->assertFalse($History->isReadBy($Referrer), "should return false when referrer has not read history");
        $this->assertFalse($History->isReadBy($Referee), "should return false when referee has not read history");
        // read by referrer
        $History->setReadBy($Referrer);
        $this->assertTrue($History->isReadBy($Referrer), "should return true when referrer has read history");
        $this->assertFalse($History->isReadBy($Referee), "should return false when referee has not read history");
        // read by referee
        $History->setReadBy($Referee);
        $this->assertTrue($History->isReadBy($Referee), "should return true when referee has read history");
        // read by unknown
        $Unknown = $this->createCustomer();
        $this->assertFalse($History->isReadBy($Unknown), "should return false for unknown");
        $History->setReadBy($Unknown);
        $this->assertFalse($History->isReadBy($Unknown), "should do nothing for unknown");
    }

}
