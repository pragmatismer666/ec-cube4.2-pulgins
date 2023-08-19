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
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * トークンを保持するカラム.
     * 永続化は行わず, 注文確認画面で表示する.
     *
     *
     * @var string
     */
    private $pay_jp_token;

    private $is_save_card_on;

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
     * @return boolean
     */
    public function getIsSaveCardOn()
    {
        return $this->is_save_card_on;
    }

    /**
     * @param boolean $is_save_card_on
     *
     * @return $this;
     */
    public function setIsSaveCardOn($is_save_card_on)
    {
        $this->is_save_card_on = $is_save_card_on;

        return $this;
    }
}