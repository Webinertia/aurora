<?php

declare(strict_types=1);

namespace App;

use App\Log\Processors\PsrPlaceholder;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\I18n\Translator\Loader\PhpArray;
use Laminas\Log\Logger;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Psr\Log\LoggerInterface;

use function dirname;

return [
    'app_settings'       => [ // app_settings that are not to be edited are stored here
        'server' => [
            'app_path'        => dirname($_SERVER['DOCUMENT_ROOT'], 1),
            'upload_basepath' => dirname($_SERVER['DOCUMENT_ROOT'], 1) . '/public/module',
            'scheme'          => $_SERVER['REQUEST_SCHEME'],
        ],
    ],
    'base_dir'           => dirname(__DIR__, 3),
    'db'                 => [
        'sessions_table_name' => 'sessions',
        'log_table_name'      => 'log',
        'theme_table_name'    => 'theme',
    ],
    'router'             => [
        'router_class' => TranslatorAwareTreeRouteStack::class,
        'routes'       => [
            'home'    => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'test'    => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/test',
                    'defaults' => [
                        'controller' => Controller\TestController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'site'    => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/site[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'contact' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/site/contact[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'contact',
                    ],
                ],
            ],
            'admin'   => [
                'type'          => Placeholder::class,
                'may_terminate' => true,
                'child_routes'  => [
                    'dashboard' => [
                        'type'          => Literal::class,
                        'may_terminate' => true,
                        'options'       => [
                            'route'    => '/admin',
                            'defaults' => [
                                'controller' => Controller\AdminController::class,
                                'action'     => 'index',
                            ],
                        ],
                    ],
                    'settings'  => [
                        'type'          => Placeholder::class,
                        'may_terminate' => true,
                        'child_routes'  => [
                            'manage' => [
                                'may_terminate' => true,
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'    => '/admin/settings',
                                    'defaults' => [
                                        'controller' => Controller\AdminController::class,
                                        'action'     => 'manage-settings',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'themes'    => [
                        'type'          => Placeholder::class,
                        'may_terminate' => true,
                        'child_routes'  => [
                            'manage' => [
                                'may_terminate' => true,
                                'type'          => Literal::class,
                                'options'       => [
                                    'route'    => '/admin/themes',
                                    'defaults' => [
                                        'controller' => Controller\AdminController::class,
                                        'action'     => 'manage-themes',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'logs'      => [
                        'type'          => Placeholder::class,
                        'may_terminate' => true,
                        'child_routes'  => [
                            'overview' => [
                                'may_terminate' => true,
                                'type'          => Segment::class,
                                'options'       => [
                                    'route'    => '/admin/logs/view',
                                    'defaults' => [
                                        'controller' => Controller\LogController::class,
                                        'action'     => 'view',
                                    ],
                                ],
                            ],
                            'error'    => [
                                'may_terminate' => true,
                                'type'          => Segment::class,
                                'options'       => [
                                    'route'       => '/admin/logs/error[/:pageNumber[/:itemsPerPage]]',
                                    'defaults'    => [
                                        'controller' => Controller\LogController::class,
                                        'action'     => 'error',
                                    ],
                                    'constraints' => [
                                        'pageNumber'   => '[0-9]',
                                        'itemsPerPage' => '[0-9]',
                                    ],
                                ],
                            ],
                            'delete'   => [
                                'may_terminate' => true,
                                'type'          => Segment::class,
                                'options'       => [
                                    'route'       => '/admin/logs/delete[/:id]',
                                    'defaults'    => [
                                        'controller' => Controller\LogController::class,
                                        'action'     => 'delete',
                                    ],
                                    'constraints' => [
                                        'id' => '[0-9]',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'psr_log'            => [
        LoggerInterface::class => [
            'writers'    => [
                'db' => [
                    'name'     => 'db',
                    'priority' => Logger::INFO,
                    'options'  => [
                        'table'     => 'log',
                        'db'        => AdapterInterface::class,
                        'formatter' => [
                            'name'    => 'db',
                            'options' => [
                                'dateTimeFormat' => 'm-d-Y H:i:s',
                            ],
                        ],
                    ],
                ],
            ],
            'processors' => [
                'psrplaceholder' => [
                    'name'     => PsrPlaceholder::class,
                    'priority' => Logger::INFO,
                ],
            ],
        ],
    ],
    'log_processors'     => [
        'aliases'   => [
            'psrplaceholder' => PsrPlaceholder::class,
        ],
        'factories' => [
            PsrPlaceholder::class => Log\Processors\PsrPlaceholderFactory::class,
        ],
    ],
    'service_manager'    => [
        'factories' => [
            Db\DbGateway\LogGateway::class => Db\DbGateway\Factory\LogGatewayFactory::class,
            Model\Settings::class          => Model\Factory\SettingsFactory::class,
            Model\Theme::class             => InvokableFactory::class,
            Service\Email::class           => Service\Factory\EmailFactory::class,
            SaveHandlerInterface::class    => Session\SaveHandlerFactory::class,
        ],
    ],
    'controllers'        => [
        'factories' => [ // move this to an abstract factory???
            Controller\AdminController::class => Controller\Factory\AppControllerFactory::class,
            Controller\IndexController::class => Controller\Factory\AppControllerFactory::class,
            Controller\TestController::class  => Controller\Factory\AppControllerFactory::class,
            Controller\LogController::class   => Controller\Factory\AppControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'aliases'   => [
            'email'          => Controller\Plugin\Email::class,
            'redirectPrev'   => Controller\Plugin\RedirectPrev::class,
            'service'        => Controller\Plugin\ServiceLocator::class,
            'serviceManager' => Controller\Plugin\ServiceLocator::class,
        ],
        'factories' => [
            Controller\Plugin\Email::class          => Controller\Plugin\Factory\EmailFactory::class,
            Controller\Plugin\RedirectPrev::class   => Controller\Plugin\Factory\RedirectPrevFactory::class,
            Controller\Plugin\ServiceLocator::class => Controller\Plugin\Factory\ServiceLocatorFactory::class,
        ],
    ],
    'form_elements'      => [
        'factories' => [
            Form\ContactForm::class               => Form\Factory\ContactFormFactory::class,
            Form\Fieldset\SecurityFieldset::class => Form\Fieldset\Factory\SecurityFieldsetFactory::class,
            Form\SettingsForm::class              => Form\Factory\SettingsFormFactory::class,
            Form\ThemeSettingsForm::class         => Form\Factory\ThemeSettingsFormFactory::class,
            Form\Fieldset\ThemeFieldset::class    => InvokableFactory::class,
        ],
    ],
    'filters'            => [
        'invokables' => [
            Filter\FqcnToControllerName::class => InvokableFactory::class,
            Filter\FqcnToModuleName::class     => InvokableFactory::class,
        ],
    ],
    'navigation'         => [
        'default' => [
            [
                'label'  => 'Home',
                'route'  => 'home',
                'class'  => 'nav-link',
                'order'  => -999,
                'action' => 'index',
            ],
            [
                'label'  => 'Contact Us',
                'route'  => 'contact',
                'class'  => 'nav-link',
                'order'  => 999,
                'action' => 'contact',
            ],
            [
                'label'     => 'Admin',
                'uri'       => '/admin',
                'class'     => 'nav-link',
                'order'     => -1000,
                'resource'  => 'admin',
                'privilege' => 'view',
            ],
        ],
        'admin'   => [
            [
                'label'     => 'Home',
                'uri'       => '/',
                'iconClass' => 'mdi mdi-home text-success',
                'order'     => -1000,
            ],
            [
                'label'     => 'Dashboard',
                'uri'       => '/admin',
                'iconClass' => 'mdi mdi-speedometer text-success',
                'order'     => -99,
            ],
            [
                'label'     => 'Manage Settings',
                'uri'       => '/admin/settings',
                'iconClass' => 'mdi mdi-cogs text-danger',
                'resource'  => 'settings',
                'privilege' => 'edit',
            ],
            [
                'label'     => 'Manage Themes',
                'uri'       => '/admin/themes',
                'iconClass' => 'mdi mdi-palette text-success',
                'resource'  => 'theme',
                'privilege' => 'manage',
            ],
            [
                'label'     => 'Logs',
                'uri'       => '/admin/logs/view',
                'iconClass' => 'mdi mdi-alarm text-warning',
                'resource'  => 'logs',
                'privilege' => 'view',
                'order'     => 1000,
            ],
        ],
    ],
    'view_helpers'       => [
        'aliases'   => [
            'bootstrapForm'           => View\Helper\BootstrapForm::class,
            'bootstrapFormCollection' => View\Helper\BootstrapFormCollection::class,
            'bootstrapFormRow'        => View\Helper\BootstrapFormRow::class,
            'mapPriority'             => View\Helper\MapLogPriority::class,
        ],
        'factories' => [
            View\Helper\MapLogPriority::class          => InvokableFactory::class,
            View\Helper\BootstrapForm::class           => InvokableFactory::class,
            View\Helper\BootstrapFormCollection::class => InvokableFactory::class,
            View\Helper\BootstrapFormRow::class        => InvokableFactory::class,
        ],
    ],
    'view_manager'       => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map'             => [],
    ],
    'translator'         => [
        'locale'                    => [
            //'en_US'
        ],
        'translation_file_patterns' => [
            [
                'type'     => PhpArray::class,
                'filename' => 'en-US.php',
                'base_dir' => __DIR__ . '/languages',
                'pattern'  => '%s.php',
            ],
//             [
//                 'type'        => Laminas\I18n\Translator\Loader\PhpArray::class,
//                 'base_dir'    => __DIR__ . '/languages',
//                 'pattern'     => 'user-%s.php',
//                 'text_domain' => 'user',
//             ],
        ],
        'translation_files'         => [
            [
                'type'        => 'PhpArray',
                'filename'    => dirname(__DIR__, 3) . '/languages/en-US.php',
                'locale'      => 'en-US',
                'text_domain' => 'default',
            ],
//             [
//                 'type' => 'PhpArray',
//                 'filename' => dirname(__DIR__, 3) . '/languages/user-en-US.php',
//                 'text_domain' => 'user',
//                 'locale' => 'en-US',
//             ],
            [
                'type'        => 'PhpArray',
                'filename'    => dirname(__DIR__, 3) . '/languages/es-MX.php',
                'text_domain' => 'default',
                'locale'      => 'es-MX',
            ],
//             [
//                 'type' => 'PhpArray',
//                 'filename' => dirname(__DIR__, 3) . '/languages/user-es-MX.php',
//                 'text_domain' => 'user',
//                 'locale' => 'es-MX',
//             ],
        ],
    ],
];
