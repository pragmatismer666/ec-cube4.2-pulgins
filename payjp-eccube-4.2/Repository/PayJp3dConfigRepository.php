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
namespace Plugin\PayJp\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\PayJp\Entity\PayJp3dConfig;
// use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;

class PayJp3dConfigRepository extends AbstractRepository
{
        /**
     * 3dConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PayJp3dConfig::class);
    }


    public function get($id = 1)
    {
        return $this->find($id);
    }
}