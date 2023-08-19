<?php

namespace Plugin\PointsOnReferral\Tests\Web\Admin;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\PointsOnReferral\Entity\Config;
use Plugin\PointsOnReferral\Repository\ConfigRepository;

class ConfigControllerTest extends AbstractAdminWebTestCase {

    /**
     * @var $configRepository ConfigRepository
     */
    protected $configRepository;

    public function setUp() {
        parent::setUp();

        $this->configRepository = $this->entityManager->getRepository(Config::class);
    }

    public function testRouting() {
        $this->client->request("GET", $this->generateUrl("points_on_referral_admin_config"));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "should get successful response");
    }

    public function testValidSubmit() {

        $formData = [
            "_token" => "dummy",
            "referrer_rewards_enabled" => 1,
            "referrer_rewards" => "2500",
            "referee_rewards_enabled" => null, // request param should be empty for false
            "referee_rewards" => "1000"
        ];

        $this->client->request("POST", $this->generateUrl("points_on_referral_admin_config"), ["config" => $formData]);

        $this->assertTrue($this->client->getResponse()->isRedirection(), "should redirect after save");

        $Config = $this->configRepository->getConfig();

        $this->assertEquals($formData['referrer_rewards_enabled'], $Config->getReferrerRewardsEnabled());
        $this->assertEquals($formData['referrer_rewards'], $Config->getReferrerRewards());
        $this->assertEquals($formData['referee_rewards_enabled'], $Config->getRefereeRewardsEnabled());
        $this->assertEquals($formData['referee_rewards'], $Config->getRefereeRewards());
    }

    public function testInvalidSubmit() {
        $formData = [
            "_token" => "dummy",
            "referrer_rewards_enabled" => 1,
            "referrer_rewards" => "2500",
            "referee_rewards_enabled" => null, // request param should be empty for false
            "referee_rewards" => "-1000"
        ];

        $Before = $this->configRepository->getConfig();

        $this->client->request("POST", $this->generateUrl("points_on_referral_admin_config"), ["config" => $formData]);

        $this->assertFalse($this->client->getResponse()->isRedirection(), "should not redirect for invalid submit");

        $After = $this->configRepository->getConfig();

        $this->assertEquals($Before->getId(), $After->getId());
        $this->assertEquals($Before->getReferrerRewardsEnabled(), $After->getReferrerRewardsEnabled());
        $this->assertEquals($Before->getReferrerRewards(), $After->getReferrerRewards());
        $this->assertEquals($Before->getRefereeRewardsEnabled(), $After->getRefereeRewardsEnabled());
        $this->assertEquals($Before->getRefereeRewards(), $After->getRefereeRewards());
    }

}
