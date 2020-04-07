<?php

namespace Hooks;

use PHPMailer\PHPMailer\Exception;

require_once 'index.php';

class MyHooks
{

    public static function postInstall()
    {
        global $dataDirectory, $configPath;

        if (!is_dir($dataDirectory)) {
            mkdir($dataDirectory, 0751);
        }
        if (!file_exists($configPath)) {
            $f = fopen($configPath, 'w');
            fclose($f);
            $wrote = file_put_contents(
                $configPath,
                '{
                "adminEmail": "",
                "adminPassword":"",
                "adminnName":"admin",
                "adminRecipients": [
                    {
                    "name": "Admin",
                    "email": " "
                    }
                ],
                "sendGreeting": false
                }'
            );

            if (!$wrote) {
                throw new Exception('Failed to write config file $configPath');
            }
            chmod($configPath, 0750);
        }
        clearstatcache();
    }
}
