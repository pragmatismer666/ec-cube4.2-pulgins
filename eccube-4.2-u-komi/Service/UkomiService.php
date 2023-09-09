<?php


namespace Plugin\UKOMI4\Service;


use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use GuzzleHttp\Client;
use Plugin\UKOMI4\Repository\ConfigRepository;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UkomiService
{
    const DEBUG = false;

    /**
     * @var ContainerInterface
     */
    private $app;

    /**
     * @var Packages
     */
    private $packages;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var string|null
     */
    private $api_key;

    /**
     * @var string|null
     */
    private $api_secret;

    /**
     * @var bool
     */
    private $api_key_loaded = false;

    /**
     * @var string|bool
     */
    private $access_token = false;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array|null
     */
    private $tags;

    public function __construct(ContainerInterface $container, Packages $packages, RequestStack  $request, ConfigRepository $configRepository, UrlGeneratorInterface $router, EccubeConfig $eccubeConfig)
    {
        $this->app = $container;
        $this->packages = $packages;
        $this->configRepository = $configRepository;
        $this->router = $router;
        $this->request = $request->getCurrentRequest();
        $this->eccubeConfig = $eccubeConfig;
    }

    public function flush()
    {
        $this->api_key = $this->configRepository->getConfig('api_key');
        $this->api_secret = $this->configRepository->getConfig('api_secret');
        $this->api_key_loaded = true;
        return $this;
    }

    public function isKeyLoaded()
    {
        return $this->api_key_loaded;
    }

    public function getApiKey()
    {
        if (!$this->isKeyLoaded()) {
            $this->flush();
        }
        return $this->api_key;
    }

    public function getApiSecret()
    {
        if (!$this->isKeyLoaded()) {
            $this->flush();
        }
        return $this->api_secret;
    }

    public function getBaseUrl()
    {
        return '//api.u-komi.com';
    }

    public function getJsUrl()
    {
        return $this->getBaseUrl();
    }

    public function getApiUrl($endpoint)
    {
        return 'https:' . $this->getBaseUrl() . '/' . ltrim($endpoint, '/');
    }


    /**
     * @return Client
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
        }
        return $this->client;
    }

    protected $error_code;
    protected $error_message;
    protected $input_errors;
    protected function setError($code, $message=null, $errors=null)
    {
        $this->error_code = $code;
        $this->error_message = $message;
        $this->input_errors = $errors;
    }

    public function getErrorCode()
    {
        return $this->error_code;
    }

    public function getErrorMessage()
    {
        return $this->error_message;
    }

    public function getInputErrors()
    {
        return $this->input_errors;
    }

    /**
     * @param ResponseInterface $response
     * @return bool|mixed
     */
    protected function parseResponse($response)
    {
        //if ($response->getStatusCode())
        $result = @json_decode((string)$response->getBody(), true);
        if (!$result || !isset($result['status'])) {
            $this->setError(-1, 'Invalid request.');
            return false;
        }
        if ($result['status'] != 'OK') {
            $this->setError($result['code'], $result['message'], !empty($result['errors']) ? $result['errors'] : null);
            return false;
        }
        return $result;
    }

    public function getAccessToken()
    {
        if ($this->access_token === false) {
            $response = $this->getClient()
                ->post(
                    $this->getApiUrl('/auth/access_token'), array('verify' => false, 'form_params' => array(
                    'api_key' => $this->getApiKey(),
                    'api_secret' => $this->getApiSecret()
                )));
            if ($result = $this->parseResponse($response)) {
                $this->access_token = $result['data']['access_token'];
            } else {
                $this->access_token = null;
            }

        }
        if ($this->access_token == null && $this->getErrorCode() == null) {
            $this->setError(-1, 'Can not get access token.');
        }
        return $this->access_token;
    }

    public function createOrders($orders)
    {
        if (empty($orders)) {
            return 0;
        }
        $token = $this->getAccessToken();
        if (empty($token)) {
            return false;
        }

        $data = array('access_token' => $this->getAccessToken(), 'orders' => array());
        $order_ids = [];
        foreach($orders as $order) {
            /** @var Order $order */
            $products = array();
            foreach($order->getOrderItems() as $orderItem) {
                /** @var OrderItem $orderItem */
                $product = $orderItem->getProduct();
                if (empty($product)) continue;
                $specs = [];
                if ($orderItem->getClassName1() != '') {
                    $specs[$orderItem->getClassName1()] = $orderItem->getClassCategoryName1();
                }
                if ($orderItem->getClassName2() != '') {
                    $specs[$orderItem->getClassName2()] = $orderItem->getClassCategoryName2();
                }
                $photo = $product && $product->getMainListImage() ?
                    $product->getMainListImage() : 'no_image_product.jpg';

                $product_id = $product->getId();
                $item = [
                    'url' => $this->router->generate('product_detail', ['id' => $product_id], UrlGeneratorInterface::ABSOLUTE_URL),
                    'name' => $orderItem->getProductName(),
                    'image' => $this->request->getSchemeAndHttpHost() . $this->packages->getUrl($photo, 'save_image'),
                    'description' => $product->getDescriptionDetail(),
                    'group_name' => $product->getId(),
                    'price' => $orderItem->getPrice(),
                ];
                if (!empty($specs)) {
                    $item['specs'] = (object)$specs;
                }
                $products[$orderItem->getProductCode()] = $item;
            }
            $order_ids[] = $order->getId();
            $data['orders'][] = (object)[
                'customer_email' => $order->getEmail(),
                'customer_name' => $order->getName01() . " " . $order->getName02(),
                'order_id' => $order->getId(),
                //'order_date' => $order->getOrderDate()->format('Y-m-d'),
                'order_date' => $order->getUpdateDate()->format('Y-m-d'),
                'currency_iso' => 'JPY',
                'products' => (object)$products
            ];
        }
        if (self::DEBUG) {
            log_info('U-komi: Sending orders - ' . implode(',', $order_ids) );
        }
        $response = $this->getClient()->post($this->getApiUrl('orders/' . $this->getApiKey() . '/create'), array('verify' => false, 'json' => $data));
        if ($result = $this->parseResponse($response)) {
            if (self::DEBUG) {
                log_info('U-komi: orders sent', ['response' => (string)$response->getBody()]);
            }
            return true;
        } else {
            return false;
        }

    }

    public function cancelOrders($orders)
    {
        if (empty($orders)) {
            return 0;
        }
        $token = $this->getAccessToken();
        if (empty($token)) {
            return false;
        }

        $order_ids = [];
        $data = array('access_token' => $this->getAccessToken(), 'orders' => array());
        foreach($orders as $order) {
            /** @var Order $order */
            $products = array();
            foreach($order->getOrderItems() as $orderItem) {
                /** @var OrderItem $orderItem */
                $products[] = $orderItem->getProductCode();
            }
            if (!empty($products)) {
                $data['orders'][] = (object)[
                    'order_id' => $order->getId(),
                    'product_ids' => $products
                ];
            } else {
                $data['orders'][] = (object)[
                    'order_id' => $order->getId(),
                ];
            }
            $order_ids[] = $order->getId();
        }
        if (self::DEBUG) {
            log_info('U-komi: Canceling orders - ' . implode(',', $order_ids) );
        }
        $response = $this->getClient()->post($this->getApiUrl('orders/' . $this->getApiKey() . '/cancel'), array('verify' => false, 'json' => $data));
        if ($result = $this->parseResponse($response)) {
            if (self::DEBUG) {
                log_info('U-komi: orders canceled', ['response' => (string)$response->getBody()]);
            }
            return true;
        } else {
            return false;
        }

    }

    public function tags()
    {
        if (!$this->tags) {
            $api_key = $this->configRepository->getConfig('api_key', '{API_KEY}');

            $tags = array(
                'head' => '',
                'star_unit' => '',
                'star_group' => '',
                'review_unit' => '',
                'review_group' => '',
                'review_page' => '',
                'tracking' => '',
            );
            $base = $this->getJsUrl();
            //
            $tags['head'] =
                <<<EOF
<script type="text/javascript">
(function u(){var u=document.createElement("script");u.type="text/javascript",u.async=true,u.src="{$base}/{$api_key}/widget.js";var k=document.getElementsByTagName("script")[0];k.parentNode.insertBefore(u,k)})();
</script>
EOF;

            $tags['star_unit'] =
                <<<EOF
<div class="review-summary-container" data-pid="{PRODUCT_ID}" data-action="summary"></div>
EOF;
            $tags['star_group'] =
                <<<EOF
<div class="review-summary-container" data-pid="{PRODUCT_ID}" data-group="true" data-gname="{PRODUCT_GROUP}" data-action="summary"></div>
EOF;

            $tags['review_unit'] =
                <<<EOF
<div class="review-container" data-pid="{PRODUCT_ID}" data-action="widget"></div>
EOF;
            $tags['review_group'] =
                <<<EOF
<div class="review-container" data-pid="{PRODUCT_ID}" data-group="true" data-gname="{PRODUCT_GROUP}" data-action="widget"></div>
EOF;

            $tags['review_page'] =
                <<<EOF
<div class="review-container" data-action="dedicated-widget"></div>
EOF;
            $tags['tracking'] =
                <<<EOF
<script>ukomiOrderTrackData = {orderId: "######ORDER_ID######", orderAmount: "######ORDER_AMOUNT######", orderCurrency: "######ORDER_CURRENCY######"}</script>
EOF;
            $this->tags = $tags;
        }
        return $this->tags;
    }



}
