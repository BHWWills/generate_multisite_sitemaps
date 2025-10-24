<?php
namespace Concrete\Package\GenerateMultisiteSitemaps\Task;

use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Command\Task\Runner\ProcessTaskRunner;
use Concrete\Package\GenerateMultisiteSitemaps\Command\GenerateMultisiteSitemapsCommand;

defined('C5_EXECUTE') or die("Access Denied.");

class GenerateMultiSitemapsController extends AbstractController
{
    public function getName(): string
    {
        return t('Generate Multi-Sitemaps');
    }

    public function getDescription(): string
    {
        return t('Generates a sitemap.xml for multiple sites.');
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $command = new GenerateMultisiteSitemapsCommand();
        
        return new ProcessTaskRunner(
            $task,
            $command,
            $input,
            t('Generating multisite sitemaps...')
        );
    }
}