<?php
/**
 * 2016-2017 EEWEE
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <prestashop@eewee.fr>
 *  @copyright 2016-2017 EEWEE
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

require_once('libs/sellsy/sellsytools.php');
require_once('libs/sellsy/sellsyconnect_curl.php');

require_once _PS_MODULE_DIR_.'eewee_sellsy/classes/EeweesellsyApiPeopleModel.php';
require_once _PS_MODULE_DIR_.'eewee_sellsy/classes/EeweesellsyApiStaffsModel.php';
require_once _PS_MODULE_DIR_.'eewee_sellsy/classes/EeweesellsyApiSmarttagsModel.php';
require_once _PS_MODULE_DIR_.'eewee_sellsy/classes/EeweesellsyApiSupportModel.php';
require_once _PS_MODULE_DIR_.'eewee_sellsy/classes/EeweesellsyApiErrorModel.php';

/*
// init sellsy
// OK - tbl_sellsy['email']['nom']['prenom']

// check prestashop
// si tbl_sellsy['email_prestashop']['nom_prestashop']['prenom_prestashop'] existe
// update
//	- civilite
//	- age
//	- inscription newsletter
//	- inscription newsletter partenaires
//	- note privée
//	- adresseS
// sinon
// insert donnees prestashop dans sellsy
//	- email
//	- nom
//	- prenom
//	-
//	- civilite
//	- age
//	- inscription newsletter
//	- inscription newsletter partenaires
//	- note privée
//	- adresseS
//	-
//	- SI MODE BTOB PRESTASHOP ACTIVE
//	- ------------------------------
//	- societe
//	- SIRET
//	- APE
//	- Site web
//	- encours autorisé
//	- délai de paiement maximum (en jours)
//	- niveau de risque (faible, moyen, eleve)
*/

/**
 * Class Eewee_Sellsy
 */
class Eewee_Sellsy extends Module implements WidgetInterface
{
	/**
	 * @var string
     */
	protected $html = '';

    public function __construct()
    {
        $this->name = 'eewee_sellsy';
        $this->author = 'eewee';
        $this->tab = 'front_office_features';
        $this->need_instance = 0;
        $this->version = '1.4';
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->_directory = dirname(__FILE__);
        
        parent::__construct();

        $this->displayName = $this->getTranslator()->trans('Module sellsy for PrestaShop v1.7', array(), 'Modules.EeweeSellsy.Admin');
        $this->description = $this->getTranslator()->trans('Structure module type for PrestaShop v1.7', array(), 'Modules.ShareButtons.Admin');

		$this->error = false;
		$this->valid = false;
    }

	/**
	 * Install
	 * @return bool
	 * @throws PrestaShopException
	 */
    public function install()
    {
		require_once(dirname(__FILE__).'/sql/install.php');

    	if (Shop::isFeatureActive())
    		Shop::setContext(Shop::CONTEXT_ALL);
    	
    	if (!parent::install() ||
    		!$this->registerHook('displayHome')   ||
    		!$this->registerHook('displayHeader') ||
            !$this->registerHook('displayFooter') ||

			!$this->registerHook('displayFooterAfter') ||
			!$this->registerHook('displayFooterBefore') ||
			!$this->registerHook('displayFooterProduct') ||
			!$this->registerHook('displayTop') ||

			!Configuration::updateValue('EEWEE_SELLSY_CONSUMER_TOKEN', '') ||
			!Configuration::updateValue('EEWEE_SELLSY_CONSUMER_SECRET', '') ||
			!Configuration::updateValue('EEWEE_SELLSY_UTILISATEUR_TOKEN', '') ||
			!Configuration::updateValue('EEWEE_SELLSY_UTILISATEUR_SECRET', '') ||
			!Configuration::updateValue('EEWEE_SELLSY_SYNC_CONTACT_PRESTASHOP_SELLSY', 1) ||
			!Configuration::updateValue('EEWEE_SELLSY_SUPPORT_DISPLAY_FORM', 1) ||
			!Configuration::updateValue('EEWEE_SELLSY_SUPPORT_ASSIGNEDTO', '') ||
			!Configuration::updateValue('EEWEE_SELLSY_SUPPORT_SUBJECT', '[TICKET SUPPORT] Site internet')
		) {
    		return false;
    	}
    	return true;
    }

