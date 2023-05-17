<?php
/**
 * @package    Com_ZohoZeptoMail
 * @author     Zoho Mail <zmintegration@zohomail.com>
 * @copyright  © 2023, Zoho Corporation Pvt. Ltd. All Rights Reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined( '_JEXEC' ) or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Updates the database structure of the component
 *
 */
class Com_ZohoZeptoMailInstallerScript extends InstallerScript {

	/**
	 * The title of the component (printed on installation and uninstallation messages)
	 *
	 * @var  string
	 */
	protected $extension = 'Zoho Zepto';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var  string
	 */
	protected $minimumJoomla = '4.0';

	/**
	 * Method called before install/update the component
	 * 
	 * Note: This method won't be called during uninstall process
	 *
	 * @param   string  $type    Type of process [install | update]
	 * @param   mixed   $parent  Object who called this method
	 *
	 * @return  boolean  True if the process should continue, false otherwise
	 *
     * @throws  Exception
	 */
	public function preflight( $type, $parent )	{
		$result = parent::preflight( $type, $parent );

		return $result;
	}

	/**
	 * Method to install the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return  void
	 *
	 */
	public function install( $parent ) {	
		$this->installPlugins( $parent );
		if(!$this->isConfigured()) {
		    echo JText::_('COM_ZOHOZEPTOMAIL_NOCONFIG');
		}
	}

	/**
	 * Method to update the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return  void
	 *
	 */
	public function update( $parent ) {	
		$this->installPlugins( $parent );
		if(!$this->isConfigured()) {
		    echo JText::_('COM_ZOHOZEPTOMAIL_NOCONFIG');
		}
	}	

	/**
	 * Method called after install/update the component.
	 * 
	 * @param   string  $type    type
	 * @param   string  $parent  parent
	 *
	 * @return  boolean
	 *
	 */
	public function postflight( $type, $parent ) {
		return true;
	}	

	/**
	 * Method to uninstall the component
	 *
	 * @param   mixed  $parent  Object who called this method.
	 *
	 * @return  void
	 *
	 */
	public function uninstall( $parent ) {
		$this->uninstallPlugins( $parent );
	}


	/**
	 * Installs plugins for this component
	 *
	 * @param   mixed  $parent  Object who called the install/update method
	 *
	 * @return  void
	 *
	 */
	private function installPlugins( $parent ) {
		$installation_folder = $parent->getParent()->getPath( 'source' );
		$app = Factory::getApplication();

		/* @var $plugins SimpleXMLElement */
		if ( method_exists( $parent, 'getManifest' ) ) {
			$plugins = $parent->getManifest()->plugins;
		} else {
			$plugins = $parent->get( 'manifest' )->plugins;
		}

		if ( count( $plugins->children() ) ) {
			$db    = Factory::getDbo();
			$query = $db->getQuery( true );

			foreach ( $plugins->children() as $plugin )	{
				$pluginName  = (string) $plugin['plugin'];
				$pluginGroup = (string) $plugin['group'];
				$path        = $installation_folder . '/plugins/' . $pluginGroup . '/' . $pluginName;
				$installer   = new Installer;

				if ( ! $this->isAlreadyInstalled( 'plugin', $pluginName, $pluginGroup ) ) {
					$result = $installer->install( $path );
				} else {
					$result = $installer->update( $path );
				}

				if ( $result ) {
					$app->enqueueMessage( 
						Text::sprintf(
							'COM_ZOHOZEPTOMAIL_PLUGIN_SUCCESS',
							$pluginGroup, 
							$pluginName
						)
					);
				} else {
					$app->enqueueMessage(
						Text::sprintf(
							'COM_ZOHOZEPTOMAIL_PLUGIN_FAILURE',
							$pluginGroup,
							$pluginName
						),
						'error'
					);
				}

				$query
					->clear()
					->update( '#__extensions' )
					->set( 'enabled = 1' )
					->where(
						array(
							'type LIKE ' . $db->quote( 'plugin' ),
							'element LIKE ' . $db->quote( $pluginName ),
							'folder LIKE ' . $db->quote( $pluginGroup )
						)
					);
				$db->setQuery( $query );
				$db->execute();
			}
		}
	}	

	

	/**
	 * Uninstalls plugins
	 *
	 * @param   mixed  $parent  Object who called the uninstall method
	 *
	 * @return  void
	 *
	 */
	private function uninstallPlugins( $parent ) {
		$app = Factory::getApplication();

		if ( method_exists( $parent, 'getManifest' ) ) {
			$plugins = $parent->getManifest()->plugins;
		} else {
			$plugins = $parent->get( 'manifest' )->plugins;
		}

		if ( count( $plugins->children() ) ) {
			$db    = Factory::getDbo();
			$query = $db->getQuery( true );

			foreach ( $plugins->children() as $plugin )	{
				$pluginName  = (string) $plugin['plugin'];
				$pluginGroup = (string) $plugin['group'];
				$query
					->clear()
					->select( 'extension_id' )
					->from( '#__extensions' )
					->where(
						array(
							'type LIKE ' . $db->quote( 'plugin' ),
							'element LIKE ' . $db->quote( $pluginName ),
							'folder LIKE ' . $db->quote( $pluginGroup )
						)
					);
				$db->setQuery( $query );
				$extension = $db->loadResult();

				if ( ! empty( $extension ) ) {
					$installer = new Installer;
					$result    = $installer->uninstall( 'plugin', $extension );

					if ( $result ) {
						$app->enqueueMessage( 
							Text::sprintf(
								'COM_ZOHOZEPTOMAIL_PLUGIN_UNINSTALL_SUCCESS',
								$pluginGroup,
								$pluginName
							)
						 );
					} else {
						$app->enqueueMessage(
							Text::sprintf(
								'COM_ZOHOZEPTOMAIL_PLUGIN_UNINSTALL_FAILURE',
								$pluginGroup,
								$pluginName
							),
							'error'
						);
					}
				}
			}
		}
	}


	

	/**
	 * Check if an extension is already installed in the system
	 *
	 * @param   string  $type    Extension type
	 * @param   string  $name    Extension name
	 * @param   mixed   $folder  Extension folder(for plugins)
	 *
	 * @return  boolean
	 *
	 */
	private function isAlreadyInstalled( $type, $name, $folder = null )	{
		$result = false;
		
		if($type == 'plugin') {
			$result = file_exists( JPATH_PLUGINS . '/' . $folder . '/' . $name );
		}

		return $result;
	}

	
    private function isConfigured()
    {
        $params = JComponentHelper::getParams('com_zohozeptomail');
        return $params && !empty($params->get('zeptomail_from_email_id'));
    }

	
}
