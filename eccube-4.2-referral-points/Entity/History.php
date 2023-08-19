<?php

namespace Plugin\PointsOnReferral\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Customer;

/**
 * History
 *
 * @ORM\Table(name="plg_points_on_referral_history")
 * @ORM\Entity(repositoryClass="Plugin\PointsOnReferral\Repository\HistoryRepository")
 */
class History {

    const REFERRER = 1;
    const REFEREE = 2;
    const UNKNOWN = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="referrer_id", type="integer", options={"unsigned": true})
     */
    private $referrer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer_email", type="string", length=255)
     */
    private $referrer_email;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer_name01", type="string", length=255)
     */
    private $referrer_name01;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer_name02", type="string", length=255)
     */
    private $referrer_name02;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer_kana01", type="string", length=255, nullable=true)
     */
    private $referrer_kana01;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer_kana02", type="string", length=255, nullable=true)
     */
    private $referrer_kana02;

    /**
     * @var integer
     *
     * @ORM\Column(name="referee_id", type="integer", options={"unsigned": true})
     */
    private $referee_id;

    /**
     * @var string
     *
     * @ORM\Column(name="referee_email", type="string", length=255)
     */
    private $referee_email;

    /**
     * @var string
     *
     * @ORM\Column(name="referee_name01", type="string", length=255)
     */
    private $referee_name01;

    /**
     * @var string
     *
     * @ORM\Column(name="referee_name02", type="string", length=255)
     */
    private $referee_name02;

    /**
     * @var string
     *
     * @ORM\Column(name="referee_kana01", type="string", length=255, nullable=true)
     */
    private $referee_kana01;

    /**
     * @var string
     *
     * @ORM\Column(name="referee_kana02", type="string", length=255, nullable=true)
     */
    private $referee_kana02;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer_rewards", type="decimal", precision=12, scale=0, options={"unsigned":false,"default":0})
     */
    private $referrer_rewards;

    /**
     * @var string
     *
     * @ORM\Column(name="referee_rewards", type="decimal", precision=12, scale=0, options={"unsigned":false,"default":0})
     */
    private $referee_rewards;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible_to_referrer", type="boolean", options={"default": true})
     */
    private $visible_to_referrer;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible_to_referee", type="boolean", options={"default": true})
     */
    private $visible_to_referee;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="referrer_read_date", type="datetimetz", nullable=true)
     */
    private $referrer_read_date;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="referee_read_date", type="datetimetz", nullable=true)
     */
    private $referee_read_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getReferrerId() {
        return $this->referrer_id;
    }

    /**
     * @return string
     */
    public function getReferrerEmail() {
        return $this->referrer_email;
    }

    /**
     * @return string
     */
    public function getReferrerName01() {
        return $this->referrer_name01;
    }

    /**
     * @return string
     */
    public function getReferrerName02() {
        return $this->referrer_name02;
    }

    /**
     * @return string
     */
    public function getReferrerKana01() {
        return $this->referrer_kana01;
    }

    /**
     * @return string
     */
    public function getReferrerKana02() {
        return $this->referrer_kana02;
    }

    /**
     * @return integer
     */
    public function getRefereeId() {
        return $this->referee_id;
    }

    /**
     * @return string
     */
    public function getRefereeEmail() {
        return $this->referee_email;
    }

    /**
     * @return string
     */
    public function getRefereeName01() {
        return $this->referee_name01;
    }

    /**
     * @return string
     */
    public function getRefereeName02() {
        return $this->referee_name02;
    }

    /**
     * @return string
     */
    public function getRefereeKana01() {
        return $this->referee_kana01;
    }

    /**
     * @return string
     */
    public function getRefereeKana02() {
        return $this->referee_kana02;
    }

    /**
     * @return integer
     */
    public function getReferrerRewards() {
        return $this->referrer_rewards;
    }

    /**
     * @return integer
     */
    public function getRefereeRewards() {
        return $this->referee_rewards;
    }

    /**
     * @return boolean
     */
    public function getVisibleToReferrer() {
        return $this->visible_to_referrer;
    }

    /**
     * @return boolean
     */
    public function getVisibleToReferee() {
        return $this->visible_to_referee;
    }

    /**
     * @return \DateTime|null
     */
    public function getReferrerReadDate() {
        return $this->referrer_read_date;
    }

    /**
     * @return \DateTime|null
     */
    public function getRefereeReadDate() {
        return $this->referee_read_date;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate() {
        return $this->create_date;
    }

    /**
     * @return string
     */
    public function getReferrerFullName() {
        return "{$this->referrer_name01} {$this->referrer_name02}";
    }

    /**
     * @return string
     */
    public function getReferrerFullKana() {
        return "{$this->referrer_kana01} {$this->referrer_kana02}";
    }

    /**
     * @return string
     */
    public function getRefereeFullName() {
        return "{$this->referee_name01} {$this->referee_name02}";
    }

    /**
     * @return string
     */
    public function getRefereeFullKana() {
        return "{$this->referee_kana01} {$this->referee_kana02}";
    }

    /**
     * @param $Customer integer|Customer
     * @return integer
     */
    public function getOwnerShip($Customer) {
        if ($Customer instanceof Customer) {
            $customer_id = $Customer->getId();
        } else {
            $customer_id = $Customer;
        }
        if ($customer_id == $this->referrer_id) {
            return self::REFERRER;
        } else if ($customer_id == $this->referee_id) {
            return self::REFEREE;
        } else {
            return self::UNKNOWN;
        }
    }

    /**
     * @param $Customer integer|Customer
     * @return boolean
     */
    public function isReadBy($Customer) {
        $ownership = $this->getOwnerShip($Customer);
        switch ($ownership) {
            case self::REFERRER:
                return $this->referrer_read_date != null;
            case self::REFEREE:
                return $this->referee_read_date != null;
            default:
                return false;
        }
    }

    /**
     * @param $referrer_id integer
     * @return $this
     */
    public function setReferrerId($referrer_id) {
        $this->referrer_id = $referrer_id;

        return $this;
    }

    /**
     * @param $referrer_email string
     * @return $this
     */
    public function setReferrerEmail($referrer_email) {
        $this->referrer_email = $referrer_email;

        return $this;
    }

    /**
     * @param $name01 string
     * @return $this
     */
    public function setReferrerName01($name01) {
        $this->referrer_name01 = $name01;

        return $this;
    }

    /**
     * @param $name02 string
     * @return $this
     */
    public function setReferrerName02($name02) {
        $this->referrer_name02 = $name02;

        return $this;
    }

    /**
     * @param $kana01 string
     * @return $this
     */
    public function setReferrerKana01($kana01) {
        $this->referrer_kana01 = $kana01;

        return $this;
    }

    /**
     * @param $kana02 string
     * @return $this
     */
    public function setReferrerKana02($kana02) {
        $this->referrer_kana02 = $kana02;

        return $this;
    }

    /**
     * @param $referee_id integer
     * @return $this
     */
    public function setRefereeId($referee_id) {
        $this->referee_id = $referee_id;

        return $this;
    }

    /**
     * @param $referee_email string
     * @return $this
     */
    public function setRefereeEmail($referee_email) {
        $this->referee_email = $referee_email;

        return $this;
    }

    /**
     * @param $name01 string
     * @return $this
     */
    public function setRefereeName01($name01) {
        $this->referee_name01 = $name01;

        return $this;
    }

    /**
     * @param $name02 string
     * @return $this
     */
    public function setRefereeName02($name02) {
        $this->referee_name02 = $name02;

        return $this;
    }

    /**
     * @param $kana01 string
     * @return $this
     */
    public function setRefereeKana01($kana01) {
        $this->referee_kana01 = $kana01;

        return $this;
    }

    /**
     * @param $kana02 string
     * @return $this
     */
    public function setRefereeKana02($kana02) {
        $this->referee_kana02 = $kana02;

        return $this;
    }

    /**
     * @param $point integer
     * @return $this
     */
    public function setReferrerRewards($point) {
        $this->referrer_rewards = $point;

        return $this;
    }

    /**
     * @param $point integer
     * @return $this
     */
    public function setRefereeRewards($point) {
        $this->referee_rewards = $point;

        return $this;
    }

    /**
     * @param $visible boolean
     * @return $this
     */
    public function setVisibleToReferrer($visible) {
        $this->visible_to_referrer = $visible;

        return $this;
    }

    /**
     * @param $visible boolean
     * @return $this
     */
    public function setVisibleToReferee($visible) {
        $this->visible_to_referee = $visible;

        return $this;
    }

    /**
     * @param $date \DateTime
     * @return $this
     */
    public function setReferrerReadDate($date) {
        $this->referrer_read_date = $date;

        return $this;
    }

    /**
     * @param $date \DateTime
     * @return $this
     */
    public function setRefereeReadDate($date) {
        $this->referee_read_date = $date;

        return $this;
    }

    /**
     * @param $date \DateTime
     * @return $this
     */
    public function setCreateDate($date) {
        $this->create_date = $date;

        return $this;
    }

    /**
     * @param $Customer
     * @return $this
     */
    public function setReadBy($Customer) {
        $ownership = $this->getOwnerShip($Customer);
        switch($ownership) {
            case self::REFERRER:
                $this->setReferrerReadDate(date_create());
                break;
            case self::REFEREE:
                $this->setRefereeReadDate(date_create());
                break;
            default: break;
        }
        return $this;
    }
}
