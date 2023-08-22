<?php

namespace Plugin\PointsOnReferral\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\PointsOnReferral\Entity\Config;
// use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
class ConfigRepository extends AbstractRepository {

    /**
     * ConfigRepository constructor
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry) {
        parent::__construct($registry, Config::class);
    }

    /**
     * @return \Plugin\PointsOnReferral\Entity\Config
     */
    public function getConfig() {
        $Config = $this->findOneBy([], ['id' => 'desc']);
        if ($Config) {
            return $Config;
        } else {
            $Config = new Config();
            $Config->setReferrerRewardsEnabled(false)
                ->setReferrerRewards(0)
                ->setRefereeRewardsEnabled(false)
                ->setRefereeRewards(0);
            $this->save($Config);
            $this->getEntityManager()->flush();
            return $Config;
        }
    }

}
