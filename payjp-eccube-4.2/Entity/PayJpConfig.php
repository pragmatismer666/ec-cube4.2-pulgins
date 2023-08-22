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
 * @ORM\Table(name="plg_pay_jp_config")
 * @ORM\Entity(repositoryClass="Plugin\PayJp\Repository\PayJpConfigRepository")
 */
class PayJpConfig
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
     * @var string
     *
     * @ORM\Column(name="public_api_key", type="string", length=255, nullable=true)
     */
    private $public_api_key;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key_secret", type="string", length=255, nullable=true)
     */
    private $api_key_secret;

    /**
     * @var int
     * 
     * @ORM\Column(name="is_auth_and_capture_on", type="integer", options={"default" : 0}, nullable=true)
     */
    private $is_auth_and_capture_on;

    /**
     * @var string
     * 
     * @ORM\Column(name="payjp_fees_percent", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $payjp_fees_percent;

    /**
     * @return boolean
     */
    public function getIsAuthAndCaptureOn(){
        return $this->is_auth_and_capture_on > 0? true:false;
    }

    /**
     * @param boolean $is_auth_and_capture_on
     * 
     * @return $this;
     */
    public function setIsAuthAndCaptureOn($is_auth_and_capture_on){
        $this->is_auth_and_capture_on = $is_auth_and_capture_on? 1:0;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayJpFeesPercent(){
        return $this->payjp_fees_percent;
    }

    /**
     * @param string $payjp_fees_percent
     * 
     * @return $this;
     */
    public function setPayJpFeesPercent($payjp_fees_percent){
        $this->payjp_fees_percent = $payjp_fees_percent;

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
     * @return string
     */
    public function getPublicApiKey()
    {
        return $this->public_api_key;
    }

    /**
     * @param string $public_api_key
     *
     * @return $this;
     */
    public function setPublicApiKey($public_api_key)
    {
        $this->public_api_key = $public_api_key;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKeySecret()
    {
        return $this->api_key_secret;
    }

    /**
     * @param string $api_key_secret
     *
     * @return $this;
     */
    public function setApiKeySecret($api_key_secret)
    {
        $this->api_key_secret = $api_key_secret;

        return $this;
    }
}