<?php
namespace Concrete\Package\GenerateMultisiteSitemaps;

use Concrete\Core\Package\Package;
use Concrete\Core\Command\Task\Manager;

class Controller extends Package
{
    protected $appVersionRequired = '9.0';
    protected $pkgVersion = '1.5.4';
    protected $pkgHandle = 'generate_multisite_sitemaps';
    protected $pkgAutoloaderRegistries = [
        'src' => '\Concrete\Package\GenerateMultisiteSitemaps'
    ];

    public function getPackageDescription()
    {
        return t("Adds a multisite sitemap.xml generator task to your Concrete site.");
    }

    public function getPackageName()
    {
        return t("Multisite Sitemap Generator");
    }
    
    public function install()
    {
        parent::install();
        $this->installContentFile('install/tasks.xml');
        $this->installContentFile('install/attributes.xml');
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->installContentFile('install/tasks.xml');
        $this->installContentFile('install/attributes.xml');
    }
    
    public function on_start()
    {
        // Register the task
        $manager = $this->app->make(Manager::class);
        $manager->extend('generate_multi_sitemaps', function() {
            return $this->app->make(\Concrete\Package\GenerateMultisiteSitemaps\Task\GenerateMultiSitemapsController::class);
        });

        // Register the sitemap.xml route
        $router = $this->app->make('router');
        $router->register('/sitemap.xml', function() {
            $controller = $this->app->make(\Concrete\Package\GenerateMultisiteSitemaps\Controller\Sitemap::class);
            return $controller->view();
        });
    }
}