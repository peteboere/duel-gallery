<?php
/**
 * PendingEmail File Doc Comment
 *
 * @category Collection
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
namespace Duel\Gallery\Model\ResourceModel\PendingEmail;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * PendingEmail Class Doc Comment
 *
 * @category Collection
 * @package  Duel_Gallery
 * @author   Duel <ben@duel.me>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://duel.tech
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor function
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Duel\Gallery\Model\PendingEmail',
            'Duel\Gallery\Model\ResourceModel\PendingEmail'
        );
    }
}
