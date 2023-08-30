<?php
/**
* 2007-2023 PrestaShop
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
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PSpell\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pssentry extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'pssentry';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.0';
        $this->author = 'PickleBoxer';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Sentry Integrations');
        $this->description = $this->l('Sentry is a developer-first error tracking and performance monitoring platform');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        //Configuration::updateValue('PSSENTRY_DEBUG_MODE', false);

        return parent::install() &&
            $this->registerHookAndSetToTop('header') &&
            $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PSSENTRY_DEBUG_MODE');
        Configuration::deleteByName('PSSENTRY_LOADER_SCRIPT');
        Configuration::deleteByName('PSSENTRY_DSN');

        return parent::uninstall();
    }

    /**
     * Register the current module to a given hook and moves it at the first position.
     *
     * @param string $hookName
     *
     * @return bool
     */
    public function registerHookAndSetToTop($hookName)
    {
        return $this->registerHook($hookName) && $this->updatePosition((int) Hook::getIdByName($hookName), false);
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPssentryModule')) == true) {
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
        $helper->submit_action = 'submitPssentryModule';
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
                'title' => $this->l('Sentry SDK Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Debug mode'),
                        'name' => 'PSSENTRY_DEBUG_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Enable or disable Sentry DEBUG mode'),
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
                    array(
                        'type' => 'text',
                        'label' => $this->l('Loader Script'),
                        'name' => 'PSSENTRY_LOADER_SCRIPT',
                        'desc' => $this->l('You can configure the Loader Script to enable/disable Performance, Replay, and more.'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'PSSENTRY_DSN',
                        'label' => $this->l('DSN'),
                        'desc' => $this->l('The DSN tells the SDK where to send the events to. Show deprecated DSN'),
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
            'PSSENTRY_DEBUG_MODE' => Configuration::get('PSSENTRY_DEBUG_MODE'),
            'PSSENTRY_LOADER_SCRIPT' => Configuration::get('PSSENTRY_LOADER_SCRIPT'),
            'PSSENTRY_DSN' => Configuration::get('PSSENTRY_DSN'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($key === 'PSSENTRY_LOADER_SCRIPT') {
                Configuration::updateValue($key, Tools::getValue($key), true);
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    *
    * public function hookDisplayBackOfficeHeader()
    * {
    *     if (Tools::getValue('configure') == $this->name) {
    *         $this->context->controller->addJS($this->_path.'views/js/back.js');
    *         $this->context->controller->addCSS($this->_path.'views/css/back.css');
    *     }
    * }
    */

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->smarty->assign([
            'sentry_dsn' => Configuration::get('PSSENTRY_DSN'),
            'sentry_loader_script' => Configuration::get('PSSENTRY_LOADER_SCRIPT'),
            'sentry_environment' => _PS_MODE_DEV_ ? 'dev' : 'production',
            //'sentry_release' => Configuration::get('SENTRY_RELEASE'),
            'sentry_debug' => Configuration::get('PSSENTRY_DEBUG_MODE')
        ]);

        //$this->context->controller->addJS($this->_path.'/views/js/sentry.js');

        return $this->display(__FILE__, 'header.tpl');
    }
}
