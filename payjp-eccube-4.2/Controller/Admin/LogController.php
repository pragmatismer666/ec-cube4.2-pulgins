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


namespace Plugin\PayJp\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PayJp\Repository\PayJpLogRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Component\Pager\PaginatorInterface;


class LogController extends AbstractController
{
    /**
     * @var PayJpLogRepository
     */
    protected $payJpLogRepository;
    /**
     * ConfigController constructor.
     *
     * @param PayJpLogRepository $payJpLogRepository
     */
    public function __construct(PayJpLogRepository $payJpLogRepository)
    {
        $this->payJpLogRepository = $payJpLogRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/pay_jp/log", name="pay_jp_admin_log")
     * @Route("/%eccube_admin_route%/pay_jp/page/{page_no}", requirements={"page_no" = "\d+"}, name="pay_jp_admin_log_page")
     * @Template("@PayJp/admin/pay_jp_log.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $page_count = $this->eccubeConfig->get('eccube_default_page_count');
        if($page_no){
        } else {
            $page_no=1;
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('s')->from('Plugin\PayJp\Entity\PayJpLog', 's')->orderBy('s.id','DESC');
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );
        return [
            'pagination' => $pagination
        ];
    }
}