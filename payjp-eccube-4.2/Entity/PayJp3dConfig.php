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

/**
 * Config
 *
 * @ORM\Table(name="plg_pay_jp_3d_config")
 * @ORM\Entity(repositoryClass="Plugin\PayJp\Repository\PayJp3dConfigRepository")
 */
class PayJp3dConfig
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     */
    private $id = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="td_min_value", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0}, nullable=true)
     */
    private $td_min_value;

    /**
     * @var boolean
     *
     * @ORM\Column(name="td_enabled", type="boolean", options={"default":false}, nullable=true)
     */
    private $td_enabled = false;

    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function getTdMinValue()
    {
        return $this->td_min_value;
    }
    public function setTdMinValue($td_min_value)
    {
        $this->td_min_value = $td_min_value;
        return $this;
    }
    public function getTdEnabled()
    {
        return $this->td_enabled;
    }
    public function setTdEnabled($td_enabled)
    {
        $this->td_enabled = $td_enabled;
        return $this;
    }
}