	/**
	 * Uninstall
	 * @return bool
	 */
    public function uninstall()
    {
		require_once(dirname(__FILE__).'/sql/uninstall.php');

        if (!parent::uninstall() ||
            !Configuration::deleteByName('EEWEE_SELLSY_CONSUMER_TOKEN') ||
			!Configuration::deleteByName('EEWEE_SELLSY_CONSUMER_SECRET') ||
			!Configuration::deleteByName('EEWEE_SELLSY_UTILISATEUR_TOKEN') ||
			!Configuration::deleteByName('EEWEE_SELLSY_UTILISATEUR_SECRET') ||
			!Configuration::deleteByName('EEWEE_SELLSY_SYNC_CONTACT_PRESTASHOP_SELLSY') ||
			!Configuration::deleteByName('EEWEE_SELLSY_SUPPORT_DISPLAY_FORM') ||
			!Configuration::deleteByName('EEWEE_SELLSY_SUPPORT_ASSIGNEDTO') ||
			!Configuration::deleteByName('EEWEE_SELLSY_SUPPORT_SUBJECT')
        ) {
    		return false;
        }
    	return true;
    }

	/**
	 * Get Sellsy access token
	 * @return bool|mixed token
	 */
	static public function getSellsyAccessToken()
	{
		$res = Configuration::get('EEWEE_SELLSY_UTILISATEUR_TOKEN');
		if (isset($res) && !empty($res)) {
			return $res;
		}
		return false;
	}

	/**
	 * Get Sellsy access token secret
	 * @return bool|mixed token secret
	 */
	static public function getSellsyAccessTokenSecret()
	{
		$res = Configuration::get('EEWEE_SELLSY_UTILISATEUR_SECRET');
		if (isset($res) && !empty($res)) {
			return $res;
		}
		return false;
	}

	/**
	 * Get Sellsy consumer token
	 * @return bool|mixed consumer token
	 */
	static public function getSellsyConsumerToken()
	{
		$res = Configuration::get('EEWEE_SELLSY_CONSUMER_TOKEN');
		if (isset($res) && !empty($res)) {
			return $res;
		}
		return false;
	}

	/**
	 * Get Sellsy consumer secret
	 * @return bool|mixed consumer sercret
	 */
	static public function getSellsyConsumerSecret()
	{
		$res = Configuration::get('EEWEE_SELLSY_CONSUMER_SECRET');
		if (isset($res) && !empty($res)) {
			return $res;
		}
		return false;
	}

