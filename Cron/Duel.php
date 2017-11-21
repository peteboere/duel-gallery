<?php
/**
 * Duel File Doc Comment
 *
 * @category Duel
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
namespace Duel\Gallery\Cron;

/**
 * Duel Class Doc Comment
 *
 * @category Duel
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
class Duel
{

    private $pendingEmailFactory;

    /**
     * Construct function to inject necessary arguments
     *
     * @param DateTime              $date                Date
     * @param TransportBuilder      $transportBuilder    Transport Builder
     * @param StoreManagerInterface $storeManager        Store Manager
     * @param Order                 $_order              Order
     * @param ScopeConfigInterface  $scopeConfig         Scope Config
     * @param PendingEmailFactory   $pendingEmailFactory Pending Email Factory
     * @param ProductRepository     $productRepository   Product Factory
     * @param array                 $data                Data
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $_order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Duel\Gallery\Model\PendingEmailFactory $pendingEmailFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        array $data = []
    ) {
        $this->_date = $date;
        $this->transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->_order = $_order;
        $this->_scopeConfig = $scopeConfig;
        $this->pendingEmailFactory = $pendingEmailFactory;
        $this->productRepository = $productRepository;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
    }

    /**
     * Execute function
     *
     * @return void
     */
    public function execute()
    {
        $dateNow = strtotime($this->_date->gmtDate());

        $placeholder = $this->_scopeConfig
        ->getValue('catalog/placeholder/thumbnail_placeholder');
        $emailsEnabled = $this->_scopeConfig
        ->getValue('settings/emails/duel_email_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $delay = $this->_scopeConfig
        ->getValue('settings/emails/duel_email_delay', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $brandId = $this->_scopeConfig
        ->getValue('settings/emails/duel_brand_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($emailsEnabled == 0 or !($brandId)) {
            return;
        }

        $config = [
            'templateId' => $this->_scopeConfig
            ->getValue('settings/emails/duel_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'storeEmail' => $this->_scopeConfig
            ->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'storeName'  => $this->_storeManager->getStore()->getName(),
            'brandId' => $brandId,

        ];

        // 5 days is the recommended default value
        if (!isset($delay)) {
            $delay = 5;
        } else {
            $delay = 5 - $delay;
        }
        
        $pendingEmail = $this->pendingEmailFactory->create();
        $collection = $pendingEmail->getCollection()->getData();

        foreach ($collection as $key => $value) {
            $createdAt = strtotime($value['created_at']);
            $delayInSeconds = 86400 * $delay;
            
            if (($createdAt + $delayInSeconds) < $dateNow) {
                $value['overdue'] = true;
            } else {
                $value['overdue'] = false;
            }

            $_order = $this->_order->load($value['order_id']);
            $_items = $_order->getAllItems();

            $customerEmail = $_order->getCustomerEmail();

            $orderItems = $this->_prepareItems($_items);

            if (!empty($orderItems)) {
                $this->_sendDuelEmail($value['order_id'], $orderItems, $customerEmail, $config);
            }
            $current = $pendingEmail->load($value['entity_id'], 'entity_id');
            // $current->delete();
        }
    }

    private function _prepareItems($items)
    {
        $storeUrl = $this->_storeManager->getStore()->getBaseUrl();
        $orderItems = [];
        foreach ($items as $_item) {
            if (null !== $_item->getProduct()) {
                $itemType = $_item->getProduct()->getData('type_id');
                $productId = $_item->getProduct()->getData('entity_id');
                $itemEmailEnabled = $_item->getProduct()->getData('duel_email_enabled');
                
                if ($itemType == 'virtual' or $itemType == 'configurable') {
                    $qtyNotRefunded = $_item->getQtyOrdered() - $_item->getQtyRefunded();
                } else {
                    $qtyNotRefunded = $_item->getQtyShipped() - $_item->getQtyRefunded();
                }

                $parentByChild = $this->catalogProductTypeConfigurable->getParentIdsByChild($productId);
                
                if ($parentByChild) {
                    $parentId = $parentByChild[0];
                    $sku = $this->productRepository->getById($parentId)->getData('sku');
                } else {
                    $sku = $_item->getSku();
                }
                
                if ($itemEmailEnabled and $qtyNotRefunded) {
                    $productThumbnail = $_item->getProduct()->getThumbnail();
                    if ($productThumbnail == 'no_selection') {
                        $thumbnail = ($placeholder == null) ? "" : $placeholder;
                    } else {
                        $thumbnail = $storeUrl . 'pub/media/catalog/product' . $productThumbnail;
                    }
                    $item = [
                        'product_id' => $productId,
                        'qtyOrdered' => $_item->getQtyOrdered(),
                        'qtyNotRefunded' => $qtyNotRefunded,
                        'type' => $itemType,
                        'sku' => $sku,
                        'item_id' => $productId,
                        'price' => $_item->getPrice(),
                        'thumbnail' => $thumbnail,
                        'name' => $_item->getName(),
                    ];
                    array_push($orderItems, $item);
                }
            }
        }
        return $orderItems;
    }

    /**
     * Send the post-purchase email for this order
     *
     * @param string $orderId       Order Id
     * @param array  $orderItems    Order Items
     * @param string $customerEmail Customer Email
     * @param array  $config        Config
     *
     * @return void
     */
    private function _sendDuelEmail($orderId, $orderItems, $customerEmail, $config)
    {
        $emailData = [];

        $shipmentData = [];
        $shipmentData['order_id'] = $orderId;

        $shipmentData['orderItems'] = $orderItems;
        $shipmentData['brandId'] = $config['brandId'];

        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData($shipmentData);

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($config['templateId'])
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ])
            ->setTemplateVars(['data' => $postObject])
            ->setFrom(['email' => $config['storeEmail'], 'name' => $config['storeName']])
            ->addTo([$customerEmail])
            ->getTransport();
        $transport->sendMessage();
    }
}
