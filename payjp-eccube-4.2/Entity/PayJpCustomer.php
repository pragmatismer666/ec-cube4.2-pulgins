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
use Eccube\Entity\Customer;

/**
 * Customer
 * 
 * @ORM\Table(name="plg_payjp_customer")
 * @ORM\Entity(repositoryClass="Plugin\PayJp\Repository\PayJpCustomerRepository")
 */
class PayJpCustomer
{
    /**
     * @var int
     * 
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Customer
     * 
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @var string
     * 
     * @ORM\Column(name="payjp_customer_id", type="string")
     */
    private $payjp_customer_id;

    /**
     * @var int
     * 
     * @ORM\Column(name="is_save_card_on", type="integer", options={"default" : 0}, nullable=true)
     */
    private $is_save_card_on;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Customer.
     *
     * @param Customer $Customer
     *
     * @return $this
     */
    public function setCustomer(Customer $Customer)
    {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * Get Customer.
     *
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }


    /**
     * @return string
     */
    public function getPayJpCustomerId()
    {
        return $this->payjp_customer_id;
    }

    /**
     * @param string $payjp_customer_id
     *
     * @return $this;
     */
    public function setPayJpCustomerId($payjp_customer_id)
    {
        $this->payjp_customer_id = $payjp_customer_id;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsSaveCardOn()
    {
        return $this->is_save_card_on > 0? true:false;
    }

    /**
     * @param boolean $is_save_card_on
     *
     * @return $this;
     */
    public function setIsSaveCardOn($is_save_card_on)
    {
        $this->is_save_card_on = $is_save_card_on? 1:0;

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