	/**
	 * Content
	 * @return string
	 */
    public function getContent()
    {
		$output		= null;
		$renderSync	= "";

		//--------------------------------------------------------------------------------------------------

		// Form submitted
    	if (Tools::isSubmit('submit'.$this->name))
    	{
			$c_consumer_token		= strval(Tools::getValue('EEWEE_SELLSY_CONSUMER_TOKEN'));
			$c_consumer_secret		= strval(Tools::getValue('EEWEE_SELLSY_CONSUMER_SECRET'));
			$c_utilisateur_token	= strval(Tools::getValue('EEWEE_SELLSY_UTILISATEUR_TOKEN'));
			$c_utilisateur_secret	= strval(Tools::getValue('EEWEE_SELLSY_UTILISATEUR_SECRET'));

            if (!$c_consumer_token || empty($c_consumer_token) /*|| !Validate::isGenericName($c_consumer_token)*/) {
				$output .= $this->displayError($this->l('Invalid consumer token'));
			} elseif(!$c_consumer_secret || empty($c_consumer_secret)) {
				$output .= $this->displayError($this->l('Invalid consumer secret'));
			} elseif(!$c_utilisateur_token || empty($c_utilisateur_token)) {
				$output .= $this->displayError($this->l('Invalid user token'));
			} elseif(!$c_utilisateur_secret || empty($c_utilisateur_secret)) {
				$output .= $this->displayError($this->l('Invalid user secret'));
    		} else {
                Configuration::updateValue('EEWEE_SELLSY_CONSUMER_TOKEN', $c_consumer_token);
                Configuration::updateValue('EEWEE_SELLSY_CONSUMER_SECRET', $c_consumer_secret);
                Configuration::updateValue('EEWEE_SELLSY_UTILISATEUR_TOKEN', $c_utilisateur_token);
                Configuration::updateValue('EEWEE_SELLSY_UTILISATEUR_SECRET', $c_utilisateur_secret);

                $output .= $this->displayConfirmation($this->l('Settings updated'));
    		}
    	}

		//--------------------------------------------------------------------------------------------------

		// Informations Sellsy API
		$output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

		//--------------------------------------------------------------------------------------------------

		// SYNC : PrestaShop => Sellsy people
		if (Tools::isSubmit('submit_sync_people'))
		{
			// EXECUTE : synchronisation contact
			$this->syncContactPrestaShopToSellsy();

			$output .= $this->displayConfirmation($this->l('Synchro contact PrestaShop to Sellsy executed.'));
		}

		//--------------------------------------------------------------------------------------------------

		// OPTIONS
		if (Tools::isSubmit('submit_ticket_support'))
		{
			Configuration::updateValue('EEWEE_SELLSY_SUPPORT_DISPLAY_FORM', (int)Tools::getValue('EEWEE_SELLSY_SUPPORT_DISPLAY_FORM'));
			Configuration::updateValue('EEWEE_SELLSY_SUPPORT_SUBJECT', Tools::getValue('EEWEE_SELLSY_SUPPORT_SUBJECT'));
			Configuration::updateValue('EEWEE_SELLSY_SUPPORT_ASSIGNEDTO', (int)Tools::getValue('EEWEE_SELLSY_SUPPORT_ASSIGNEDTO'));

			$output .= $this->displayConfirmation($this->l('Saved options.'));
		}

		//--------------------------------------------------------------------------------------------------

		// Render sync
		$this->context->smarty->assign(array(
			'toto' => 'titi'
			/*
			// Exemple :)
			'help_box' => Configuration::get('PS_HELPBOX'),
			'round_mode' => Configuration::get('PS_PRICE_ROUND_MODE'),
			'brightness' => Tools::getBrightness($bo_color) < 128 ? 'white' : '#383838',
			'bo_width' => (int)$this->context->employee->bo_width,
			'bo_color' => isset($this->context->employee->bo_color) ? Tools::htmlentitiesUTF8($this->context->employee->bo_color) : null,
			'show_new_orders' => Configuration::get('PS_SHOW_NEW_ORDERS') && isset($accesses['AdminOrders']) && $accesses['AdminOrders']['view'],
			'show_new_customers' => Configuration::get('PS_SHOW_NEW_CUSTOMERS') && isset($accesses['AdminCustomers']) && $accesses['AdminCustomers']['view'],
			'show_new_messages' => Configuration::get('PS_SHOW_NEW_MESSAGES') && isset($accesses['AdminCustomerThreads']) && $accesses['AdminCustomerThreads']['view'],
			'employee' => $this->context->employee,
			'search_type' => Tools::getValue('bo_search_type'),
			'bo_query' => Tools::safeOutput(Tools::stripslashes(Tools::getValue('bo_query'))),
			'quick_access' => empty($quick_access) ? false : $quick_access,
			'multi_shop' => Shop::isFeatureActive(),
			'shop_list' => $helperShop->getRenderedShopList(),
			'current_shop_name' => $helperShop->getCurrentShopName(),
			'shop' => $this->context->shop,
			'shop_group' => new ShopGroup((int)Shop::getContextShopGroupID()),
			'is_multishop' => $is_multishop,
			'multishop_context' => $this->multishop_context,
			'default_tab_link' => $this->context->link->getAdminLink(Tab::getClassNameById((int)Context::getContext()->employee->default_tab)),
			'login_link' => $this->context->link->getAdminLink('AdminLogin'),
			'collapse_menu' => isset($this->context->cookie->collapse_menu) ? (int)$this->context->cookie->collapse_menu : 0,
			*/
		));
		$renderSync = $this->context->smarty->fetch($this->local_path.'views/templates/admin/renderSync.tpl');

		//--------------------------------------------------------------------------------------------------

    	return $output.$this->displayForm().$this->displayFormSyncPeople().$this->displayFormOption();
    }

