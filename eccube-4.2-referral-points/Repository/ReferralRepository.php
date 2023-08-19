<?php

namespace Plugin\PointsOnReferral\Repository;

// use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Entity\Customer;
use Eccube\Repository\AbstractRepository;
use Eccube\Util\StringUtil;
use Plugin\PointsOnReferral\Entity\Referral;
use Plugin\PointsOnReferral\Service\ReferralService;
//use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;

class ReferralRepository extends AbstractRepository{

    /**
     * @var ReferralService
     */
    protected $referralService;

    /**
     * ReferralRepository constructor
     *
     * @param RegistryInterface $registry
     * @param ReferralService $referralService
     */
    public function __construct(ManagerRegistry $registry, ReferralService $referralService) {
        parent::__construct($registry, Referral::class);
        $this->referralService = $referralService;
    }

    /**
     * @param Customer $Customer
     * @return Referral
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createFromCustomer(Customer $Customer) {
        $Referral = new Referral();
        $Referral->setCustomer($Customer)
            ->setCustomerId($Customer->getId())
            ->setReferralCode($this->generateReferralCode())
            ->setCreateDate(date_create())
            ->setUpdateDate(date_create());
        $this->getEntityManager()->persist($Referral);
        $this->getEntityManager()->flush();
        return $Referral;
    }

    /**
     * @param Customer $Customer
     * @return object|Referral
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOrCreateByCustomer(Customer $Customer) {
        $Referral = $this->findOneBy([
           'customer_id' => $Customer->getId()
        ]);
        if ($Referral) {
            return $Referral;
        } else {
            return $this->createFromCustomer($Customer);
        }
    }

    /**
     * @return string
     */
    public function generateReferralCode() {
        do {
            $referral_code = StringUtil::random() . time();
        } while(
            $this->count(['referral_code' => $referral_code])
        );
        return $referral_code;
    }

}
