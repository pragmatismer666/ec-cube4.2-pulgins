<?php

namespace Plugin\PointsOnFirstOrder\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_points_on_first_order_config")
 * @ORM\Entity(repositoryClass="Plugin\PointsOnFirstOrder\Repository\ConfigRepository")
 */
class Config
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
     * @var int
     *
     * @ORM\Column(name="points", type="integer", options={"unsigned":true, "default":0})
     */
    private $points;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @param int $points
     *
     * @return $this;
     */
    public function setPoints($points)
    {
        $this->points = $points;

        return $this;
    }
}