	/**
	 * Synchronisation contact : PrestaShop => Sellsy
	 *
	 * if find => email + lastname + firstname "PrestaShop in Sellsy" = UPDATE
	 * else INSERT
	 */
	public function syncContactPrestaShopToSellsy()
	{
		// INIT
		$m_customer = new Customer();

		// Data Sellsy
		$sellsyPeople = $this->getSellsyPeople();

		// Data PrestaShop
		$prestashopContacts = $this->getPrestashopCustomer();
		foreach ($prestashopContacts as $prestashopContact) {

			// PrestaShop
			$emailPS		= strtolower($prestashopContact['email']);
			$lastnamePS		= strtolower($prestashopContact['lastname']);
			$firstnamePS	= strtolower($prestashopContact['firstname']);
			// Sellsy
			$idPeople		= $sellsyPeople['small'][$emailPS][$lastnamePS][$firstnamePS];

			// PrestaShop contact infos
			$datasPrestashopContact = $m_customer->getCustomersByEmail($emailPS);

			// UPDATE (if data PrestaShop in Sellsy)
			if ($idPeople) {

				foreach ($datasPrestashopContact as $dataPrestaShopContact) {

					//echo 'UPDATE : '.$emailPS.' !!!<br>';

					// INIT
					$tbl_people = array();
					// required
					$tbl_people['name'] = $dataPrestaShopContact['lastname'];
					// optional
					$tbl_people['forename'] = $dataPrestaShopContact['firstname'];
					$tbl_people['email'] = $dataPrestaShopContact['email'];
					//$tbl_people['massmailingUnsubscribed'] = $dataPrestaShopContact['newsletter'];	// IN CLIENT, NOT IN PEOPLE = WTF
					if (isset($dataPrestaShopContact['website']) && !empty($dataPrestaShopContact['website'])) {
						$tbl_people['web'] = $dataPrestaShopContact['website'];
					}
					if ($dataPrestaShopContact['birthday'] != '0000-00-00') {
						$tbl_people['birthdate'] = strtotime($dataPrestaShopContact['birthday']);
					}
					//$tbl_people['tel']	= $dataPrestaShopContact[''];
					//$tbl_people['fax']	= $dataPrestaShopContact[''];
					//$tbl_people['mobile'] = $dataPrestaShopContact[''];
					//$tbl_people['civil']	= $dataPrestaShopContact['id_gender'];

					// UPDATE
					$res = EeweesellsyApiPeopleModel::updateSellsy(array(
						'idPeople'	=> $idPeople,
						'people'	=> $tbl_people,
					));

					// API : error
					if ($res['status'] == 'error') {

						// LOG : error
						$m_eeweeSellsyError				= new EeweesellsyApiErrorModel();
						$m_eeweeSellsyError->date_add	= date('Y-m-d H:i:s');
						$m_eeweeSellsyError->status		= $res['status'];
						$m_eeweeSellsyError->code		= $res['error']->code;
						$m_eeweeSellsyError->message	= $res['error']->message;
						$m_eeweeSellsyError->more		= $res['error']->more;
						$m_eeweeSellsyError->inerror	= $res['error']->inerror;
						$m_eeweeSellsyError->add();
					}

				}//foreach

			// INSERT (if data PrestaShop not in Sellsy)
			} else {

				foreach ($datasPrestashopContact as $dataPrestaShopContact) {

					//echo 'INSERT : '.$emailPS.' !!!<br>';

//					// SOCIETE
//					echo '
//					Societe : <br>
//					- ' . $dataPrestaShopContact['company'] . '<br>
//					- ' . $dataPrestaShopContact['siret'] . '<br>
//					- ' . $dataPrestaShopContact['ape'] . '<br>
//					<br>';
//
//					// PARTICULIER
//					echo '
//					Particulier : <br>
//					- ' . $dataPrestaShopContact['firstname'] . '<br>
//					- ' . $dataPrestaShopContact['lastname'] . '<br>
//					- ' . $dataPrestaShopContact['email'] . '<br>
//					- ' . $dataPrestaShopContact['birthday'] . '<br>
//					- ' . $dataPrestaShopContact['newsletter'] . '<br>
//					- ' . $dataPrestaShopContact['website'] . '<br>
//					- ' . $dataPrestaShopContact['note'] . '<br>
//					<br>';

					// INIT
					$tbl_people = array();
					// required
					$tbl_people['name'] = $dataPrestaShopContact['lastname'];
					// optional
					if (isset($dataPrestaShopContact['firstname']) && !empty($dataPrestaShopContact['firstname'])) {
						$tbl_people['forename'] = $dataPrestaShopContact['firstname'];
					}
					if (isset($dataPrestaShopContact['email']) && !empty($dataPrestaShopContact['email'])) {
						$tbl_people['email'] = $dataPrestaShopContact['email'];
					}
					if (isset($dataPrestaShopContact['website']) && !empty($dataPrestaShopContact['website'])) {
						$tbl_people['web'] = $dataPrestaShopContact['website'];
					}
					if ($dataPrestaShopContact['birthday'] != '0000-00-00') {
						$tbl_people['birthdate'] = strtotime($dataPrestaShopContact['birthday']);
					}

					// INSERT
					$res = EeweesellsyApiPeopleModel::addSellsy(array(
						'people' => $tbl_people,
					));

					// API : error
					if ($res['status'] == 'error') {

						echo 'ERREUR : insert people (email='.$dataPrestaShopContact['email'].')<hr>';

						// LOG : error
						$m_eeweeSellsyError = new EeweesellsyApiErrorModel();
						$m_eeweeSellsyError->date_add = date('Y-m-d H:i:s');
						$m_eeweeSellsyError->status = $res['status'];
						$m_eeweeSellsyError->code = $res['error']->code;
						$m_eeweeSellsyError->message = $res['error']->message;
						$m_eeweeSellsyError->more = $res['error']->more;
						$m_eeweeSellsyError->inerror = $res['error']->inerror;
						$m_eeweeSellsyError->add();
					}

				}//foreach
			}//else
		}//foreach

	}

