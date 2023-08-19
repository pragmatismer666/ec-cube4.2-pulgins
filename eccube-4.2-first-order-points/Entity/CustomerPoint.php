<?php
/*
* Plugin Name : PointsOnFirstOrder
*
* Copyright (C) 2020 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\PointsOnFirstOrder\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Customer;

/**
 * CustomerPoint
 *
 * @ORM\Table(name="plg_points_on_first_order_customer_point")
 * @ORM\Entity(repositoryClass="Plugin\PointsOnFirstOrder\Repository\CustomerPointRepository")
 */
class CustomerPoint
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     * @ORM\Id
     */
    private $Customer;

    /**
     * @var int
     *
     * @ORM\Column(name="is_added", type="integer", options={"default":0})
     */
    private $is_added;

    /**
     * @var int
     *
     * @ORM\Column(name="point", type="integer", options={"default":0})
     */
    private $point;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

//    /**
//     * @return int
//     */
//    public function getId()
//    {
//        return $this->id;
//    }
//
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
     * @return int
     */
    public function isAdded()
    {
        return $this->is_added;
    }

    /**
     * @param int $is_added
     *
     * @return $this
     */
    public function setAdded($is_added)
    {
        $this->is_added = $is_added;

        return $this;
    }


    /**
     * @return int
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @param int $point
     *
     * @return $this
     */
    public function setPoint($point)
    {
        $this->point = $point;

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
