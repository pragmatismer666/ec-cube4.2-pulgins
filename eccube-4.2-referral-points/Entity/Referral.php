<?php

namespace Plugin\PointsOnReferral\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Customer;

/**
 * Referral
 *
 * @ORM\Table(name="plg_points_on_referral", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="plg_points_on_referral_referral_code_idx", columns={"referral_code"})
 * })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\Entity(repositoryClass="Plugin\PointsOnReferral\Repository\ReferralRepository")
 */
class Referral {

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
     * @ORM\Column(name="customer_id", type="integer", nullable=false, options={"unsigned":true})
     */
    private $customer_id;

    /**
     * Referrer customer id, it's not a plugin customer id
     * @var integer
     * @ORM\Column(name="referrer_id", type="integer", nullable=true, options={"unsigned":true})
     */
    private $referrer_id;

    /**
     * @var string
     * @ORM\Column(name="referral_code", type="string", length=255, nullable=true)
     */
    private $referral_code;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @var Customer
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getCustomerId() {
        return $this->customer_id;
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
    public function getReferralCode() {
        return $this->referral_code;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate() {
        return $this->create_date;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate() {
        return $this->update_date;
    }

    /**
     * @return Customer
     */
    public function getCustomer() {
        return $this->Customer;
    }

    /**
     * @param $customer_id integer
     * @return $this
     */
    public function setCustomerId($customer_id) {
        $this->customer_id = $customer_id;

        return $this;
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
     * @param $referral_code string
     * @return $this
     */
    public function setReferralCode($referral_code) {
        $this->referral_code = $referral_code;

        return $this;
    }

    /**
     * @param $Customer Customer
     * @return $this
     */
    public function setCustomer(Customer $Customer) {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * @param $create_date \DateTime
     * @return $this
     */
    public function setCreateDate(\DateTime $create_date) {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @param $update_date \DateTime
     * @return $this
     */
    public function setUpdateDate(\DateTime $update_date) {
        $this->update_date = $update_date;

        return $this;
    }
}
