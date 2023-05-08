<?php

/**
 * @version    1.0.0
 * @package    Com_ZohoZeptoMail
 * @author     Zoho Mail <zmintegration@zohomail.com>
 * @copyright  Copyright © 2023, Zoho Corporation Pvt. Ltd. All Rights Reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\Component\Plugins\Administrator\Model\PluginModel;

use Zoho\Component\ZohoZepto\Administrator\Helpers\SettingsHelper;
require JPATH_BASE . '/components/com_zohozepto/src/Helpers/settings.php';
use Joomla\CMS\Factory;
?>
<head>
    <meta charset="UTF-8">
    <title>ZeptoMail by Zoho Mail</title>
	<?php JHtml::stylesheet('media/com_zohozepto/css/style.css');?>
</head>

<?php


$pluginParams = JComponentHelper::getParams('com_zohozepto');
$appl = JFactory::getApplication();

  if (isset($_POST['zeptomail_submit'])) {

		$need_to_test = 0;
        $zeptomail_send_mail_token = $appl->input->getString('zeptomail_send_mail_token','');
        $zeptomail_bounce_email_address = $appl->input->getString('zeptomail_bounce_email_address','');
        $zeptomail_from_email_id = $appl->input->getString('zeptomail_from_email_id','');
        $zeptomail_domain_name = $appl->input->getString('zeptomail_domain_name','');
        $zeptomail_from_name = $appl->input->getString('zeptomail_from_name','');


		$need_to_test = 0;
		if(strcmp($zeptomail_bounce_email_address, $pluginParams->get('zeptomail_bounce_email_address')) != 0
          || strcmp($zeptomail_from_email_id, $pluginParams->get('zeptomail_from_email_id')) != 0
          || strcmp($zeptomail_send_mail_token, $pluginParams->get('zeptomail_send_mail_token')) != 0) {

            $need_to_test = 1;
		}
		$isPluginEnabled = JPluginHelper::isEnabled('system', 'zeptomailer');
		if(!$isPluginEnabled){
			JFactory::getApplication()->enqueueMessage(JText::_("COM_ZOHOZEPTO_PLUGIN_ENABLE"),'error');
		}else if ( $need_to_test == 1)
		{
			$path = JPATH_ROOT . '/plugins/system/zeptomailer/mailer/mail.php';
			JLoader::register('JMail', $path);
			JLoader::load('JMail');
			$headers = array('Content-Type: text/html; charset=UTF-8');
			$testBody = "<b>Welcome</b>";
			$mailer = JFactory::getMailer();
			$mailer->setSender($zeptomail_from_email_id);
			$mailer->addRecipient($zeptomail_from_email_id,$zeptomail_from_name);
			$mailer->setSubject("Welcome");
			$mailer->setBody($testBody);

			$postData = json_decode("{}", true);
			$postData['zeptomail_send_mail_token'] = $zeptomail_send_mail_token;
			$postData['zeptomail_bounce_email_address'] = $zeptomail_bounce_email_address;
			$postData['zeptomail_from_email_id'] = $zeptomail_from_email_id;
			$postData['zeptomail_from_name'] = $zeptomail_from_name;
			$postData['zeptomail_domain_name'] = $zeptomail_domain_name;

			$mailer->sendmailconfig = $postData;
			$mailSent = $mailer->Send();

			$mailresp = $mailer->getMailerResponse();

			if ($mailSent) {
				SettingsHelper::configure_zeptomail($postData);
				JFactory::getApplication()->enqueueMessage(JTEXT::_('COM_ZOHOZEPTO_CONFIGURE_SUCCESS'),"message");
			} else
			{
				$data = json_decode($mailresp);
				$message_key= 'COM_ZOHOZEPTO_VALID_TOKEN';
				if(!empty($data->error)) {
					if(!empty($data->error->details[0]->message) && strcmp($data->error->details[0]->message,"Invalid API Token found") == 0 ) {
						$message_key = "COM_ZOHOZEPTO_VALID_TOKEN";
					}
					if(!empty($data->error->details[0]->target) && strcmp($data->error->details[0]->target,"bounce_address") == 0 ) {
						$message_key = "COM_ZOHOZEPTO_VALID_BOUNCEADDR";
					}
					if(!empty($data->error->details[0]->target) && strcmp($data->error->details[0]->target,"from") == 0 ) {
						$message_key = "COM_ZOHOZEPTO_VALID_FROMADDR";
					}
				}
				JFactory::getApplication()->enqueueMessage(JTEXT::_($message_key),"error");
			}
		} else {
			JFactory::getApplication()->enqueueMessage(JTEXT::_('COM_ZOHOZEPTO_CONFIGURE_SUCCESS'),"message");
		}

	} else
	{
		$zeptomail_send_mail_token = base64_decode($pluginParams->get('zeptomail_send_mail_token'));
		$zeptomail_bounce_email_address = $pluginParams->get('zeptomail_bounce_email_address');
		$zeptomail_from_email_id = $pluginParams->get('zeptomail_from_email_id');
		$zeptomail_domain_name = $pluginParams->get('zeptomail_domain_name');
		$zeptomail_from_name = $pluginParams->get('zeptomail_from_name');

	}
?>
<body>
<div class="page"><div class="page__content">
            <div class="page__header">
                <h1>Welcome to ZeptoMail by  Zoho Mail.</h1>
                <p>Visit <a class="zm_a" href=<?php echo "https://zeptomail.zoho.com/#dashboard/setupDetail"?> target="_blank">here</a> to generate your Send Mail token.</p>
            </div>


    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">


            <div class="form">
                <div class="form__row">
                    <label class="form--label"><?php echo JText::_('COM_ZOHOZEPTO_LABEL_HOST'); ?></label>
                    <select class="form--input form--input--select" name="zeptomail_domain_name">

                        <option value="com" <?php if($zeptomail_domain_name == "com") {?> selected="true"<?php } ?>>zeptomail.zoho.com</option>
                        <option value="eu" <?php if($zeptomail_domain_name == "eu") {?> selected="true"<?php } ?>>zeptomail.zoho.eu</option>
                        <option value="in" <?php if($zeptomail_domain_name == "in") {?> selected="true"<?php } ?>>zeptomail.zoho.in</option>
                        <option value="com.cn" <?php if($zeptomail_domain_name == "com.cn") {?> selected="true"<?php } ?>>zeptomail.zoho.com.cn</option>
                        <option value="com.au" <?php if($zeptomail_domain_name == "com.au") {?> selected="true"<?php } ?>>zeptomail.zoho.com.au</option>
                    </select> <br><i class="form__row-info"><?php echo JText::_('COM_ZOHOZEPTO_INFO_HOST'); ?></i> </div>
                    <div class="form__row">
                        <label class="form--label"><?php echo JText::_('COM_ZOHOZEPTO_LABEL_TOKEN'); ?></label>
                        <input type="password" value="<?php echo SettingsHelper::zepto_escape($zeptomail_send_mail_token); ?>" name="zeptomail_send_mail_token" class="form--input" id="zeptomail_send_mail_token" required/>
                        <i class="form__row-info"><?php echo JText::_('COM_ZOHOZEPTO_INFO_TOKEN'); ?></i>
                    </div>
                    <div class="form__row">
                        <label class="form--label"><?php echo JText::_('COM_ZOHOZEPTO_LABEL_BOUNCE'); ?></label>
                        <input type="email" value="<?php echo SettingsHelper::zepto_escape($zeptomail_bounce_email_address); ?>" name="zeptomail_bounce_email_address" class="form--input" id="zeptomail_bounce_email_address"  required/>
                        <i class="form__row-info"><?php echo JText::_('COM_ZOHOZEPTO_INFO_BOUNCE'); ?></i>
					</div>
                    <div class="form__row">
						<label class="form--label"><?php echo JText::_('COM_ZOHOZEPTO_LABEL_FROM'); ?></label>
						<input type="text" name="zeptomail_from_email_id" value="<?php echo SettingsHelper::zepto_escape($zeptomail_from_email_id); ?>" class="form--input" id="zeptomail_from_email_id" required/>
						<i class="form__row-info"><?php echo JText::_('COM_ZOHOZEPTO_INFO_FROM'); ?></i>
					</div>
                    <div class="form__row">
						<label class="form--label"><?php echo JText::_('COM_ZOHOZEPTO_LABEL_FROMNAME'); ?></label>
						<input type="text" name="zeptomail_from_name" value="<?php echo SettingsHelper::zepto_escape($zeptomail_from_name); ?>" class="form--input" id="zeptomail_from_name" required/>
						<i class="form__row-info"><?php echo JText::_('COM_ZOHOZEPTO_INFO_FROMNAME'); ?></i>
					</div>


					<div class="form__row form__row-btn">
						<input type="submit" name="zeptomail_submit" id="zeptomail_submit" class="btn" value="<?php echo JText::_('COM_ZOHOZEPTO_LABEL_SAVE'); ?>"/>
                    </div>
                </div>
    </form>

	</div>
	</div>
</body>
