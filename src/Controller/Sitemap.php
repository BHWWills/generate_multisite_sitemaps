<?php
namespace Concrete\Package\GenerateMultisiteSitemaps\Controller;

use Concrete\Core\Site\Service;
use Concrete\Core\Controller\Controller;

defined('C5_EXECUTE') or die("Access Denied.");

class Sitemap extends Controller
{
    public function view()
    {
        $siteService = $this->app->make(Service::class);
        
        $currentSite = $siteService->getSite();
        $siteHandle = $currentSite->getSiteHandle();
        
        $filename = "sitemap_{$siteHandle}.xml";
        $filepath = DIR_BASE . "/{$filename}";
        
        if (file_exists($filepath)) {
            // Set headers and output directly
            header('Content-Type: application/xml; charset=UTF-8');
            readfile($filepath);
            exit;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'Sitemap not found';
            exit;
        }
    }
}