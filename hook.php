<?php

/**
*/

/**
 * Plugin_0GLPIXx_install
 * Call all install methods of the plugin
 *
 * @return bool
 */
function Plugin_0GLPIXx_install(): bool
{
    $migration = new Migration(PLUGIN_0GLPIXX_VERSION);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
            $classname = 'Plugin0GLPIXx' . ucfirst($matches[1]);
            include_once $filepath;
            // If the install method exists, load it
            if (method_exists($classname, 'install')) {
                $classname::install($migration);
            }
        }
    }

    return true;
}

/**
 * Plugin_0GLPIXx_uninstall
 * Call all uninstall methods of the plugin
 *
 * @return bool
 */
function Plugin_0GLPIXx_uninstall(): bool
{
    $migration = new Migration(PLUGIN_0GLPIXX_VERSION);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
            $classname = 'Plugin0GLPIXx' . ucfirst($matches[1]);
            include_once $filepath;
            // If the install method exists, load it
            if (method_exists($classname, 'uninstall')) {
                $classname::uninstall($migration);
            }
        }
    }

    return true;
}
