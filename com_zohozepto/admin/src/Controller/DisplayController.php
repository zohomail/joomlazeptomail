<?php
namespace Zoho\Component\ZohoZepto\Administrator\Controller;

defined('_JEXEC') or die;
/**
 * @version    1.0.0
 * @package    Com_ZohoZeptoMail
 * @author     Zoho Mail <zmintegration@zohomail.com>
 * @copyright  Copyright © 2023, Zoho Corporation Pvt. Ltd. All Rights Reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
use Joomla\CMS\MVC\Controller\BaseController;
/**
 * Class AdvertController.
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController {

	protected $default_view = 'settings';
    
    public function display($cachable = false, $urlparams = array()) {
		$view = $this->input->get('view', 'default');
		
        return parent::display($cachable, $urlparams);
    }
    
}
