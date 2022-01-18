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

        $uploads_dir = wp_get_upload_dir();
        $this->uploadsDir = $uploads_dir['basedir'] ?? false;
        $this->contentDir = WP_CONTENT_DIR;

        $this->tmpDir = $args['tmp_dir'] ?? WP_CLI\Utils\get_temp_dir();
    }

    /**
     * Executes the export of the database using WP-CLI and compresses the uploads
     * directory, and sets it up to rsync to a Forge server
     * 
     * ## OPTIONS
     * 
     * [--ip[=value]]
     * : String of the Forge IP to rsync the database and uploads folder to. Plugin will end if none is provided
     * ---
     * default: none
     * ---
     * 
     * [--siteUrl[=value]]
     * : String of the Forge site name used for the path of the database and uploads folder. Plugin will end if none is provided
     * 
     * ## EXAMPLES
     * 
     *      wp envsync export --ip=127.0.0.1 --siteUrl=staging.envsync.sitedomain.com
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

        // Check for directory existences first
        self::checkMissing($ip_address, "You must include the site url for the directory path where the files will be uploaded to. Use --ip");
        self::checkMissing($site_url, "You must include the IP address for the server to upload the files to. Use --siteUrl");
        self::checkMissing($this->tmpDir, "Unable to set the temporary directory.");

        echo WP_CLI::colorize("Beginning process of exporting files.");
        $progress = \WP_CLI\Utils\make_progress_bar( 'Exporting files', 5);

        // Begin the process of exporting and compressing
        echo WP_CLI::colorize("Starting the export of the database.\n");
        // Runs the WP-CLI command to export the database
        // WP_CLI::runcommand("db export mysql.sql");
        
        // Moves the mysql file to the content dir
        echo WP_CLI::colorize("Moving the database file for compression\n");

        $db_cmd = "mv mysql.sql %s";
        WP_CLI::launch(
            WP_CLI\Utils\esc_cmd(
                $db_cmd,
                $this->contentDir
            )
        );
        $progress->tick();

        // Runs the WP-CLI Launch command to go to the content directory and create a zip folder with the uploads directory
        echo WP_CLI::colorize("Compressing the uploads directory and the mysql.sql file.\n");
        
        $compress_cmd = "cd %s && tar -cPzf migrate-dir.tar.gz --exclude=**/cache mysql.sql -C %s ./uploads ";
        WP_CLI::launch(
            WP_CLI\Utils\esc_cmd(
                $compress_cmd,
                $this->contentDir,
                $this->contentDir,
                $this->uploadsDir
            )
        );
        $progress->tick();

        // Delete the mysql file
        echo WP_CLI::colorize("Deleting the mysql.sql file.\n");

        $delete_sql_cmd = "rm -f %s/mysql.sql";
        WP_CLI::launch(
            WP_CLI\Utils\esc_cmd(
                $delete_sql_cmd,
                $this->contentDir
            )
        );
        $progress->tick();
        
        // Check if the mysql.sql file still exists, and warn the user.
        if (file_exists($this->contentDir . "/mysql.sql")) {
            echo WP_CLI::warning(
                "Unable to delete the mysql.sql file. This is usually due to permissions. Please delete it manually.\n"
            );
        }

        echo WP_CLI::colorize("Starting rsync over to $ip_address under path /home/forge/$site_url/web/app\n");
        $rsync_cmd = "rsync -aP %s/migrate-dir.tar.gz forge@%s:/home/forge/%s/web/app";
        WP_CLI::launch(
            WP_CLI\Utils\esc_cmd(
                $rsync_cmd,
                $ip_address,
                $site_url,
            )
        );
        $progress->tick();

        $progress->finish();
        echo WP_CLI::success(
            "Exported and compressed the uploads folder and mysql.sql file. Preparing to migrate this to the new server."
        );
    }

    public function import($args, $assoc_args)
    {
        // extract tar -xPf /path/to/folder.tar.gz
    }

    /**
     * Method to check for an existing method or directory
     * 
     * @return WP_CLI::error
     */
    private function checkMissing($check, $err_msg)
    {
        if (!$check) {
            return WP_CLI::error($err_msg);
        }
    }
}