<?php

namespace ForgeSync\Traits;

use WP_CLI;

trait ForgeSyncTraits
{
    /**
     * Method to check for an existing method or directory
     *
     * @return WP_CLI::error
     */
    private function checkMissing($check, $err_msg)
    :WP_CLI
    {
        if (!$check) {
            return WP_CLI::error($err_msg);
        }
    }
}
