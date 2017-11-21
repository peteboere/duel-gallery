<?php
/**
 * Gallery File Doc Comment
 *
 * @category Gallery
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
namespace Duel\Gallery\Block;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;

/**
 * Gallery Class Doc Comment
 *
 * @category Gallery
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
class Gallery extends Template
{
    /**
     * The product
     *
     * @var Product
     */
    private $product;

    /**
     * Construct function to inject necessary arguments
     *
     * @param BlockFactory $blockFactory Block Factory
     * @param Context      $context      Context
     * @param Registry     $registry     Registry
     * @param array        $data         Data
     */
    public function __construct(
        \Magento\Cms\Model\BlockFactory $blockFactory,
        Template\Context $context,
        Registry $registry,
        array $data
    ) {
        $this->_blockFactory = $blockFactory;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }
    
    /**
     * Gets the product for this product page if available
     *
     * @return Product
     */
    private function getProduct()
    {
        if ($this->product === null) {
            $this->product = $this->registry->registry('product');

            if (!$this->product->getId()) {
                throw new LocalizedException(__('Failed to initialize product'));
            }
        }

        return $this->product;
    }

    /**
     * Gets the gallery info for this product and returns it to the calling template file
     *
     * @return $gallery
     */
    public function getGallery()
    {
        $duelDefaults = [];

        $availableBlocks = $this->_blockFactory->create()->getCollection();

        $showGalleries = $this->_scopeConfig
        ->getValue('settings/defaults/show_galleries', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $showGallery = $this->getProduct()->getData('duel_is_active');
        $result = [];

        if ($showGalleries == false || $showGallery == false) {
            $result['active'] = false;
            return json_encode($result);
        } else {
            $result['active'] = true;
        }

        $duelDefaults['colour'] = $this->_scopeConfig
        ->getValue('settings/defaults/duel_colour', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $duelDefaults['background_colour'] = $this->_scopeConfig
        ->getValue('settings/defaults/duel_background_colour', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $duelDefaultRows = $this->_scopeConfig
        ->getValue('settings/defaults/duel_rows', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $duelDefaultColumns = $this->_scopeConfig
        ->getValue('settings/defaults/duel_columns', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $result['default_page_position'] = $this->_scopeConfig
        ->getValue('settings/defaults/duel_page_position', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $result['default_page_position_custom'] = $this->_scopeConfig
        ->getValue('settings/defaults/duel_page_selector', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $duelBrandId = $this->_scopeConfig
        ->getValue('settings/emails/duel_brand_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $result['gallery_id'] = $this->getProduct()->getData('duel_gallery_id');
        if ($result['gallery_id'] == 'No gallery selected') {
            $result['gallery_id'] = null;
        }
        
        $result['duel_product'] = $duelBrandId . '/' . $this->getProduct()->getData('sku');
        $result['colour'] = $this->getProduct()->getData('duel_colour');
        $result['background_colour'] = $this->getProduct()->getData('duel_background_colour');
        $result['rows'] = $this->getProduct()->getData('duel_rows');
        $result['columns'] = $this->getProduct()->getData('duel_columns');
        $result['page_position'] = $this->getProduct()->getData('duel_page_position');
        $result['page_position_custom'] = $this->getProduct()->getData('duel_page_position_custom');
        
        if ($result['page_position_custom'] == 'N/A') {
            $result['page_position_custom'] = null;
        }

        if (empty($result['rows']) or $result['rows'] == 0) {
            $result['rows'] = ($duelDefaultRows == 0 or empty($duelDefaultRows)) ? 15 : $duelDefaultRows;
        }
        if (empty($result['columns']) or $result['columns'] == 0) {
            $result['columns'] = ($duelDefaultColumns == 0 or empty($duelDefaultColumns)) ? 3 : $duelDefaultColumns;
        }
        if (empty($result['colour']) or $result['colour'] == 'Use default') {
            $result['colour'] = empty($duelDefaults['colour']) ? "#000000" : $duelDefaults['colour'];
        }
        if (empty($result['background_colour']) or $result['background_colour'] == 'Use default') {
            $result['background_colour'] = empty($duelDefaults['background_colour'])
            ? "#ffffff"
            : $duelDefaults['background_colour'];
        }
        
        $gallery = json_encode($result);

        return $gallery;
    }
}
