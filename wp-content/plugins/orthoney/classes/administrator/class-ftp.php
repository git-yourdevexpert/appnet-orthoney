<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}


// $uploader = new Orthoney_SFTP_Uploader();
// $files = $uploader->listFiles();
// print_r($files);

require OH_PLUGIN_DIR_PATH . '/vendor/autoload.php';

use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

class Orthoney_SFTP_Uploader {

    // private $host       = "198.74.52.21";
    // private $port       = 22;
    // private $username   = "orthoney1";
    // private $password   = 'a%sJ$g!Hx2$uwIK$';
    // private $remoteRoot = "/var/www/144b11f1-8163-448a-bcaf-202247e4dda2/public_html";

    private $host       = "ftp.allpointsatl.com";
    private $port       = 22;
    private $username   = "HoneyBackstage";
    private $password   = 'ref8pfk3xwm3DBD.drk';
    private $remoteRoot = "";

    private function getFilesystem() {
        $connectionProvider = new SftpConnectionProvider(
            $this->host,
            $this->username,
            $this->password,
            null,  // private key
            null,  // passphrase
            $this->port,
            false, // use agent
            30000, // timeout
            1000,  // max tries
            null,  // host fingerprint
            null   // connectivity checker
        );

        $adapter = new SftpAdapter($connectionProvider, $this->remoteRoot);
        return new Filesystem($adapter);
    }

    /**
     * Upload file to remote SFTP server
     */
    public function upload($localFile, $remotePath = null) {
        $log_file = WP_CONTENT_DIR . '/uploads/fulfillment_log.txt';

        if (!file_exists($localFile)) {
            error_log("Local file does not exist: " . $localFile . "\n", 3, $log_file);
            return false;
        }

        if ($remotePath === null) {
            $remotePath = "Test/" . basename($localFile);
        }

        try {
            $filesystem = $this->getFilesystem();

            $dir = dirname($remotePath);
            if (!$filesystem->directoryExists($dir)) {
                $filesystem->createDirectory($dir);
            }

            $content = file_get_contents($localFile);
            $filesystem->write($remotePath, $content);

            error_log("🎉 File uploaded successfully to: " . $this->remoteRoot . '/' . $remotePath . "\n", 3, $log_file);
            return true;

        } catch (\Exception $e) {
            error_log("❌ Upload failed: " . $e->getMessage() . "\n", 3, $log_file);
            return false;
        }
    }


    /**
     * List all files in a remote directory
     *
     * @param string $remotePath Relative path from remote root
     * @return array|false
     */
    public function listFiles($remotePath = "wp-content/tracking-csv") {
        try {
            $filesystem = $this->getFilesystem();

            if (!$filesystem->directoryExists($remotePath)) {
                error_log("❌ Remote directory does not exist: " . $remotePath);
                return false;
            }

            $contents = $filesystem->listContents($remotePath, false); // false = not recursive
            $files = [];

            foreach ($contents as $item) {
                if ($item->isFile()) {
                    $fileName = basename($item->path());

                    // Get last modified timestamp
                    $lastModified = $filesystem->lastModified($item->path());

                    // Format datetime
                    $formattedDate = date("Y-m-d H:i:s", $lastModified);

                    $files[] = [
                        'name' => $fileName,
                        'last_modified' => $formattedDate,
                    ];
                }
            }

            error_log("📂 Found files: " . json_encode($files));
            return $files;

        } catch (\Exception $e) {
            error_log("❌ Failed to list files: " . $e->getMessage());
            return false;
        }
    }
}
