<?php

namespace EnvSync;

use WP_CLI;

class EnvSyncCommands
{

    /**
     * @var string The content directory
     */
    private $contentDir;
    
    /**
     * @var string The uploads directory
     */
    private $uploadsDir;

    /**
     * @var string The temporary directory
     */
    private $tmpDir;

    public function __construct(array $args = [])
    {
        $this->uploadsDir = $args['uploads_dir'] ?? false;
        if (!$this->uploadsDir) {
            $uploads_dir = wp_get_upload_dir();
            $this->uploadsDir = $uploads_dir['basedir'] ?? false;
        }

        $this->tmpDir = $args['tmp_dir'] ?? WP_CLI\Utils\get_temp_dir();
        $this->contentDir = $args['content_dir'] ?? WP_CONTENT_DIR;
    }

    /**
     * Executes the export of the database using WP-CLI and compresses the uploads
     * directory, and sets it up to rsync to a Forge server
     * 
     * @when after_wp_load
     * 
     * @param array $args
     * @param array $assoc_args [
     *      @param string ip       The IP address of the new site
     *      @param string siteUrl  The site URL defined in the new forge server
     * ]
     * 
     * @return WP_CLI::error|WP_CLI::success
     */
    public function export($args, $assoc_args)
    {
        $ip_address = $assoc_args['ip'] ?? false;
        $site_url = $assoc_args['siteUrl'] ?? false;

        if (!class_exists('PharData')) {
            return WP_CLI::error("This plugin requires PharData to be installed");
        }

        if (!$ip_address) {
            return WP_CLI::error("You must include the IP address to export files to");
        }

        if (!$this->uploadsDir) {
            return WP_CLI::error('Unable to locate the uploads directory.');
        }



        WP_CLI::colorize("Starting the export of the database");
        // Runs the WP-CLI command to export the database
        // WP_CLI::runcommand("db export mysql.sql");
        // $cmd = "tar xz --strip-components=1 --directory=%s -f $tarball";
        // WP_CLI::launch(WP_CLI\Utils\esc_cmd($cmd, $uploads_dir));
    }

    public function import($args, $assoc_args)
    {
    }

    private function checkForDir($dir, $err)
    {
        if (!$dir) {
            return WP_CLI::error($err);
        }
    }
}