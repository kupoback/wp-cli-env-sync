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

if (!class_exists('WP_CLI')) {
    return;
}

$instance = new EnvSyncCommands();
WP_CLI::add_command('envsync', $instance);