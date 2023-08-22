<?php

namespace Plugin\PointsOnReferral\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_points_on_referral_config")
 * @ORM\Entity(repositoryClass="Plugin\PointsOnReferral\Repository\ConfigRepository")
 */

class Config {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="referrer_rewards_enabled", type="boolean", options={"default":true})
     */
    private $referrer_rewards_enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="referrer_rewards", type="decimal", precision=12, scale=0, options={"unsigned":false,"default":0})
     */
    private $referrer_rewards;

    /**
     * @var boolean
     *
     * @ORM\Column(name="referee_rewards_enabled", type="boolean", options={"default":true})
     */
    private $referee_rewards_enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="referee_rewards", type="decimal", precision=12, scale=0, options={"unsigned":false,"default":0})
     */
    private $referee_rewards;

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function getReferrerRewardsEnabled() {
        return $this->referrer_rewards_enabled;
    }

    /**
     * @return string
     */
    public function getReferrerRewards() {
        return $this->referrer_rewards;
    }

    /**
     * @return boolean
     */
    public function getRefereeRewardsEnabled() {
        return $this->referee_rewards_enabled;
    }

    /**
     * @return string
     */
    public function getRefereeRewards() {
        return $this->referee_rewards;
    }

    /**
     * @param $enabled boolean
     * @return $this
     */
    public function setReferrerRewardsEnabled($enabled) {
        $this->referrer_rewards_enabled = $enabled;

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
     * @param $enabled boolean
     * @return $this
     */
    public function setRefereeRewardsEnabled($enabled) {
        $this->referee_rewards_enabled = $enabled;

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
}
