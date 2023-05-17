<?php
defined('_JEXEC') or die('Restricted access');
/**
 * @version    1.0.0
 * @package    Com_ZohoZeptoMail
 * @author     Zoho Mail <zmintegration@zohomail.com>
 * @copyright  Copyright © 2023, Zoho Corporation Pvt. Ltd. All Rights Reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
 

use Joomla\Component\Plugins\Administrator\Model\PluginModel;

use Zoho\Component\ZohoZeptoMail\Administrator\Helpers\SettingsHelper;
require JPATH_BASE . '/components/com_zohozeptomail/src/Helpers/settings.php';
use Joomla\CMS\Factory;
$appl =JFactory::getApplication();
if (isset($_POST['zeptomail_send_mail_submit'])) {
	$to = $appl->input->getString('zeptomail_to_address','');
	$subject = $_POST['zeptomail_subject'];
	$content = $_POST['zeptomail_content'];
	$isHtml = $appl->input->getBool('mail_type',false);
    $isPluginEnabled = JPluginHelper::isEnabled('system', 'zeptomailer');
    if($isPluginEnabled) {
        SettingsHelper::sendTestMail($to,$subject,$content,$isHtml);
    } else {
		JFactory::getApplication()->enqueueMessage(JText::_("COM_ZOHOZEPTOMAIL_PLUGIN_ENABLE"),'error');
    }
} else {
    $to = '';
    $subject = '';
    $content = '';
    $isHtml = 0;
}

?>
<head>
    <meta charset="UTF-8">
    <title>ZeptoMail by Zoho Mail</title>
	<?php JHtml::stylesheet('media/com_zohozeptomail/css/style.css');?>
</head>

<body>
    
           <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
               
               <body>
                  <div class="page"><div class="page__content">
                      <div class="page__header">
                          <h1><?php echo JText::_('COM_ZOHOZEPTOMAIL_LABEL_SENDMAIL'); ?> <span class="ico-send"></span></h1>
                      </div>
                      <div class="form">
                       <div class="form__row">
                        <label class="form--label"><?php echo JText::_('COM_ZOHOZEPTOMAIL_LABEL_TO'); ?></label>
                        <input type="text" class="form--input" name="zeptomail_to_address" value="<?php echo $to; ?>" required = "required"/> </div>
                        <div class="form__row">
                            <label class="form--label"><?php echo JText::_('COM_ZOHOZEPTOMAIL_LABEL_MAILTYPE'); ?></label>
                            <select class="form--input form--input--select" name="mail_type">
                                <option value="0">Text/Plain</option>
                                <option value="1" <?php if($isHtml == 1) {?> selected="true"<?php } ?>>HTML</option>
                            </select>
                        </div>
                        <div class="form__row">
                            <label class="form--label" ><?php echo JText::_('COM_ZOHOZEPTOMAIL_LABEL_SUBJECT'); ?></label>
                            <input type="text" class="form--input" name="zeptomail_subject" value="<?php echo SettingsHelper::zepto_escape($subject); ?>" required = "required"/> </div>
                            <div class="form__row">
                             <label class="form--label"><?php echo JText::_('COM_ZOHOZEPTOMAIL_LABEL_CONTENT'); ?></label>
                             <textarea class="form--input" name="zeptomail_content" ><?php echo SettingsHelper::zepto_escape($content); ?></textarea> </div>
                             <div class="form__row form__row-btn"> <input type="submit" class = "btn" name="zeptomail_send_mail_submit" id="zeptomail_send_mail_submit" value="<?php echo JText::_('COM_ZOHOZEPTOMAIL_LABEL_SENDMAIL'); ?>">

                             </div>
                         </div>
                     </div>
                 </div>
             </body>
         </form>
</body>
