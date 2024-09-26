<?php

namespace Drupal\nrfc_league_table_scraper\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\File\FileSystemInterface;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
class NrfcLeagueTableScraperEngine
{
    private ConfigFactoryInterface $config_factory;
    private ExtensionList $extension_list;
    private FileSystemInterface $file_system;

    public function __construct(
        ConfigFactoryInterface $config_factory,
        ExtensionList          $extension_list,
        FileSystemInterface    $file_system
    )
    {
        $this->config_factory = $config_factory;
        $this->extension_list = $extension_list;
        $this->file_system = $file_system;

        $file_system = \Drupal::service('file_system');
        $file_system->prepareDirectory(
            $this->directory,
            FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
        );
    }

    public function updateAll(): void
    {
        $teams = $this->getTeams();
        foreach ($teams as $team) {
            $this->updateTeam($team);
        }
    }

    public function getTeams(): array
    {
        return [
            "nid_138" => [
                "nid" => 138,
                "teamId" => "15036",
                "competition" => "261",
                "division" => "56597",
            ],
        ];
    }

    public function updateTeam(array $team)
    {
        if (!(
            in_array("nid", $team) &&
            in_array("teamId", $team) &&
            in_array("competition", $team) &&
            in_array("division", $team)
        )) {
            \Drupal::logger('nrfc_league_table_scraper')
                ->warning("Badly formatted scarper config: " . json_encode($team));
            return;
        }

        $node = \Drupal\node\Entity\Node::load($team['nid']);
        $filename = $this->makeFilePath($node->getTitle());
        if ($this->isExpired($filename)) {
            $this->fetch($team, $filename);
        } else {
            $this->parse($team, $filename);
        }
    }

    public function makeFilePath($teamName): string
    {
        $directory = 'public://nrfc_scraper';
        $fileName = $this->makeFileName($teamName);
        $filePath = $this->extension_list->getPath(
                'nrfc_league_table_scraper') . '/scraper/' . $fileName . '.html';
        $this->file_system->prepareDirectory(
            $directory,
            FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
        );
        return $filePath;
    }

    protected function makeFileName($teamName): string
    {
        return strtolower(trim(
            preg_replace('#\W+#', '_', $teamName, '_')
        ));
    }

    public function isExpired($filename): boolean
    {

        return false;
    }

    public function fetch($teamConfig, $filename): void
    {
//        $file = file_save_data($data, 'public://my-dir/MY_FILE.txt', FileSystemInterface::EXISTS_REPLACE);
    }

    public function parse(array $team, string $filename)
    {
        /** @var \Drupal\Core\Extension\ExtensionList $extension_list */
        $extension_list = \Drupal::service('extension.list.module');
        $filepath = $extension_list->getPath('MY_MODULE') . '/assets/MY_FILE.txt';

        $directory = 'public://my-dir';
        /** @var \Drupal\Core\File\FileSystemInterface $file_system */
        $file_system = \Drupal::service('file_system');
        $file_system->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
        $file_system->copy($filepath, $directory . '/' . basename($filepath), FileSystemInterface::EXISTS_REPLACE);
    }

}
