<?php

namespace Plugin\PointsOnReferral\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ReferralService {

    const QUERY_KEY = "referral_code";
    const SESSION_KEY = "referral_code";
    /**
     * ReferralService constructor
     *
     */
    public function __construct() {

    }

    /**
     * @param Request $request
     * @return mixed|null
     */
    public function getRequestReferralCode(Request $request) {
        return $request->get(self::QUERY_KEY);
    }

    /**
     * @param SessionInterface $session
     * @param string $referal_code
     */
    public function storeSessionReferralCode(SessionInterface $session, $referral_code) {
        $session->set(self::SESSION_KEY, $referral_code);
    }

    /**
     * @param SessionInterface $session
     * @param bool $destroy
     * @return mixed
     */
    public function retreiveSessionReferralCode(SessionInterface $session, $destroy = true) {
        $referral_code = $session->get(self::SESSION_KEY);
        if ($destroy) {
            $session->remove(self::SESSION_KEY);
        }
        return $referral_code;
    }

    /**
     * @param SessionInterface $session
     * @return bool
     */
    public function hasSessionReferralCode(SessionInterface $session) {
        return !empty($this->retreiveSessionReferralCode($session, false));
    }

    /**
     * @param SessionInterface $session
     */
    public function removeSessionReferralCode(SessionInterface $session) {
        $session->remove(self::SESSION_KEY);
    }

}