	/**
	 * get PrestaShop customer
	 * @return array
	 */
	public function getPrestashopCustomer()
	{
		return Customer::getCustomers();
	}
	
	/**
	 * get Sellsy contact
	 * @return object
	 */
	public function getSellsyPeople()
	{
		// INIT
		$nbTotal						= 0;
		$tbl_sellsyForPrestashop		= array();
		$tbl_sellsyForPrestashopTotal	= array();

		// PEOPLE : infos
		$getPeopleInfos = EeweesellsyApiPeopleModel::getPeopleInfos();

		// PEOPLE : for all pages
		for ($i=1; $i<=$getPeopleInfos->nbpages; $i++) {
			$response						= EeweesellsyApiPeopleModel::getPeopleResults(array('pagenum'=>$i));

			$tbl_sellsyForPrestashop		= EeweesellsyApiPeopleModel::dataSellsyForMatchWithPrestashop(array('response'=>$response));
			$tbl_sellsyForPrestashopTotal	= array_merge($tbl_sellsyForPrestashop, $tbl_sellsyForPrestashopTotal);

			// For match
			foreach ($tbl_sellsyForPrestashop as $peopleOne) {
				$id			= $peopleOne['id'];
				$email		= $peopleOne['email'];
				$lastname	= $peopleOne['name'];
				$firstname	= $peopleOne['forename'];

				if (
					isset($email) && !empty($email) &&
					isset($lastname) && !empty($lastname) &&
					isset($firstname) && !empty($firstname)
				){
					$tbl_sellsyForPrestashopTotalSmall[$email][$lastname][$firstname] = $id;
				}
			}

			// People count
			$nbTotal += sizeof( $tbl_sellsyForPrestashop );
		}//for
		//echo 'Total people : '.$nbTotal.'<hr>';

		return array(
			'all'	=> $tbl_sellsyForPrestashopTotal,
			'small'	=> $tbl_sellsyForPrestashopTotalSmall
		);
	}

    /**
     * Create form with helperForm : Add API informations
     * More : http://doc.prestashop.com/display/PS16/Using+the+HelperForm+class#UsingtheHelperFormclass-Selector
     * @return string
     */
    public function displayForm()
    {
        // Get default language
    	$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
    
    	// Init Fields form array
    	$fields_form[0]['form'] = array(
    		'legend' => array(
    			'title' => $this->l('API Sellsy informations'),
    		),
    		'input' => array(
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Consumer token'),
                    'name'     => 'EEWEE_SELLSY_CONSUMER_TOKEN',
                    'required' => true,
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Consumer secret'),
                    'name'     => 'EEWEE_SELLSY_CONSUMER_SECRET',
                    'required' => true,
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('User token'),
                    'name'     => 'EEWEE_SELLSY_UTILISATEUR_TOKEN',
                    'required' => true,
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('User secret'),
                    'name'     => 'EEWEE_SELLSY_UTILISATEUR_SECRET',
                    'required' => true,
                ),
    		),
    		'submit' => array(
    			'title' => $this->l('Save'),
    			'class' => 'btn btn-default pull-right'
    		)
    	);

