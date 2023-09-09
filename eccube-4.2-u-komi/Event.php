<?php

namespace Plugin\UKOMI4;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Product;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Request\Context;
use Plugin\UKOMI4\Repository\ConfigRepository;
use Plugin\UKOMI4\Service\UkomiService;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;

class Event implements EventSubscriberInterface
{
    private $context;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var UkomiService
     */
    private $ukomiService;

    public function __construct(
        Context $context,
        ConfigRepository $configRepository,
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        UkomiService $ukomiService
    )
    {
        $this->context = $context;
        $this->configRepository = $configRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->ukomiService = $ukomiService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onRenderFrame', 1],
            'Shopping/complete.twig' => ['onRenderShoppingComplete', 1],
            EccubeEvents::ADMIN_ORDER_EDIT_INDEX_COMPLETE => ['onAdminOrderEditIndexComplete', 1],
            'workflow.order.completed' => ['onOrderCompleted']
        ];
    }

    protected function isInitialized()
    {
        return $this->configRepository->getConfig('api_init', 0) != 1 ? false : true;
    }

    protected function isActive()
    {
        if ($this->configRepository->getConfig('api_init', 0) != 1 || $this->configRepository->getConfig('no_auto', 0) == 1) {
            return false;
        }
        return true;
    }

    public function onRenderFrame(ResponseEvent $event)
    {
        $path = $event->getRequest()->getPathInfo();
        if (!$this->context->isFront()) {
            return;
        }
        if (!$this->isActive()) {
            return;
        }
        $response = $event->getResponse();
        $tags = $this->ukomiService->tags();

        $html = $response->getContent();
        if (preg_match('#^/products/list#', $path)) {
            $html = $this->doProductList($tags, $html);
        }
        if (preg_match('#^/products/detail/(\d+)+$#', $path, $m)) {
            $html = $this->doProductDetail($m[1], $tags, $html);
        }
        $response->setContent(preg_replace('#</\s*head>#i', "{$tags['head']}\n</head>", $html));
    }

    protected function doProductList($tags, $html)
    {
        $crawler = new Crawler($html);
        $html = $crawler->html();
        $productRepo = $this->productRepository;
        $updated = false;
        $crawler->filter('.ec-shelfGrid__item a')->each(function (Crawler $crawler) use (&$html, &$updated, $tags, $productRepo) {
            /** @var \DOMDocument $document */
            $id_text = trim($crawler->getNode(0)->getAttribute('href'));
            if (preg_match('#detail/(\d+)$#', $id_text, $m)) {
                $id = $m[1];
            } else {
                $id = $id_text;
            }
            /** @var Product $product */
            $product = $productRepo->find($id);
            //Replace value for PRODUCT_GROUP
            $tags['star_group']=str_replace('{PRODUCT_GROUP}', $product->getId(), $tags['star_group']);
            $old = $crawler->html();
            $new = $old . str_replace('{PRODUCT_ID}', $product ? $product->getCodeMin() : $id, $tags['star_group']);
            $html = str_replace($old, $new, $html);
            $updated = true;
        });
        if ($updated) {
            $html = html_entity_decode($html);
        }
        return '<!doctype html><html lang="ja">' . $html . "</html>";
    }

    protected function doProductDetail($product_id, $tags, $html)
    {
        $product = $this->productRepository->find($product_id);
        if (empty($product)) return $html;
        $updated = false;

        //Replace value for PRODUCT_GROUP
        $tags['star_group']=str_replace('{PRODUCT_GROUP}', $product->getId(), $tags['star_group']);
        $tags['review_group']=str_replace('{PRODUCT_GROUP}', $product->getId(), $tags['review_group']);

        $snippet = str_replace('{PRODUCT_ID}', $product->getCodeMin(), $tags['star_group']);
        $crawler = new Crawler($html);
        $html = $crawler->html();
        if (preg_match('/<ul\s+class=\"ec-productRole__tags">/', $html)) {
            $html = preg_replace('/<ul\s+class=\"ec-productRole__tags">/', $snippet . "\n\$0", $html);
            $updated = true;
        }
        $crawler = new Crawler($html);
        $html = $crawler->html();
        $detail = $crawler->filter('.ec-productRole')->first();
        if ($detail->count() > 0) {
            $old = $detail->html();
            $new = $old . str_replace('{PRODUCT_ID}', $product->getCodeMin(), $tags['review_group']);
            $html = str_replace($old, $new, $html);
            $updated = true;
        }
        if ($updated) {
            return '<!doctype html><html lang="ja">' . html_entity_decode($html) . '</html>';
        } else {
            return $html;
        }

    }


    public function onRenderShoppingComplete(TemplateEvent $event)
    {
        if (!$this->context->isFront()) {
            return;
        }
        if (!$this->isActive()) {
            return;
        }
        $tags = $this->ukomiService->tags();
        /** @var Order $order */
        $order = $event->getParameter('Order');
        $event->addSnippet(
            str_replace(
            ['######ORDER_ID######', '######ORDER_AMOUNT######', '######ORDER_CURRENCY######'],
            [$order->getId(), $order->getTotal(), 'JPY'], $tags['tracking']), false);

    }

    public function onAdminOrderEditIndexComplete(EventArgs $event)
    {
        if (!$this->isActive()) {
            return;
        }
        /** @var Order $origin_order */
        $origin_order = $event->getArgument('OriginOrder');
        /** @var Order $target_order */
        $target_order = $event->getArgument('TargetOrder');

        //BOC if new order then clone target order to original order
        if(empty($origin_order->getId())){
            $origin_order = clone $target_order;
        }
        //EOC if new order then clone target order to original order

        if ($origin_order->getOrderStatus()->getId() == $target_order->getOrderStatus()->getId()) {
            return;
        }
        $detect_order_status = $this->configRepository->getConfig('order_status');
        if ($target_order->getOrderStatus()->getId() == OrderStatus::CANCEL) {
            $this->ukomiService->cancelOrders([$target_order]);
        } else if (!empty($detect_order_status) && $target_order->getOrderStatus()->getId() == $detect_order_status) {
            $this->ukomiService->createOrders([$target_order]);
        }

    }

    public function onOrderCompleted(WorkflowEvent $event) {
        $context = $event->getSubject();
        $Order = $context->getOrder();
        $order_status = $Order->getOrderStatus()->getId();
        $detect_order_status = $this->configRepository->getConfig('order_status');
        if ($order_status == $detect_order_status) {
            $this->ukomiService->createOrders([$Order]);
        }
    }
}
