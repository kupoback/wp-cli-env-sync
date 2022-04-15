<?php

namespace ForgeSync;

use ForgeSync\Traits\ForgeSyncTraits;

use phpseclib3\Net\SSH2;
use WP_CLI;
use function WP_CLI\Utils\make_progress_bar;

class ForgeSyncCommands
{
    use ForgeSyncTraits;

    /**
     * @var string The content directory
     */
    private string $contentDir;

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

        $this->tmpDir = $args['tmp_dir'] ?? get_temp_dir();
    }

    /**
     * Executes the export of the database using WP-CLI and compresses the uploads
     * directory, and sets it up to rsync to a Forge server
     *
     * ## OPTIONS
     *
     * --ip=<value>
     * : String of the Forge IP to rsync the database and uploads folder to. Plugin will end if none is provided
     *
     * --forgeenv=<value>
     * : String of the Forge site name used for the path of the database and uploads folder. Plugin will end if none is provided
     *
     * ## EXAMPLES
     *
     *      $ wp forgeSync export --ip=127.0.0.1 --forgeenv=staging.forgesync.sitedomain.com
     *      Success: Exported and compressed the uploads folder and mysql.sql file. The file has been moved to the server.
     *
     * @when after_wp_load
     *
     * @param array $_
     * @param array $assoc_args
     */
    public function export(array $_, array $assoc_args)
    {
        $ip_address = $assoc_args['ip'] ?? false;
        $forge_env  = $assoc_args['forgeenv'] ?? false;
        $errors = [];

        // Check for directory existences first
        self::checkMissing($ip_address, "You must include the site url for the directory path where the files will be uploaded to. Use --ip");
        self::checkMissing($forge_env, "You must include the IP address for the server to upload the files to. Use --siteUrl");
        self::checkMissing($this->tmpDir, "Unable to set the temporary directory.");

        echo WP_CLI::colorize("Beginning process of exporting files.\n");
        $progress = make_progress_bar('Progress', 5);

        // Begin the process of exporting and compressing
        // echo WP_CLI::colorize("Starting the export of the database.\n");
        // Runs the WP-CLI command to export the database
        // WP_CLI::runcommand("db export mysql.sql");

        // Moves the mysql file to the content dir
        // echo WP_CLI::colorize("Moving the database file for compression\n");

        $db_cmd = "mv mysql.sql %s";
        // WP_CLI::launch(
        //     WP_CLI\Utils\esc_cmd(
        //         $db_cmd,
        //         $this->contentDir
        //     )
        // );
        $progress->tick();

        // Runs the WP-CLI Launch command to go to the content directory and create a zip folder with the uploads directory
        // echo WP_CLI::colorize("Compressing the uploads directory and the mysql.sql file.\n");

        $compress_cmd = "cd %s && tar -cPzf migrate-dir.tar.gz --exclude=**/cache mysql.sql -C %s ./uploads ";
        // WP_CLI::launch(
        //     WP_CLI\Utils\esc_cmd(
        //         $compress_cmd,
        //         $this->contentDir,
        //         $this->contentDir,
        //         $this->uploadsDir
        //     )
        // );
        $progress->tick();

        // Delete the mysql file
        // echo WP_CLI::colorize("Deleting the mysql.sql file.\n");

        $delete_sql_cmd = "rm -f %s/mysql.sql";
        // WP_CLI::launch(
        //     WP_CLI\Utils\esc_cmd(
        //         $delete_sql_cmd,
        //         $this->contentDir
        //     )
        // );
        $progress->tick();

        // Check if the mysql.sql file still exists, and warn the user.
        if (file_exists($this->contentDir . "/mysql.sql")) {
            $errors[] = "Unable to delete the mysql.sql file. This is usually due to permissions. Please delete it manually.\n";
        }

        //$clean_forge_env = escapeshellarg('forge' . $forge_env);
        $ssh = new SSH2($ip_address);
        $key = $ssh->getServerPublicHostKey();

        if (!$ssh->login('forge', $key)) {
            error_log(print_r('failed', true));
        }

        // $ssh->login('forge', $ssh->getServerPublicHostKey());
        // $ssh->write("ping -c 5 $ip_address\n");

        // if (!$test_connection->isSuccessful()) {
        //     WP_CLI::error("Unable to successfully connect to the server. Ensure the SSH key is added from this server, and that the connection data is correct");
        // }
        // error_log(print_r($ssh->login('forge', $ssh->getServerPublicHostKey()), true));

        // echo WP_CLI::colorize("Starting rsync over to $ip_address under path /home/forge/$forge_env/web/app\n");
        // $rsync_cmd = "rsync -aP %s/migrate-dir.tar.gz forge@%s:/home/forge/%s/web/app";
        // WP_CLI::launch(
        //     WP_CLI\Utils\esc_cmd(
        //         $rsync_cmd,
        //         $ip_address,
        //         $forge_env,
        //         $forge_env,
        //     )
        // );
        $progress->tick();

        $progress->finish();
        // error_log(print_r($errors, true));
        echo WP_CLI::success(
            "Exported and compressed the uploads folder and mysql.sql file. The file has been moved to the server."
        );
    }

    /**
     * Executes an import
     *
     * @when after_wp_load
     *
     * @param array $args
     * @param array $assoc_args [
     *      @param string oldUrl The old URL that the site migrated from
     * ]
     */
    public function import($args, $assoc_args)
    {
        // extract tar -xPf /path/to/folder.tar.gz
    }

}
