<?php
/**
 * @version    1.0.0
 * @package    Com_ZohoZeptoMail
 * @author     Zoho Mail <zmintegration@zohomail.com>
 * @copyright  Copyright © 2023, Zoho Corporation Pvt. Ltd. All Rights Reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Zoho\Component\ZohoZeptoMail\Administrator\Helpers;
use Joomla\CMS\Factory;
use Joomla\Component\Plugins\Administrator\Model\PluginModel;
use Joomla\CMS\Language\Text;
defined('_JEXEC') or die('Restricted access');



class SettingsHelper {

	public static function configure_zeptomail($postData) {
		$appl = \JFactory::getApplication();
		$params = \JComponentHelper::getParams('com_zohozeptomail');
		$input = $appl->input;
		$data = json_encode((array)$params);
		$db = \JFactory::getDbo();
		$prefix = $db->getPrefix();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array(
        'extension_id',
        'name',
        'params'
		)));
    $query->from($db->quoteName($prefix . 'extensions'));
    $query->where($db->quoteName('name') . ' = \'Zoho ZeptoMail\'');
	$db->setQuery($query);
	$results = $db->loadObjectList();
    $db_params = json_decode($results[0]->params, true);
	
	$db_params['zeptomail_send_mail_token'] = base64_encode($postData['zeptomail_send_mail_token']);
	$db_params['zeptomail_bounce_email_address'] = $postData['zeptomail_bounce_email_address'];
	$db_params['zeptomail_from_email_id'] = $postData['zeptomail_from_email_id'];
	$db_params['zeptomail_from_name'] = $postData['zeptomail_from_name'];
	$db_params['zeptomail_domain_name'] = $postData['zeptomail_domain_name'];

    $db_params = json_encode($db_params);
	
    $query = $db->getQuery(true);
    $query = $db->getQuery(true);
    $query->clear();

    $fields = array(
        $db->quoteName('params') . ' = ' . $db->quote($db_params)
    );
    $conditions = array(
        $db->quoteName('name') . ' = ' . $db->quote("Zoho ZeptoMail")
    );

    $query->update($db->quoteName($prefix . 'extensions'))
        ->set($fields)
        ->where($conditions);

    $db->setQuery($query);

    try {
        $db->execute();
    } catch (RuntimeException $e) {
        $appl->enqueueMessage($e->getMessage(),'error');
    }
	
	return true;
		
	}
	
	public static function sendTestMail($to,$subject,$content,$mail_type) {
		$appl = \JFactory::getApplication();
		$mailer = \JFactory::getMailer();
		$pluginParams = \JComponentHelper::getParams('com_zohozeptomail');

		if(empty($pluginParams->get('zeptomail_send_mail_token'))){
		    $appl->enqueueMessage(Text::_('COM_ZOHOZEPTOMAIL_NOCONFIG'),'error');
		    return false;
		}
		
		if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
          $appl->enqueueMessage(Text::_('COM_ZOHOZEPTOMAIL_ENTER_RECIPIENT'),'error');
          return false;
        }
        if(empty(trim($subject))) {
            $appl->enqueueMessage(Text::_("COM_ZOHOZEPTOMAIL_SPECIFY_SUBJECT"),'error');
            return false;
        }
		$mailer->addRecipient($to);
		if(empty(trim($content))) {
		    $appl->enqueueMessage(Text::_("COM_ZOHOZEPTOMAIL_SPECIFY_CONTENT"),'error');
		    return false;
		}
		$mailer->setBody($content);

		$mailer->setSubject($subject);
		if($mail_type == 1) {
		    $mailer->isHtml();
		}
		if($mailer->Send()){
			$appl->enqueueMessage(Text::_("COM_ZOHOZEPTOMAIL_MAIL_SENT"),'message');
			return true;
		}
		$appl->enqueueMessage(Text::_("COM_ZOHOZEPTOMAIL_MAIL_NOT_SENT"),'error');
		return false;
	}
	
	public static function zepto_escape($text)
	{
		if(!empty($text)) {
			return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
		}else {
			return $text;
		}
		
	}


	
}