    	$helper = new HelperForm();
    
    	// Module, token and currentIndex
    	$helper->module = $this;
    	$helper->name_controller = $this->name;
    	$helper->token = Tools::getAdminTokenLite('AdminModules');
    	$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
    
    	// Language
    	$helper->default_form_language = $default_lang;
    	$helper->allow_employee_form_lang = $default_lang;
    
    	// Title and toolbar
    	$helper->title = $this->displayName;
    	$helper->show_toolbar = true;        // false -> remove toolbar
    	$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    	$helper->submit_action = 'submit'.$this->name;
    	$helper->toolbar_btn = array(
    		'save' =>
    		array(
    			'desc' => $this->l('Save'),
    			'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
    			'&token='.Tools::getAdminTokenLite('AdminModules'),
    		),
    		'back' => array(
    			'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
    			'desc' => $this->l('Back to list')
    		)
    	);
        // fields_value 01
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),           // Load current value
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

    	return $helper->generateForm($fields_form);
    }

    /**
     * Load values
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'EEWEE_SELLSY_CONSUMER_TOKEN'		=> Tools::getValue('EEWEE_SELLSY_CONSUMER_TOKEN', Configuration::get('EEWEE_SELLSY_CONSUMER_TOKEN')),
            'EEWEE_SELLSY_CONSUMER_SECRET'		=> Tools::getValue('EEWEE_SELLSY_CONSUMER_SECRET', Configuration::get('EEWEE_SELLSY_CONSUMER_SECRET')),
            'EEWEE_SELLSY_UTILISATEUR_TOKEN'	=> Tools::getValue('EEWEE_SELLSY_UTILISATEUR_TOKEN', Configuration::get('EEWEE_SELLSY_UTILISATEUR_TOKEN')),
            'EEWEE_SELLSY_UTILISATEUR_SECRET'	=> Tools::getValue('EEWEE_SELLSY_UTILISATEUR_SECRET', Configuration::get('EEWEE_SELLSY_UTILISATEUR_SECRET')),
        );
    }


	/**
	 * renderWidget
	 * @param $hookName
	 * @param array $params
	 * @return mixed
	 */
    public function renderWidget($hookName, array $params)
    {
    	$this->smarty->assign($this->getWidgetVariables($hookName, $params));

		switch ($hookName) {
			
			// displayTop
			case "displayTop" :
				$templateType = 'top';
				break;
			
			// displayHome
			case "displayHome" :
				$templateType = 'home';
				break;

			// displayFooterBefore
			case "displayFooterBefore" :
				$templateType = 'footer_before';
				break;

			// displayFooter
			case "displayFooter" :
				$templateType = 'footer';
				$this->context->smarty->assign('EEWEE_SELLSY_DISPLAY_FORM_SUPPORT', Configuration::get('EEWEE_SELLSY_DISPLAY_FORM_SUPPORT'));
				break;

			// displayFooterAfter
			case "displayFooterAfter" :
				$templateType = 'footer_after';
				break;

		}//switch

        // call template
    	return $this->fetch('module:eewee_sellsy/views/templates/hook/eewee_sellsy_'.$templateType.'.tpl');
    }

	/**
	 * getWidgetVariables
	 * @param $hookName
	 * @param array $params
	 * @return array
	 */
    public function getWidgetVariables($hookName, array $params)
    {
		// INIT
		$r = array();

		// displayHome
		if ($hookName == 'displayHome') {

		}

		// displayFooter
		if ($hookName == 'displayFooter') {
			$r = [];
			$r['f_eewee_sellsy_email']   = Tools::getValue('f_eewee_sellsy_email');
			$r['f_eewee_sellsy_name']    = Tools::getValue('f_eewee_sellsy_name');
			$r['f_eewee_sellsy_message'] = Tools::getValue('f_eewee_sellsy_message');
			$r['msg'] = '';

			if (Tools::isSubmit('submitEeweeSellsy')) {
				$this->frontFormRegistration();
				if ($this->error) {
					$r['msg'] = $this->error;
					$r['error'] = true;
				} elseif ($this->valid) {
					$r['msg'] = $this->valid;
					$r['error'] = false;
				}
			}
    	}

		return $r;
    }

	/**
	 * Register form.
	 */
	protected function frontFormRegistration()
	{
		// INIT
		$error = array();

		// ERROR
		if (empty($_POST['f_eewee_sellsy_email']) || !Validate::isEmail($_POST['f_eewee_sellsy_email'])) {
			$error[] = $this->trans('email address', array(), 'Shop.Notifications.Error');
		}
		if (empty($_POST['f_eewee_sellsy_name']) || !Validate::isString($_POST['f_eewee_sellsy_name'])) {
			$error[] = $this->trans('name', array(), 'Shop.Notifications.Error');
		}
		if (empty($_POST['f_eewee_sellsy_message']) || !Validate::isString($_POST['f_eewee_sellsy_message'])) {
			$error[] = $this->trans('message', array(), 'Shop.Notifications.Error');
		}

		if ($error) {
			$this->error = $this->trans('Invalid', array(), 'Shop.Notifications.Error').' '.implode(', ', $error).'.';
			return false;
		}

		// SAVE
		if (!$this->error) {
			$name		= pSQL($_POST['f_eewee_sellsy_name']);
			$email		= pSQL($_POST['f_eewee_sellsy_email']);
			$message	= pSQL($_POST['f_eewee_sellsy_message']);

			// API SELLSY : prospect
			/*
			$request = array(
				'method' => 'Prospects.create',
				'params' => array(
					'third' => array(
						'name'	=> 'nc_prospect_prestashop',
					),
					'contact' => array(
						'name'	=> $name,
						'email'	=> $email,
					)
				)
			);
			*/


			$subject = '[TICKET SUPPORT] Site internet';
			if (Configuration::get('EEWEE_SELLSY_SUPPORT_SUBJECT')) {
				$subject = Configuration::get('EEWEE_SELLSY_SUPPORT_SUBJECT');
			}
			$staffId = Configuration::get('EEWEE_SELLSY_SUPPORT_ASSIGNEDTO');

			// API SELLSY : support
			$response = EeweesellsyApiSupportModel::create(array(
				'subject'			=> $subject,
				'message'			=> "<h2>Client :</h2>".$name."<h2>Message :</h2>".$message,
				'requesterEmail'	=> $email,
				'staffId'			=> $staffId,
			));
			$ticketId = $response->response->ticketid;

			// API SELLSY : tag
			EeweesellsyApiSmarttagsModel::assign(array(
				'linkedtype'=> 'ticket',
				'linkedid'  => $ticketId,
				'tags'      => 'prestashop'
			));

			// API : success
			if ($response->status == 'success') {
				unset($_POST['f_eewee_sellsy_name']);
				unset($_POST['f_eewee_sellsy_email']);
				unset($_POST['f_eewee_sellsy_message']);

				return $this->valid = $this->trans('Successful registration.', array(), 'Shop.Notifications.Error');

			// API : error
			} elseif($response->status == 'error') {

				// LOG : error
				$m_eeweeSellsyError				= new EeweesellsyApiErrorModel();
				$m_eeweeSellsyError->date_add	= date('Y-m-d H:i:s');
				$m_eeweeSellsyError->status		= $response->status;
				$m_eeweeSellsyError->code		= $response->error->code;
				$m_eeweeSellsyError->message	= $response->error->message;
				$m_eeweeSellsyError->more		= $response->error->more;
				$m_eeweeSellsyError->inerror	= $response->error->inerror;
				$m_eeweeSellsyError->add();

				return $this->error = $this->trans('Error :(', array(), 'Shop.Notifications.Error');;
			}
		}
	}


	/**
	 * Create form with helperForm : synchronisation people
	 * @return string
	 */
	public function displayFormSyncPeople()
	{
		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// option (actualy only Yes)
		$options = array(
			array(
				'id_option' => 1,
				'name' => 'Yes'
			),
		);

		// Init Fields form array
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Synchronisation PrestaShop to Sellsy'),
			),
			'input' => array(
				array(
					'type' => 'select',
					'lang' => true,
					'label' => $this->l('Contact PrestaShop to Sellsy'),
					'name' => 'EEWEE_SELLSY_SYNC_CONTACT_PRESTASHOP_SELLSY',
					'desc' => $this->l('Synchronisation only contact "customer" (not business) PrestaShop to Sellsy'),
					'required' => true,
					'options' => array(
						'query' => $options,
						'id' => 'id_option',
						'name' => 'name'
					)
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit_sync_people';
//		$helper->toolbar_btn = array(
//			'save' =>
//				array(
//					'desc' => $this->l('Save'),
//					'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
//						'&token='.Tools::getAdminTokenLite('AdminModules'),
//				),
//			'back' => array(
//				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
//				'desc' => $this->l('Back to list')
//			)
//		);

		$helper->tpl_vars = array(
			'fields_value'	=> array(
				'EEWEE_SELLSY_SYNC_CONTACT_PRESTASHOP_SELLSY' => Tools::getValue('EEWEE_SELLSY_SYNC_CONTACT_PRESTASHOP_SELLSY', Configuration::get('EEWEE_SELLSY_SYNC_CONTACT_PRESTASHOP_SELLSY')),
			), // Load current value
			'languages'		=> $this->context->controller->getLanguages(),
			'id_language'	=> $this->context->language->id
		);

		return $helper->generateForm($fields_form);
	}

	/**
	 * Create form with helperForm : synchronisation people
	 * @return string
	 */
	public function displayFormOption()
	{
		// INIT
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		// option (actualy only Yes)
		$assignedTo = array();
		foreach (EeweesellsyApiStaffsModel::getStaffInfos() as $kStaff=>$vStaff) {
			$assignedTo[] = array(
				'id_option'	=> $kStaff,
				'name'		=> $vStaff
			);
		}

		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Ticket support', 'eewee_sellsy'),
			),
			'input' => array(
				// SWITCH
				array('type' => 'switch',
					'label' => $this->l('Display form support', 'eewee_sellsy'),
					'name' => 'EEWEE_SELLSY_SUPPORT_DISPLAY_FORM',
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => true,
							'label' => $this->l('Enabled', 'eewee_sellsy')
						),
						array(
							'id' => 'active_off',
							'value' => false,
							'label' => $this->l('Disabled', 'eewee_sellsy'),
						)
					),
				),
				// SUBJECT
				array(
					'type'     => 'text',
					'label'    => $this->l('Subject', 'eewee_sellsy'),
					'name'     => 'EEWEE_SELLSY_SUPPORT_SUBJECT',
					'required' => true,
				),
				// SELECT
				array(
					'type' => 'select',
					'lang' => true,
					'label' => $this->l('Assigned to', 'eewee_sellsy'),
					'name' => 'EEWEE_SELLSY_SUPPORT_ASSIGNEDTO',
					'required' => true,
					'options' => array(
						'query' => $assignedTo,
						'id' => 'id_option',
						'name' => 'name'
					)
				),
			),
			'submit' => array(
				'title' => $this->l('Save', 'eewee_sellsy'),
				'class' => 'btn btn-default pull-right'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module				= $this;
		$helper->name_controller	= $this->name;
		$helper->token				= Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex		= AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language		= $default_lang;
		$helper->allow_employee_form_lang 	= $default_lang;

		// Title and toolbar
		$helper->title			= $this->displayName;
		$helper->show_toolbar	= true; // false -> remove toolbar
		$helper->toolbar_scroll	= true; // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action	= 'submit_ticket_support';

		$helper->tpl_vars = array(
			'fields_value'	=> array(
				'EEWEE_SELLSY_SUPPORT_DISPLAY_FORM' => (int)Configuration::get('EEWEE_SELLSY_SUPPORT_DISPLAY_FORM'),
				'EEWEE_SELLSY_SUPPORT_SUBJECT'		=> Configuration::get('EEWEE_SELLSY_SUPPORT_SUBJECT'),
				'EEWEE_SELLSY_SUPPORT_ASSIGNEDTO'	=> (int)Configuration::get('EEWEE_SELLSY_SUPPORT_ASSIGNEDTO'),
			), // Load current value
			'languages'		=> $this->context->controller->getLanguages(),
			'id_language'	=> $this->context->language->id
		);

		return $helper->generateForm($fields_form);
	}
}
