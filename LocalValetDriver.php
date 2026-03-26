<?php

/**
 * Custom Valet/Herd driver for CakePHP 2.x
 *
 * CakePHP 2.x puts public assets under app/webroot/, not at the project root.
 * This driver tells Herd to look there for static files (CSS, JS, images, etc.)
 * while routing all PHP requests through CakePHP's front controller.
 */
class LocalValetDriver extends \Valet\Drivers\BasicValetDriver
{
    /**
     * The CakePHP public webroot relative to the site path.
     */
    const WEBROOT = '/app/webroot';

    /**
     * Determine if this driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath . self::WEBROOT . '/index.php');
    }

    /**
     * Determine if the request is for a static file.
     * Check inside app/webroot/ instead of the project root.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)
    {
        $webroot = $sitePath . self::WEBROOT;

        if (file_exists($staticFilePath = $webroot . rtrim($uri, '/') . '/index.html')) {
            return $staticFilePath;
        }

        if ($this->isActualFile($staticFilePath = $webroot . $uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the front controller path for CakePHP.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        // Use the root index.php which bootstraps CakePHP with correct paths
        $rootIndex = $sitePath . '/index.php';

        if ($this->isActualFile($rootIndex)) {
            $_SERVER['SCRIPT_FILENAME'] = $rootIndex;
            $_SERVER['SCRIPT_NAME']     = '/index.php';
            $_SERVER['DOCUMENT_ROOT']   = $sitePath;

            return $rootIndex;
        }

        // Fallback: serve directly from app/webroot
        $webrootIndex = $sitePath . self::WEBROOT . '/index.php';

        $_SERVER['SCRIPT_FILENAME'] = $webrootIndex;
        $_SERVER['SCRIPT_NAME']     = '/index.php';
        $_SERVER['DOCUMENT_ROOT']   = $sitePath . self::WEBROOT;

        return $webrootIndex;
    }
}
