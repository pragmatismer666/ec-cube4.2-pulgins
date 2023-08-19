<?php


namespace Plugin\PointsOnReferral\Tests\Web\Mypage;


use Eccube\Tests\EccubeTestCase;

class ReferralControllerTest extends EccubeTestCase {

    public function testRedirect() {
        $this->client->request(
            'GET',
            $this->generateUrl('mypage_referral')
        );
        $this->assertTrue($this->client->getResponse()->isRedirect(), "should redirect unauthorized requests");
    }

}
