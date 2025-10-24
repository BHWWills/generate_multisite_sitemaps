<?php
namespace Concrete\Package\GenerateMultisiteSitemaps\Command;

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Site\Service;
use Concrete\Core\Entity\Site\Site;
use Concrete\Core\Page\PageList;
use Concrete\Core\Page\Page;

class GenerateMultisiteSitemapsCommandHandler
{
    public function __invoke(GenerateMultisiteSitemapsCommand $command)
    {
        $app = Application::getFacadeApplication();
        $results = [];
        
        try {
            $siteService = $app->make(Service::class);
            
            //\Log::addInfo("Starting multisite sitemap generation");
            
            // Get all sites
            $sites = $siteService->getList();
            //\Log::addInfo("Found " . count($sites) . " sites");
            
            foreach ($sites as $site) {
                //\Log::addInfo("Processing site: " . $site->getSiteName() . " (Handle: " . $site->getSiteHandle() . ")");
                
                // Generate comprehensive sitemap for this site
                $xml = $this->generateComprehensiveSitemap($site);
                
                if ($xml) {
                    $filename = "sitemap_{$site->getSiteHandle()}.xml";
                    $filepath = DIR_BASE . "/{$filename}";
                    
                    if (file_put_contents($filepath, $xml) !== false) {
                        $pageCount = substr_count($xml, '<url>');
                        //\Log::addInfo("SUCCESS: Created sitemap: {$filename} with {$pageCount} pages");
                        $results[] = "SUCCESS: Created sitemap: {$filename} with {$pageCount} pages";
                    } else {
                        //\Log::addInfo("ERROR: Failed to create: {$filename}");
                        $results[] = "ERROR: Failed to create: {$filename}";
                    }
                } else {
                    //\Log::addInfo("ERROR: Could not generate XML for '{$site->getSiteName()}'");
                    $results[] = "ERROR: Could not generate XML for '{$site->getSiteName()}'";
                }
            }
            
            //\Log::addInfo("Sitemap generation completed");
            $results[] = "Task completed successfully";

        } catch (\Exception $e) {
            //\Log::addInfo("EXCEPTION: " . $e->getMessage());
            $results[] = "EXCEPTION: " . $e->getMessage();
        }
        
        return $results;
    }
    
    private function generateComprehensiveSitemap(Site $site)
    {
        try {
            $app = Application::getFacadeApplication();
            $siteService = $app->make(Service::class);
            
            // Create page list for this site
            $list = new PageList();
            $list->filterBySite($site);
            
            // Get all pages (active ones are included by default in Concrete)
            $pages = $list->getResults();
            
            //\Log::addInfo("Found " . count($pages) . " pages for site " . $site->getSiteName());
            
            // Create XML with proper formatting
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;
            
            $urlset = $dom->createElement('urlset');
            $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $dom->appendChild($urlset);
            
            $urlCount = 0;
            foreach ($pages as $page) {
                // Skip if page is excluded from sitemap
                if ($page->getAttribute('exclude_sitemapxml')) {
                    continue;
                }
                
                // Check if any parent has exclude_subpages_sitemapxml
                if ($this->isExcludedByParent($page)) {
                    continue;
                }
                
                // Skip if page is excluded from sitemap
                if ($page->isExternalLink()) {
                    continue;
                }
                
                // Skip system pages and dashboard
                if ($page->getCollectionPath() == '/dashboard' || strpos($page->getCollectionPath(), '/dashboard/') === 0) {
                    continue;
                }
                
                // Skip pages that are in trash or not approved
                if ($page->isInTrash() || $page->isError()) {
                    continue;
                }
                
                // Get the URL
                $url = $page->getCollectionLink(true);
                if (!$url) {
                    continue;
                }
                
                // Create URL element
                $urlElement = $dom->createElement('url');
                
                // Location
                $loc = $dom->createElement('loc', htmlspecialchars($url));
                $urlElement->appendChild($loc);
                
                // Last modified
                $lastmod = $dom->createElement('lastmod', date('c', strtotime($page->getCollectionDateLastModified())));
                $urlElement->appendChild($lastmod);
                
                // Change frequency
                $changefreq = $dom->createElement('changefreq', 'weekly');
                $urlElement->appendChild($changefreq);
                
                // Priority
                $priority = $dom->createElement('priority', '0.5');
                $urlElement->appendChild($priority);
                
                $urlset->appendChild($urlElement);
                $urlCount++;
            }
            
            //\Log::addInfo("Added " . $urlCount . " URLs to sitemap for site " . $site->getSiteName());
            
            return $dom->saveXML();
            
        } catch (\Exception $e) {
            //\Log::addInfo("EXCEPTION in generateComprehensiveSitemap: " . $e->getMessage());
            return false;
        }
    }
    
    private function isExcludedByParent($page)
    {
        // Check only the immediate parent
        $parentID = $page->getCollectionParentID();
        if ($parentID > 1) { // 1 is the home page, which is fine
            $parent = Page::getByID($parentID);
            if ($parent && !$parent->isError() && $parent->getAttribute('exclude_subpages_sitemapxml')) {
                return true;
            }
        }
        
        return false;
    }
}