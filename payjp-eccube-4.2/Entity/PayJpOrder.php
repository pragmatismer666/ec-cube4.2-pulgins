<?php
/*
* Plugin Name : PayJp
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\PayJp\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Order;

/**
 * Order
 *
 * @ORM\Table(name="plg_pay_jp_order")
 * @ORM\Entity(repositoryClass="Plugin\PayJp\Repository\PayJpOrderRepository")
 */
class PayJpOrder
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_jp_token", type="string")
     */
    private $pay_jp_token;

    /**
     * @var string
     * 
     * @ORM\Column(name="pay_jp_customer_id_for_guest_checkout", type="string", nullable=true)
     */
    private $pay_jp_customer_id_for_guest_checkout;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_jp_charge_id", type="string")
     */
    private $pay_jp_charge_id;
    
    /**
     * @var int
     * 
     * @ORM\Column(name="is_charge_captured", type="integer", options={"default" : 0}, nullable=true)
     */
    private $is_charge_captured;
    
    /**
     * @var int
     * 
     * @ORM\Column(name="is_charge_refunded", type="integer", options={"default" : 0}, nullable=true)
     */
    private $is_charge_refunded;

    /**
     * @var int
     * 
     * @ORM\Column(name="selected_refund_option", type="integer", options={"unsigned":true,"default":0}, nullable=true)
     */
    private $selected_refund_option;

    /**
     * @var string
     * 
     * @ORM\Column(name="refunded_amount", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $refunded_amount = 0;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="paid_at", type="datetime", nullable=true)
     */
    private $paid_at;

    public function getPaidAt()
    {
        return $this->paid_at;
    }
    public function setPaidAt($paid_at)
    {
        $this->paid_at = $paid_at;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Order.
     *
     * @param Order $Order
     *
     * @return $this
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;

        return $this;
    }

    /**
     * Get Order.
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * @return string
     */
    public function getPayJpToken()
    {
        return $this->pay_jp_token;
    }

    /**
     * @param string $pay_jp_token
     *
     * @return $this;
     */
    public function setPayJpToken($pay_jp_token)
    {
        $this->pay_jp_token = $pay_jp_token;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayJpChargeId()
    {
        return $this->pay_jp_charge_id;
    }

    /**
     * @param string $pay_jp_charge_id
     *
     * @return $this;
     */
    public function setPayJpChargeId($pay_jp_charge_id)
    {
        $this->pay_jp_charge_id = $pay_jp_charge_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayJpCustomerIdForGuestCheckout()
    {
        return $this->pay_jp_customer_id_for_guest_checkout;
    }

    /**
     * @param string $stripe_customer_id_for_guest_checkout
     *
     * @return $this;
     */
    public function setPayJpCustomerIdForGuestCheckout($pay_jp_customer_id_for_guest_checkout)
    {
        $this->pay_jp_customer_id_for_guest_checkout = $pay_jp_customer_id_for_guest_checkout;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsChargeCaptured()
    {
        return $this->is_charge_captured > 0? true:false;
    }

    /**
     * @param boolean $is_charge_captured
     *
     * @return $this;
     */
    public function setIsChargeCaptured($is_charge_captured)
    {
        $this->is_charge_captured = $is_charge_captured? 1:0;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsChargeRefunded()
    {
        return $this->is_charge_refunded > 0? true:false;
    }

    /**
     * @param boolean $is_charge_refunded
     *
     * @return $this;
     */
    public function setIsChargeRefunded($is_charge_refunded)
    {
        $this->is_charge_refunded = $is_charge_refunded? 1:0;

        return $this;
    }

    /**
     * @return int
     */
    public function getSelectedRefundOption()
    {
        return $this->selected_refund_option;
    }

    /**
     * @param int $selected_refund_option
     *
     * @return $this;
     */
    public function setSelectedRefundOption($selected_refund_option)
    {
        $this->selected_refund_option = $selected_refund_option;

        return $this;
    }

    /**
     * @return string
     */
    public function getRefundedAmount()
    {
        return $this->refunded_amount;
    }

    /**
     * @param string $refunded_amount
     *
     * @return $this;
     */
    public function setRefundedAmount($refunded_amount)
    {
        $this->refunded_amount = $refunded_amount;

        return $this;
    }
    
    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     *
     * @return $this;
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }
}