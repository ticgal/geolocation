<?php

/**
 */

use Glpi\Plugin\Hooks;

define('PLUGIN_0GLPIXX_VERSION', '0.1.0');
define('PLUGIN_0GLPIXX_MIN_GLPI', '10.0');
define('PLUGIN_0GLPIXX_MAX_GLPI', '11.0');

/**
 * Plugin_Version_0GLPIxx
 *
 * @return array
 */
function Plugin_Version_0GLPIxx(): array
{
    return [
        'name'          => '0GLPIXO',
        'version'       => PLUGIN_0GLPIXX_VERSION,
        'author'        => '<a href="https://tic.gal">TICgal</a>',
        'homepage'      => 'https://tic.gal',
        'license'       => 'AGPLv3+',
        'requirements'  => [
            'glpi' => [
                'min' => PLUGIN_0GLPIXX_MIN_GLPI,
                'max' => PLUGIN_0GLPIXX_MAX_GLPI,
            ]
        ]
    ];
}

/**
 * Plugin_Init_0GLPIXx
 *
 * @return void
 */
function Plugin_Init_0GLPIXx(): void
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['0GLPIxx'] = true;

    $plugin = new Plugin();
    if ($plugin->isActivated('0GLPIxx')) {
    }
}
