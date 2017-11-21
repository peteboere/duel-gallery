<?php
/**
 * Index File Doc Comment
 *
 * @category Index
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
namespace Duel\Gallery\Controller\DuelFeed;

use \Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Index Class Doc Comment
 *
 * @category Index
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Construct function to inject necessary arguments
     *
     * @param Context     $context           Context
     * @param PageFactory $resultPageFactory Result Page Factory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        CollectionFactory $collectionFactory,
        ResponseInterface $responseInterface,
        RequestInterface $requestInterface
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->_storeManager = $storeManager;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_collectionFactory = $collectionFactory;
        $this->_responseInterface = $responseInterface;
        $this->_requestInterface = $requestInterface;
    }

    /**
     * Execute function
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $storeUrl = $this->_storeManager->getStore()->getBaseUrl();
        $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $result = $this->resultJsonFactory->create();

        $collection = $this->_collectionFactory->create();
        $collection->addFieldToFilter('duel_feed_enabled', true)
        ->addAttributeToSelect(['entity_id','name','sku','description','product_url','thumbnail','price']);
        
        $productsArray = [];

        if (!empty($collection)) {
            foreach ($collection as $item) {
                $inStock = $this->_stockItemRepository->get($item->getId())->getIsInStock();
                
                $row = [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'description' => strip_tags($item->getShortDescription()),
                    'url' => $item->getProductUrl(),
                    'srcImg' => $storeUrl . 'pub/media/catalog/product' . $item->getData('thumbnail'),
                    'price' => number_format($item->getPrice(), 2),
                    'currency' => $currency,
                    
                ];
                if (!$inStock) {
                    $row['noStock'] = true;
                }
                $productsArray[] = $row;
            }
        }
        $productsObj = (object)[];
        $checksum = md5(json_encode($productsArray));
        $prevChecksum = $this->_requestInterface->getHeader('If-None-Match');

        $productsObj->items = $productsArray;
        
		$response = $this->_responseInterface;
		if ($checksum == $prevChecksum) {
			$response->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_304); 
			$response->setHeader('ETag', $checksum);
		}
		else {
			$response->setHeader('ETag', $checksum);
			return $result->setData($productsObj);
		}

    }
}