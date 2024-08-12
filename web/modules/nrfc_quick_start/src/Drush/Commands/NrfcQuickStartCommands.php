<?php

namespace Drupal\nrfc_quick_start\Drush\Commands;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
final class NrfcQuickStartCommands extends DrushCommands
{
  /**
   * Command description here.
   */
  #[CLI\Command(name: 'nrfc_quick_start:tearup', aliases: ['nrfctu'])]
  #[CLI\Argument(name: 'file', description: 'JOSN file to read data from.')]
  #[CLI\Option(name: 'clear', description: 'Delete all existing content')]
  #[CLI\Usage(name: 'nrfc_quick_start:tearup nrfctu', description: 'Usage description')]
  public function commandName($file, $options = [])
  {
    if (!file_exists($file)) {
      $this->logger->error(dt("No such file as @file", array("file" => $file)));
      exit(1);
    }
    $data = json_decode(file_get_contents($file), TRUE);

    // taxonomies
    if (array_key_exists('taxonomy', $data) && count($data['taxonomy']) > 0) {
      $this->importTaxonomies($data['taxonomy']);
    }
    // nodes
    if (array_key_exists('node', $data) && count($data['node']) > 0) {
      // people
      if (array_key_exists('person', $data['node']) && count($data['node']['person']) > 0) {
        $this->importPeople($data['node']['person']);
      }
      // teams
      if (array_key_exists('team', $data['node']) && count($data['node']['team']) > 0) {
        $this->importTeams($data['node']['team']);
      }
    }

    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * @throws EntityStorageException
   */
  private function importTaxonomies(mixed $taxonomies): void
  {
    foreach ($taxonomies as $taxonomy => $definition) {
      $vid = $this->slugify($taxonomy);
      if (!Vocabulary::load($vid)) {
        Vocabulary::create(['vid' => $vid, 'description' => $definition["description"], 'name' => $taxonomy])->save();
      }
      $this->addTerms($vid, $definition["terms"]);
    }
  }

  private function slugify($in)
  {
    return strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $in));
  }

  /**
   * @throws EntityStorageException
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function addTerms($vid, $terms, $parent = null): void
  {
    foreach ($terms as $term => $children) {
      $_term = $this->getTermByName($vid, $term);
      if (!$_term) {
        $_term = Term::create([
          "vid" => $vid,
          "name" => $term,
          "parent" => $parent
        ]);
        $_term->enforceIsNew();
        $_term->save();
      }
      if (count($children)>0) {
        $this->addTerms($vid, $children, $_term);
      }
    }
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function getTermByName(string $vid, string $term)
  {
    return Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term, 'vid' => $vid]);
  }

  private function importPeople(mixed $person)
  {
    foreach ($person as $definition) {
      if (!empty($definition['title'])) {
        $definition["type"] = "person";
        $node = $this->getOrCreateNode($definition);

        if (array_key_exists("field_email", $definition)) {
          $node->field_email = $this->uniqueFieldValues($node->get("field_email")->getValue(), $definition["field_email"]);
        }
        if (array_key_exists("field_phone_number", $definition)) {
          $node->field_phone_number = $this->uniqueFieldValues($node->get("field_phone_number")->getValue(), $definition["field_phone_number"]);
        }

        if (array_key_exists("field_headshot", $definition)) {
          $image_ids = $this->getImages($node->get("field_headshot")->getValue(), $definition["field_headshot"]);
          $images = [];
          foreach ($image_ids as $image_id) {
            $images[] = ['target_id' => $image_id, 'alt' => "Headshot for " . $definition['title']];
          }
          $node->field_headshot = $images;
        }
        $node->save();
      }
    }
  }

  private function getNodeByTitle(string $title)
  {
    $nodes = Drupal::entityTypeManager()->getStorage("node")->loadByProperties(['title' => $title]);
    if (count($nodes) > 0) {
      return reset($nodes);
    }
    return null;
  }

  private function getOrCreateNode($seedData)
  {
    $node = $this->getNodeByTitle($seedData['title']);
    if ($node != null) {
      return $node;
    }
    return Node::create(['type' => $seedData["type"], 'title' => $seedData["title"],]);
  }

  private function uniqueFieldValues($fieldList, $values)
  {
    $flattened = array_map(function ($x) {
      return $x["value"];
    }, $fieldList);
    foreach ($values as $value) {
      $flattened[] = $value;
    }
    return array_unique($flattened);
  }

  private function getImages($existingImages, mixed $field_images, $target="headshots")
  {
    $ids = array_map(function ($x) {
      return $x["target_id"];
    }, $existingImages);
    if (!is_dir("public://$target")) {
      mkdir("public://$target", 0775, true);
    }
    foreach ($field_images as $headshot) {
      if (!empty($headshot)) {
        $slug = $this->slugify(basename($headshot));
        $image_target_path = "public://$target/$slug.jpeg";
        if (file_exists($image_target_path)) {
          $image_objects = Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $image_target_path]);
          if (count($image_objects) > 0) {
            $ids[] = reset($image_objects)->id();
            if (count($image_objects) > 1) {
              // should only ever be one
              $this->logger()->warning(dt("Multiple images found for path @image_target_path", array('@image_target_path' => $image_target_path)));
            }
          }
        } else {
          $image_source_path = $headshot;
          $image_data = file_get_contents($image_source_path);
          $image_object = Drupal::service('file.repository')->writeData($image_data, $image_target_path);
          $ids[] = $image_object->id();
        }
      }
    }
    return array_unique($ids);
  }

  private function importTeams(mixed $team)
  {
    foreach ($team as $definition) {
      if (!empty($definition['title'])) {
        $definition["type"] = "team";
        $node = $this->getOrCreateNode($definition);

        if (array_key_exists("field_team_description", $definition)) {
          $node->field_team_description = $this->uniqueFieldValues(
            $node->field_team_description->getValue(),
            $definition["field_team_description"]
          );
        }

        if (array_key_exists("field_section", $definition)) {
          $terms = $this->getTermByName("team-section", $definition["field_section"]);
          $node->field_section = [ "target_id" => array_keys($terms)[0] ];
        }

        if (array_key_exists("field_photographs", $definition)) {
          $folder = $this->slugify($definition['title']);
          $image_ids = $this->getImages(
            $node->get("field_photographs")->getValue(),
            $definition['field_photographs'],
            "teams/$folder"
          );
          $images = [];
          foreach ($image_ids as $image_id) {
            $images[] = ['target_id' => $image_id, 'alt' => "Team photo of " . $definition['title']];
          }
          $node->field_photographs = $images;
        }

        if (array_key_exists("field_support_team", $definition)) {
          $node->field_support_team = [];
          foreach ($definition["field_support_team"] as $team_member) {
            $roles = [];
            foreach ($team_member["role"] as $role) {
              $roles[] = $this->getTermByName("role", $role);
            }
            $paragraph = Paragraph::create([
              'type' => 'volunteer',
              'field_person' => array(
                "value"  =>  [
                  "target_id" => $this->getNodeByTitle($team_member["person"])
                ],
              ),
              'field_roles' => array(
                "value"  =>  $roles,
              ),
            ]);
            $paragraph->save();

            $node->field_support_team[] = array(
              array(
                'target_id' => $paragraph->id(),
                'target_revision_id' => $paragraph->getRevisionId(),
              )
            );
          }
        }

        $node->save();
      }
    }
  }

}
