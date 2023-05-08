<?php
/**
 * @version    1.0.0
 * @package    Com_ZohoZeptoMail
 * @author     Zoho Mail <zmintegration@zohomail.com>
 * @copyright  Copyright © 2023, Zoho Corporation Pvt. Ltd. All Rights Reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');

class PlgSystemZeptoMailer extends JPlugin {

    /**
     * Here we will override the JMail class
     *
     * @return bool
     */
    public function onAfterInitialise() {
        
        $pluginParams = JComponentHelper::getParams('com_zohozepto');
		if(!$pluginParams) {
			return false;
		} else if(empty($pluginParams->get('zeptomail_send_mail_token'))){
			return false;
		}
        $path = JPATH_ROOT . '/plugins/system/zeptomailer/mailer/mail.php';
        JLoader::register('JMail', $path);
        JLoader::load('JMail');
		
	}
}
 



