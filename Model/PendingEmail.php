<?php
/**
 * PendingEmail
 *
 * @category PendingEmail
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
namespace Duel\Gallery\Model;

/**
 * PendingEmail
 *
 * @category PendingEmail
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
class PendingEmail extends \Magento\Framework\Model\AbstractModel
{
 
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Duel\Gallery\Model\ResourceModel\PendingEmail');
    }
}
