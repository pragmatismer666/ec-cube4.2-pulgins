<?php
/*
* Plugin Name : PointsOnSignUp
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\PointsOnSignUp\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\PointsOnSignUp\Entity\Config;
// use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * ConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ConfigRepository extends AbstractRepository
{
    /**
     * ConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @param int $id
     * @return null|Config
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
