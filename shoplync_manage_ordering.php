<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Shoplync_manage_ordering extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'shoplync_manage_ordering';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Shoplync';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();
        
        

        $this->displayName = $this->l('Manage Ordering Of Products');
        $this->description = $this->l('A SMS Pro add-on module. Designed to allow store administrators to block customers from ordering a specific product/product combination.');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SHOPLYNC_MANAGE_ORDERING_LIVE_MODE', true);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayProductAdditionalInfo');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SHOPLYNC_MANAGE_ORDERING_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitShoplync_manage_orderingModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShoplync_manage_orderingModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Module'),
                        'name' => 'SHOPLYNC_MANAGE_ORDERING_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('When enabled this module will deny orders for product combination marked as "deny" from within SMS Pro.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SHOPLYNC_MANAGE_ORDERING_LIVE_MODE' => Configuration::get('SHOPLYNC_MANAGE_ORDERING_LIVE_MODE', true),
            'SHOPLYNC_MANAGE_ORDERING_ACCOUNT_EMAIL' => Configuration::get('SHOPLYNC_MANAGE_ORDERING_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'SHOPLYNC_MANAGE_ORDERING_ACCOUNT_PASSWORD' => Configuration::get('SHOPLYNC_MANAGE_ORDERING_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
    
    /*
    SELECT sa.id_stock_available, sa.id_product, sa.id_product_attribute, sa.out_of_stock, sa.quantity, sa.id_shop, pac.id_attribute
    FROM ps_ca_stock_available as sa
    RIGHT JOIN ps_ca_product_attribute_combination as pac
    ON sa.id_product_attribute = pac.id_product_attribute
    WHERE sa.id_product = 8036;
    */
    public function QueryDbWithIdAttribute($id_attribute, $product_id)
    {
        $result = "";
        
        if(isset($product_id))
        {
            $query = 'SELECT sa.id_stock_available, sa.id_product, sa.id_product_attribute, sa.out_of_stock, sa.quantity, sa.id_shop, pac.id_attribute FROM `' 
            . _DB_PREFIX_ . 'stock_available` AS sa LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` AS pac ON sa.id_product_attribute = pac.id_product_attribute ';
            
            if(isset($id_attribute) && $id_attribute > 0)
                $query = $query.'WHERE pac.id_attribute = '.$id_attribute.' AND sa.id_product ='.$product_id.' LIMIT 1;';
            else
                $query = $query.'WHERE sa.id_product ='.$product_id.' LIMIT 1;';
            
            $result = Db::getInstance()->executeS($query);
        }
        return $result;
    }
    
    public function hookDisplayProductAdditionalInfo($params)
    {
        $default = 2;
        $allow = 1;
        $deny = 0;
        $code = '';
        //this query will return result for product, can be used to targetspecific combination
        if(isset($params['product']) && Configuration::get('SHOPLYNC_MANAGE_ORDERING_LIVE_MODE', true))
        {
            $product = new Product($params['product']->getId());
            
            if(isset($product))
            {
                if($product->hasAttributes() > 0)
                {
                    $combination = $params['product']->getAttributes();
                    $combination = array_pop($combination);
                }
                else
                    $combination = array('id_attribute' => 0);

                if(is_array($combination) && array_key_exists('id_attribute', $combination) && isset($combination['id_attribute']))
                {         
                    $result = $this->QueryDbWithIdAttribute($combination['id_attribute'], $product->id);
                    if (!empty($result) && is_array($result))
                    {
                        $result = array_pop($result);
                        if(array_key_exists('out_of_stock', $result) && $result['out_of_stock'] == $deny)
                        {
                            $code = '<script>setTimeout(function() {'
                            .' document.getElementById("product-availability").innerHTML ='
                            .' \'<i class="material-icons rtl-no-flip product-unavailable">block</i> Product Unavailable To Order\';'
                            .' document.querySelector(".add-to-cart").setAttribute("disabled", ""); console.log("This Product Is Unavailable To Order"); }, 100);'
                            .'</script>';
                            
                            if($combination['id_attribute'] == 0)
                                $code = $code.'<script>window.onload = setTimeout(function(){document.querySelector(".add-to-cart").setAttribute("disabled", "");}, 200);</script>';
                        }
                    }                
                }
                return '<!--Product Manage Ordering -->'.$code;
            }
            return '<!--Product Manage Ordering -->';
        }
    }
}
