<?php

namespace Plugin\UKOMI4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Plugin\UKOMI4\Form\Type\Admin\ConfigType;
use Plugin\UKOMI4\Form\Type\Admin\SyncType;
use Plugin\UKOMI4\Repository\ConfigRepository;
use Plugin\UKOMI4\Service\UkomiService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;
    /**
     * @var UkomiService
     */
    protected $ukomiService;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        ProductRepository $productRepository,
        UkomiService $ukomiService,
        UrlGeneratorInterface $router)
    {
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->productRepository = $productRepository;
        $this->ukomiService = $ukomiService;
        $this->router = $router;
    }

    /**
     * @Route("/%eccube_admin_route%/ukomi/config", name="ukomi4_admin_config")
     * @Template("@UKOMI4/admin/config.twig")
     */
    public function index(Request $request)
    {
        $method = $request->getMethod();
        $config_api_key = $this->configRepository->getConfig('api_key');
        $config_api_secret = $this->configRepository->getConfig('api_secret');
        $config_order_status = $this->configRepository->getConfig('order_status');
        $config_no_auto = $this->configRepository->getConfig('no_auto', 0);

        $form = $this->createForm(ConfigType::class, [
            'api_key' => $config_api_key,
            'api_secret' => $config_api_secret,
            'order_status' => $config_order_status ? $this->orderStatusRepository->find($config_order_status) : null,
            'no_auto' => $config_no_auto ? true : false
        ]);

        $form_sync = $this->createForm(SyncType::class, [
            'sync_date_start' => new \DateTime('-30 day'),
            'sync_date_end' => new \DateTime('now')
        ]);

        // @TODO form_sync
        if ($request->isMethod('post')) {
            switch($request->get('mode')) {
                case 'sync':
                    $form_sync->handleRequest($request);
                    if ($form_sync->isSubmitted() && $form_sync->isValid()) {
                        $start = $form_sync->get('sync_date_start')->getData();
                        $end = $form_sync->get('sync_date_end')->getData();
                        $status = $form_sync->get('order_status')->getData();
                        $search = [];
                        if ($start) $search['order_date_start'] = $start;
                        if ($end) $search['order_date_end'] = $end;
                        if ($status) $search['status'] = [$status->getId()];
                        //var_dump($search);exit;
                        $orders = $this->orderRepository->getQueryBuilderBySearchDataForAdmin($search)->getQuery()->execute();
                        if (empty($orders)) {
                            $this->addWarning('admin.ukl.sync.nodata', 'admin');
                            break;
                        }
                        $result = $this->ukomiService->createOrders($orders);
                        if ($result) {
                            $this->addSuccess('admin.ukl.sync.complete', 'admin');
                            return $this->redirectToRoute('ukomi4_admin_config');
                        } else {
                            $message = $this->ukomiService->getErrorMessage();
                            if ($this->ukomiService->getInputErrors()) {
                                $message .= '(';
                                foreach($this->ukomiService->getInputErrors() as $input_error) {
                                    $message .= "\nOrder {$input_error['order_id']} : {$input_error['field']} / {$input_error['error']}";
                                }
                                $message .= ')';
                            }
                            $this->addError($message, 'admin');
                        }
                    }
                    break;
                case 'config':
                    $form->handleRequest($request);
                    if ($form->isSubmitted() && $form->isValid()) {
                        $this->configRepository->setConfig('api_key', $form->get('api_key')->getData());
                        $this->configRepository->setConfig('api_secret', $form->get('api_secret')->getData());
                        $this->configRepository->setConfig('order_status', $form->get('order_status')->getData() ? $form->get('order_status')->getData()->getId() : 0);
                        //echo $form->get('order_status')->getData()->getId();exit;
                        $this->configRepository->setConfig('no_auto', $form->get('no_auto')->getData() ? 1 : 0);
                        if (!$this->ukomiService->flush()->getAccessToken()) {
                            $this->configRepository->setConfig('api_init', 0);
                            $this->addError('admin.ukl.save.error', 'admin');
                        } else {
                            $this->configRepository->setConfig('api_init', 1);
                            $this->addSuccess('admin.ukl.save.complete', 'admin');
                            return $this->redirectToRoute('ukomi4_admin_config');
                        }
                    }
                    break;
            }
        }

        $api_init = $this->configRepository->getConfig('api_init', 0);

        return [
            'form' => $form->createView(),
            'form_sync' => $form_sync->createView(),
            'api_init' => $api_init,
            'tags' => $this->applyTagParams($this->ukomiService->tags())
        ];
    }

    private function applyTagParams($tags)
    {
        foreach($tags as $key => &$tag) {
            $tag = preg_replace_callback('/\{(PRODUCT_ID|PRODUCT_GROUP)\}/', function($matches) {
                switch($matches[1]) {
                    case 'PRODUCT_ID': return '{{ Product.code_min }}';
                    case 'PRODUCT_GROUP': return '{{ Product.id }}';
                }
            }, $tag);
            $tag = preg_replace_callback('/######(ORDER_ID|ORDER_AMOUNT|ORDER_CURRENCY)######/', function($matches) {
                switch($matches[1]) {
                    case 'ORDER_ID': return '{{ Order.id }}';
                    case 'ORDER_AMOUNT': return '{{ Order.total }}';
                    case 'ORDER_CURRENCY': return 'JPY';
                }
            }, $tag);
        }
        return $tags;
    }

    /**
     * @Route("/%eccube_admin_route%/ukomi/check", name="ukomi4_admin_check")
     */
    public function check(Request $request) {
        $check = [
            'head' => 0,
            'star_list' => 0,
            'star_detail' => 0,
            'review_list' => 0,
            'tracking' => ($this->configRepository->getConfig('api_init', 0) == 1 && $this->configRepository->getConfig('no_auto', 0) != 1) ? 1 : 0
        ];
        // ヘッダJS
        $url = $this->router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $html = file_get_contents($url);
        if (preg_match('#' . preg_quote($this->ukomiService->getJsUrl(), '#') .'/.+/widget\.js#', $html)) {
            $check['head'] = 1;
        }
        // スターレーティング・リストページ
        $url = $this->router->generate('product_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $html = file_get_contents($url);
        if (preg_match('#<div\s+class="review-summary-container"[^<>]*data-pid=".+"[^<>]*data-action="summary"[^<>]*>#', $html)) {
            $check['star_list'] = 1;
        }
        $product = $this->productRepository->findOneBy([]);
        if ($product) {
            // スターレーティング・詳細ページ
            $url = $this->router->generate('product_detail', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $html = file_get_contents($url);
            if (preg_match('#<div\s+class="review-summary-container"[^<>][^<>]*>#', $html)) {
                $check['star_detail'] = 1;
            }
            // レビューリスト・詳細ページ
            if (preg_match('#<div\s+class="review-container"[^<>]*data-pid=".+"[^<>]*data-action="widget"[^<>]*>#', $html)) {
                $check['review_list'] = 1;
            }
        }
        return $this->json($check);

    }

}
