<?php
/*
* Plugin Name : RestrictEmailDomain
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\RestrictEmailDomain\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_restrictemaildomain_config")
 * @ORM\Entity(repositoryClass="Plugin\RestrictEmailDomain\Repository\ConfigRepository")
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
     * @var text
     *
     * @ORM\Column(name="restricted_email_domains", type="text")
     */
    private $restricted_email_domains;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return text
     */
    public function getRestrictedEmailDomains()
    {
        return $this->restricted_email_domains;
    }

    /**
     * @param text $restricted_email_domains
     *
     * @return $this;
     */
    public function setRestrictedEmailDomains($restricted_email_domains)
    {
        $this->restricted_email_domains = $restricted_email_domains;

        return $this;
    }
}
