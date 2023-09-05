<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
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

        /*
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Sentry Integrations');
        $this->description = $this->l('Sentry is a developer-first error tracking and performance monitoring platform');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        // the following code will test if an uncaught exception logs to sentry
        // try {
        //    $this->functionFailsForSure();
        // } catch (\Throwable $exception) {
        //    \Sentry\captureException($exception);
        // }
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        // Configuration::updateValue('PSSENTRY_DEBUG_MODE', false);

        return parent::install()
            && $this->registerHookAndSetToTop('header')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayNavFullWidth');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PSSENTRY_DEBUG_MODE');
        Configuration::deleteByName('PSSENTRY_LOADER_SCRIPT');
        Configuration::deleteByName('PSSENTRY_DSN');

        $this->updatePssentryCodeInDefinesCustomFile(true);
        $this->updatePssentryCodeInConfigFile(true);

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
        /*
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitPssentryModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
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
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Sentry SDK Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Debug mode'),
                        'name' => 'PSSENTRY_DEBUG_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Enable or disable Sentry DEBUG mode'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Loader Script'),
                        'name' => 'PSSENTRY_LOADER_SCRIPT',
                        'desc' => $this->l('You can configure the Loader Script to enable/disable Performance, Replay, and more.'),
                    ],
                    [
                        'type' => 'text',
                        'name' => 'PSSENTRY_DSN',
                        'label' => $this->l('DSN'),
                        'desc' => $this->l('The DSN tells the SDK where to send the events to. Show deprecated DSN'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            'PSSENTRY_DEBUG_MODE' => Configuration::get('PSSENTRY_DEBUG_MODE'),
            'PSSENTRY_LOADER_SCRIPT' => Configuration::get('PSSENTRY_LOADER_SCRIPT'),
            'PSSENTRY_DSN' => Configuration::get('PSSENTRY_DSN'),
        ];
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

        if (Configuration::get('PSSENTRY_DSN')) {
            $this->updatePssentryCodeInDefinesCustomFile();
            $this->updatePssentryCodeInConfigFile();
        } else {
            $this->updatePssentryCodeInDefinesCustomFile(true);
            $this->updatePssentryCodeInConfigFile(true);
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
            // 'sentry_release' => Configuration::get('SENTRY_RELEASE'),
            'sentry_debug' => Configuration::get('PSSENTRY_DEBUG_MODE'),
        ]);

        return $this->display(__FILE__, 'header.tpl');
    }

    /**
     * This function is a hook that is called before the closing body tag of a page.
     * It displays the content of the 'displayNavFullWidth.tpl' template file.
     *
     * @return string The HTML content of the 'displayNavFullWidth.tpl' template file.
     */
    public function hookDisplayNavFullWidth()
    {
        return $this->fetch('module:pssentry/views/templates/hook/displayNavFullWidth.tpl');
    }

    /**
     * Adds or removes the Pssentry module code to/from the defines_custom.inc.php file.
     *
     * @param bool $remove whether to remove the old code block or not
     *
     * @return void
     */
    protected function updatePssentryCodeInDefinesCustomFile($remove = false)
    {
        $filename = _PS_CONFIG_DIR_ . '/defines_custom.inc.php';
        $comment = PHP_EOL . '// Start of Pssentry module code' . PHP_EOL;
        $code = PHP_EOL . '$rootDir = realpath(dirname(__FILE__).\'/..\');' . PHP_EOL .
            '$modulesDir = $rootDir.\'/modules\';' . PHP_EOL .
            '$autoloadPath = $modulesDir . \'/pssentry/vendor/autoload.php\';' . PHP_EOL .
            'if (file_exists($autoloadPath)) {' . PHP_EOL .
            '    require_once $autoloadPath;' . PHP_EOL .
            '}' . PHP_EOL .
            'try {' . PHP_EOL .
            '    Sentry\init([' . PHP_EOL .
            '        \'dsn\' => \'' . Configuration::get('PSSENTRY_DSN') . '\',' . PHP_EOL .
            '    ]);' . PHP_EOL .
            '} catch (Exception $e) {' . PHP_EOL .
            '    // We\'re not able to connect to Sentry, so we\'ll just ignore it for now.' . PHP_EOL .
            '}' . PHP_EOL .
            '// End of Pssentry module code' . PHP_EOL;

        // Check if the file exists
        if (!file_exists($filename)) {
            // If the file doesn't exist, create it with the initial contents
            file_put_contents($filename, '<?php' . PHP_EOL);
        }

        // Get the current contents of the file
        $fileContents = file_get_contents($filename);

        // Remove the old code block if requested
        $startPos = strpos($fileContents, $comment);
        $endPos = strpos($fileContents, '// End of Pssentry module code');
        if ($startPos !== false && $endPos !== false) {
            $oldCode = substr($fileContents, $startPos, $endPos - $startPos + strlen('// End of Pssentry module code') + 1);
            $fileContents = str_replace($oldCode, '', $fileContents);
        }

        // Write the new contents to the file
        if ($remove) {
            file_put_contents($filename, $fileContents);
        } else {
            file_put_contents($filename, $fileContents . $comment . $code);
        }
    }

    /**
     * Adds or removes the Pssentry module code to/from the config.inc.php file.
     *
     * @param bool $remove whether to remove the old code block or not
     *
     * @return void
     */
    protected function updatePssentryCodeInConfigFile($remove = false)
    {
        $filename = _PS_CONFIG_DIR_ . '/config.inc.php';
        $comment = PHP_EOL . '// Start of Pssentry module code' . PHP_EOL;
        $code = 'if (defined(\'_PS_ADMIN_DIR_\')) {' . PHP_EOL .
            '    Sentry\configureScope(function (Sentry\State\Scope $scope) use ($employee): void {' . PHP_EOL .
            '        $scope->setUser([' . PHP_EOL .
            '            \'id\' => $employee->id,' . PHP_EOL .
            '            \'email\' => $employee->email,' . PHP_EOL .
            '            \'type\' => \'employee\'' . PHP_EOL .
            '        ]);' . PHP_EOL .
            '    });' . PHP_EOL .
            '} else {' . PHP_EOL .
            '    Sentry\configureScope(function (Sentry\State\Scope $scope) use ($customer): void {' . PHP_EOL .
            '        $scope->setUser([' . PHP_EOL .
            '            \'id\' => $customer->id,' . PHP_EOL .
            '            \'email\' => $customer->email,' . PHP_EOL .
            '            \'type\' => \'customer\'' . PHP_EOL .
            '        ]);' . PHP_EOL .
            '    });' . PHP_EOL .
            '}' . PHP_EOL .
            '// End of Pssentry module code' . PHP_EOL;

        // Check if the file exists
        if (!file_exists($filename)) {
            // If the file doesn't exist, create it with the initial contents
            file_put_contents($filename, '<?php' . PHP_EOL);
        }

        // Get the current contents of the file
        $fileContents = file_get_contents($filename);

        // Remove the old code block if requested
        $startPos = strpos($fileContents, $comment);
        $endPos = strpos($fileContents, '// End of Pssentry module code');
        if ($startPos !== false && $endPos !== false) {
            $oldCode = substr($fileContents, $startPos, $endPos - $startPos + strlen('// End of Pssentry module code') + 1);
            $fileContents = str_replace($oldCode, '', $fileContents);
        }

        // Write the new contents to the file
        if ($remove) {
            file_put_contents($filename, $fileContents);
        } else {
            file_put_contents($filename, $fileContents . $comment . $code);
        }
    }
}
