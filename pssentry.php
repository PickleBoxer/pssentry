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

use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Yaml\Yaml;

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
        $this->version = '0.1.1';
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
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install()
            && $this->registerHookAndSetToTop('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayNavFullWidth');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PSSENTRY_DEBUG_MODE');
        Configuration::deleteByName('PSSENTRY_LOADER_SCRIPT');
        Configuration::deleteByName('PSSENTRY_DSN');

        $this->modifyConfigIncInit(false);
        $this->modifyConfigIncUser(false);
        $this->deleteSentryYmlFile();
        $this->modifyAppKernel(false);

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

        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'symfony' => $this->getBundleVersion('symfony/symfony'),
            'monolog_bundle' => $this->getBundleVersion('symfony/monolog-bundle'),
            'controller_link' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name,
        ]);

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
                        'desc' => $this->l('The DSN tells the SDK where to send the events to. You can find your project\'s DSN in your Sentry project\'s settings under Client Keys (DSN).'),
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
            $this->modifyConfigIncInit(true);
            $this->modifyConfigIncUser(true);
            $this->createServicesYmlFile();
            $this->modifyAppKernel(true);
        } else {
            $this->modifyConfigIncInit(false);
            $this->modifyConfigIncUser(false);
            $this->deleteSentryYmlFile();
            $this->modifyAppKernel(false);
        }

        // Clear Symfony cache.
        Tools::clearSf2Cache();
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
    public function hookDisplayHeader()
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
     * Modify the config.inc.php file to add or remove the Sentry initialization code.
     *
     * @param bool $addInit If true, add the Sentry initialization code. If false, remove it.
     *
     * @return void
     */
    protected function modifyConfigIncInit(bool $addInit)
    {
        $configIncPath = _PS_CONFIG_DIR_ . '/config.inc.php';
        $configIncContent = file_get_contents($configIncPath);

        $initCode = PHP_EOL . '// START of Sentry PHP init' . PHP_EOL .
            '$autoloadPath = _PS_MODULE_DIR_ . \'pssentry/vendor/autoload.php\';' . PHP_EOL .
            'if (file_exists($autoloadPath)) {' . PHP_EOL .
            '    require_once $autoloadPath;' . PHP_EOL .
            '}' . PHP_EOL .
            'try {' . PHP_EOL .
            '    Sentry\init([' . PHP_EOL .
            '        \'dsn\' => \'' . Configuration::get('PSSENTRY_DSN') . '\',' . PHP_EOL .
            '        \'environment\' => _PS_MODE_DEV_ ? \'dev\' : \'production\',' . PHP_EOL .
            '        \'error_types\' => E_ALL & ~E_USER_DEPRECATED,' . PHP_EOL .
            '    ]);' . PHP_EOL .
            '} catch (Exception $e) {' . PHP_EOL .
            '    // We\'re not able to connect to Sentry, so we\'ll just ignore it for now.' . PHP_EOL .
            '}' . PHP_EOL .
            '// END of Sentry PHP init';

        $startComment = '// START of Sentry PHP init';
        $endComment = '// END of Sentry PHP init';
        $initCodeFind = $this->getStringBetweenComments($configIncContent, $startComment, $endComment);

        if ($addInit) {
            // Check if the code already exists
            if (strpos($configIncContent, $initCode) === false && ($initCodeFind === null || strpos($configIncContent, $initCodeFind) === false)) {
                $newContent = str_replace(
                    'require_once _PS_CONFIG_DIR_ . \'autoload.php\';',
                    'require_once _PS_CONFIG_DIR_ . \'autoload.php\';' . PHP_EOL . $initCode,
                    $configIncContent
                );
                file_put_contents($configIncPath, $newContent);
            } else {
                // If the code already exists, make sure it's up to date
                $newContent = str_replace(PHP_EOL . $initCodeFind, $initCode, $configIncContent);
                file_put_contents($configIncPath, $newContent);
            }
        } else {
            // Check if the code exists and remove it
            if (strpos($configIncContent, $initCode) !== false) {
                $newContent = str_replace(PHP_EOL . $initCode . PHP_EOL, '', $configIncContent);
                file_put_contents($configIncPath, $newContent);
            } elseif ($initCodeFind !== null && strpos($configIncContent, $initCodeFind) !== false) {
                $newContent = str_replace(PHP_EOL . $initCodeFind . PHP_EOL, '', $configIncContent);
                file_put_contents($configIncPath, $newContent);
            }
        }
    }

    /**
     * Get the string between two comments in a given content, including the comments themselves.
     *
     * @param string $content the content to search in
     * @param string $startComment the starting comment
     * @param string $endComment the ending comment
     *
     * @return string|null the string between the comments, including the comments themselves or null if not found
     */
    protected function getStringBetweenComments(string $content, string $startComment, string $endComment): ?string
    {
        $startPos = strpos($content, $startComment);
        if ($startPos === false) {
            return null;
        }

        $endPos = strpos($content, $endComment, $startPos + strlen($startComment));
        if ($endPos === false) {
            return null;
        }

        return substr($content, $startPos, $endPos + strlen($endComment) - $startPos);
    }

    /**
     * Modify the config.inc.php file to add or remove the Sentry setUser code at the end of the file.
     *
     * @param bool $addUser If true, add the Sentry setUser code. If false, remove it.
     *
     * @return void
     */
    protected function modifyConfigIncUser(bool $addUser)
    {
        $configIncPath = _PS_CONFIG_DIR_ . '/config.inc.php';
        $configIncContent = file_get_contents($configIncPath);

        $userCode = '// START of Sentry setUser' . PHP_EOL .
            'if (defined(\'_PS_ADMIN_DIR_\')) {' . PHP_EOL .
            '    if (function_exists(\'Sentry\configureScope\')) {' . PHP_EOL .
            '       if (isset($employee->id)) {' . PHP_EOL .
            '           Sentry\configureScope(function (Sentry\State\Scope $scope) use ($employee): void {' . PHP_EOL .
            '               $scope->setUser([' . PHP_EOL .
            '                   \'id\' => $employee->id,' . PHP_EOL .
            '                   \'email\' => $employee->email,' . PHP_EOL .
            '                   \'type\' => \'employee\'' . PHP_EOL .
            '               ]);' . PHP_EOL .
            '           });' . PHP_EOL .
            '       }' . PHP_EOL .
            '    }' . PHP_EOL .
            '} else {' . PHP_EOL .
            '    if (function_exists(\'Sentry\configureScope\')) {' . PHP_EOL .
            '       if (isset($cookie->id_customer)) {' . PHP_EOL .
            '           Sentry\configureScope(function (Sentry\State\Scope $scope) use ($cookie): void {' . PHP_EOL .
            '               $scope->setUser([' . PHP_EOL .
            '                   \'id\' => $cookie->id_customer,' . PHP_EOL .
            '                   \'email\' => $cookie->email,' . PHP_EOL .
            '                   \'type\' => \'customer\'' . PHP_EOL .
            '               ]);' . PHP_EOL .
            '           });' . PHP_EOL .
            '       }' . PHP_EOL .
            '    }' . PHP_EOL .
            '}' . PHP_EOL .
            '// END of Sentry setUser' . PHP_EOL;

        if ($addUser) {
            // Check if the code already exists
            if (strpos($configIncContent, $userCode) === false) {
                $newContent = $configIncContent . PHP_EOL . $userCode;
                file_put_contents($configIncPath, $newContent);
            }
        } else {
            // Check if the code exists and remove it
            if (strpos($configIncContent, $userCode) !== false) {
                $newContent = str_replace(PHP_EOL . $userCode, '', $configIncContent);
                file_put_contents($configIncPath, $newContent);
            }
        }
    }

    /**
     * Creates the services.yml file with the necessary configuration for Sentry and Monolog.
     *
     * @return void
     */
    protected function createServicesYmlFile()
    {
        $monologBundleVersion = InstalledVersions::getVersion('symfony/monolog-bundle');
        // original 3.7, old version Sentry does not work with new config see:
        // https://docs.sentry.io/platforms/php/guides/symfony/#monolog-integration
        $isGreaterThan37 = Comparator::greaterThan($monologBundleVersion, '4.7');

        $sentryConfig = [
            'dsn' => Configuration::get('PSSENTRY_DSN'),
            'options' => [
                'error_types' => 'E_ALL & ~E_USER_DEPRECATED',
            ],
        ];

        $monologConfig = [
            'handlers' => [
                'sentry' => [
                    'type' => $isGreaterThan37 ? 'sentry' : 'service',
                ],
            ],
        ];

        $servicesConfig = [];

        if (!$isGreaterThan37) {
            $servicesConfig = [
                'Sentry\Monolog\Handler' => [
                    'arguments' => [
                        '$hub' => '@Sentry\State\HubInterface',
                        '$level' => '!php/const Monolog\Logger::ERROR',
                    ],
                ],
                'Monolog\Processor\PsrLogMessageProcessor' => [
                    'tags' => '{ name: monolog.processor, handler: sentry }',
                ],
            ];

            $monologConfig['handlers']['sentry']['id'] = 'Sentry\Monolog\Handler';
            $content['services'] = $servicesConfig;
        } else {
            $monologConfig['handlers']['sentry']['level'] = '!php/const Monolog\Logger::ERROR';
            $monologConfig['handlers']['sentry']['hub_id'] = 'Sentry\State\HubInterface';
        }

        $content = [
            'sentry' => $sentryConfig,
            'monolog' => $monologConfig,
        ];

        if (!$isGreaterThan37) {
            $content['services'] = $servicesConfig;
        }

        $filename = _PS_ROOT_DIR_ . '/app/config/addons/sentry.yml';

        $yaml = Yaml::dump($content, 4);
        $yaml = str_replace('\'!php/const Monolog\\Logger::ERROR\'', '!php/const Monolog\\Logger::ERROR', $yaml);
        $yaml = str_replace('\'{ name: monolog.processor, handler: sentry }\'', '{ name: monolog.processor, handler: sentry }', $yaml);

        file_put_contents($filename, $yaml);
    }

    /**
     * Deletes the sentry.yml file.
     *
     * @return void
     */
    protected function deleteSentryYmlFile()
    {
        $filename = _PS_ROOT_DIR_ . '/app/config/addons/sentry.yml';

        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Adds or removes SentryBundle from the bundles array in appkernal.php.
     *
     * @param bool $addBundle whether to add or remove the SentryBundle
     *
     * @return void
     */
    protected function modifyAppKernel(bool $addBundle)
    {
        $appKernelPath = _PS_ROOT_DIR_ . '/app/AppKernel.php';
        $appKernelContent = file_get_contents($appKernelPath);

        $bundleCode = 'if (class_exists(\'Sentry\SentryBundle\SentryBundle\')) {
            $bundles[] = new Sentry\SentryBundle\SentryBundle();
        }';

        if ($addBundle) {
            // Check if the code already exists
            if (strpos($appKernelContent, $bundleCode) === false) {
                $newContent = str_replace(
                    'return $bundles;',
                    $bundleCode . "\n\n        return \$bundles;",
                    $appKernelContent
                );
                file_put_contents($appKernelPath, $newContent);
            }
        } else {
            // Check if the code exists and remove it
            if (strpos($appKernelContent, $bundleCode) !== false) {
                $newContent = str_replace($bundleCode . "\n\n        return \$bundles;", 'return $bundles;', $appKernelContent);
                file_put_contents($appKernelPath, $newContent);
            }
        }
    }

    /**
     * Returns the installed version of a given bundle.
     *
     * @param string $bundleName the name of the bundle
     *
     * @return string the installed version of the bundle
     */
    protected function getBundleVersion(string $bundleName): string
    {
        return InstalledVersions::getVersion($bundleName);
    }

    /**
     * Checks if the installed version of a given bundle is greater than the given version.
     *
     * @param string $bundleName the name of the bundle to check
     * @param string $version the version to compare against
     *
     * @return bool returns true if the installed version is greater than the given version, false otherwise
     */
    protected function isBundleGreaterThan(string $bundleName, string $version): bool
    {
        $installedVersion = $this->getBundleVersion($bundleName);

        return Comparator::greaterThan($installedVersion, $version);
    }

    /**
     * Processes an AJAX request to run a Symfony command.
     *
     * @throws Exception if the command or argument is invalid
     */
    public function ajaxProcessRunSymfonyCommand()
    {
        $command = Tools::getValue('command');
        $arg = Tools::getValue('arg');

        $kernel = new AppKernel(_PS_ENV_, _PS_MODE_DEV_);
        $application = new Application($kernel);
        $application->setAutoExit(false);

        if ($arg) {
            $input = new ArrayInput([
                'command' => $command,
                'name' => $arg,
            ]);
        } else {
            $input = new ArrayInput([
                'command' => $command,
            ]);
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');

        $response->setCallback(function () use ($application, $input) {
            $output = new BufferedOutput();
            $application->run($input, $output);

            $result = $output->fetch();
            $lines = explode(PHP_EOL, $result);

            foreach ($lines as $line) {
                echo "data: $line\n\n";
                ob_flush();
                flush();
                sleep(0); // Optional delay between lines
            }

            echo "event: end\n";
            echo "data: Command finished\n\n";
            ob_flush();
            flush();
        });

        $response->send();

        exit;
    }
}
