<?php

namespace Plugin\PointsOnReferral;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\PointsOnReferral\Entity\Config;
use Plugin\PointsOnReferral\Entity\Referral;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager {

    protected $pageUrls = [
        'mypage_referral' => 'MYページ/ご紹介履歴'
    ];

    public function enable(array $meta, ContainerInterface $container) {
        $em = $container->get('doctrine.orm.entity_manager');
        $Config = $this->createConfig($em);
        $this->updateReferrals($em);
        $this->registerPages($container);
    }

    public function uninstall(array $meta, ContainerInterface $container) {
        parent::uninstall($meta, $container);
        $this->unregisterPages($container);
    }

    /**
     * @param EntityManagerInterface $em
     * @return object|Config
     */
    protected function createConfig(EntityManagerInterface $em) {
        $Config = $em->find(Config::class, 1);
        if ($Config) {
            return $Config;
        }
        $Config = new Config();
        $Config->setRefereeRewardsEnabled(true)
            ->setRefereeRewards(0)
            ->setReferrerRewardsEnabled(true)
            ->setReferrerRewards(0);
        $em->persist($Config);
        $em->flush();
        return $Config;
    }

    /**
     * @param EntityManagerInterface $em
     */
    protected function updateReferrals(EntityManagerInterface $em) {
        $Customers = $em->getRepository(Customer::class)->findAll();
        $customer_ids = [];
        foreach($Customers as $Customer) {
            $em->getRepository(Referral::class)->findOrCreateByCustomer($Customer);
            $customer_ids[] = $Customer->getId();
        }
        if ($customer_ids) {
            $qb = $em->createQueryBuilder();
            $query = $qb->from(Referral::class, 'r')
                ->where('r.customer_id NOT IN (:customer_ids)')
                ->setParameter('customer_ids', $customer_ids, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->delete()
                ->getQuery();
            $query->execute();
            $em->flush();
        }
    }

    protected function registerPages(ContainerInterface $container) {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        foreach($this->pageUrls as $url => $name) {
            $Page = $entityManager->getRepository(Page::class)->findOneBy(['url' => $url]);
            if (!$Page) {
                $this->createPage($entityManager, $name, $url);
            }
        }
    }

    protected function unregisterPages(ContainerInterface $container) {
        foreach($this->pageUrls as $url => $name) {
            $this->removePage($container->get('doctrine.orm.entity_manager'), $url);
        }
    }

    protected function createPage(EntityManagerInterface $em, $name, $url) {
        $Page = new Page();
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT)
            ->setName($name)
            ->setUrl($url)
            ->setFileName('@PointsOnReferral/default/Mypage/referral');

        $em->persist($Page);
        $em->flush();
        $Layout = $em->find(Layout::class, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($Page)
            ->setPageId($Page->getId())
            ->setLayout($Layout)
            ->setLayoutId($Layout->getId())
            ->setSortNo(0);
        $em->persist($PageLayout);
        $em->flush();
    }

    protected function removePage(EntityManagerInterface $em, $url) {
        $Page = $em->getRepository(Page::class)->findOneBy(['url' => $url]);

        if (!$Page) {
            return;
        }
        foreach ($Page->getPageLayouts() as $PageLayout) {
            $em->remove($PageLayout);
            $em->flush();
        }
        $em->remove($Page);
        $em->flush();
    }
}
