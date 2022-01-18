<?php

/**
 * Plugin Name: WP-CLI Env Sync
 * Description: A plugin that syncs from one env to the next, copying the database and any uploads folders, and allowing the unpacking of the folders
 * Version: 1.0.0
 * Author: Nick Makris
 * Author URI: #
 * License: MIT License
 */

require_once __DIR__ . '/vendor/autoload.php';

use EnvSync\EnvSyncCommands;

/**
 * Checks if WP-CLI is installed
 *
 * @return trigger_error
 */
if (!class_exists('WP_CLI')) {
    return trigger_error("This plugin requires WP_CLI");
}

/**
 * Checks if PharData is installed
 * 
 * @return trigger_error
 */
if (!class_exists('PharData')) {
    return trigger_error("This plugin requires PharData to be activated.");
}

$instance = new EnvSyncCommands();
WP_CLI::add_command('envsync', $instance);