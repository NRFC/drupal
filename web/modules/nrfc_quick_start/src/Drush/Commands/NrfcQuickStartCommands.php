<?php

namespace Drupal\nrfc_quick_start\Drush\Commands;

use DOMDocument;
use DOMXPath;
use Drupal;
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

    foreach ($data as $type => $content) {
      switch ($type) {
        case "taxonomy":
          $this->importTaxonomies($data['taxonomy']);
          break;
        case "person":
          $this->importPeople($data['person']);
          break;
        case "team":
          $this->importTeams($data['team']);
          break;
        case "article":
          $this->importArticles($data['article']);
          break;
        case "page":
          $this->importPages($data['page']);
          break;
        default:
          $this->logger()->warning(dt("No data for key @type", ["@type" => $type]));
      }
    }

    $this->logger()->success(dt('Import done.'));
  }


  protected function importTaxonomies(mixed $taxonomies): void
  {
    $this->logger()->notice(dt("Importing terms"));
    $count = 0;
    foreach ($taxonomies as $taxonomy => $definition) {
      $vid = slugify($taxonomy);
      if (!Vocabulary::load($vid)) {
        $this->logger->notice(dt("Creating vocab @vocabulary.", ['vocabulary' => $taxonomy]));
        $this->logger->warning(dt("You will need to set up reference fields innode types"));
        Vocabulary::create(['vid' => $vid, 'description' => $definition["description"], 'name' => $taxonomy])->save();
      }
      $this->logger()->notice(dt("Processing terms for @vocab.", ["@vocab" => $vid]));
      $count += addTerms($vid, $definition["terms"]);
    }
    $this->logger()->notice(dt("Imported @count terms.", ["@count" => $count]));
  }


  protected function importPeople(mixed $person): void
  {
    $this->logger()->notice(dt("Importing people"));
    foreach ($person as $definition) {
      if (!empty($definition['title'])) {
        $definition["type"] = "person";
        $node = getOrCreateNode($definition);

        if (array_key_exists("field_email", $definition)) {
          $node->field_email = $definition["field_email"];
        }
        if (array_key_exists("field_phone_number", $definition)) {
          $node->field_phone_number = $definition["field_phone_number"];
        }

        if (array_key_exists("field_headshot", $definition)) {
          $image_ids = getImages($node->get("field_headshot")->getValue(), $definition["field_headshot"]);
          $images = [];
          foreach ($image_ids as $image_id) {
            $images[] = ['target_id' => $image_id, 'alt' => "Headshot for " . $definition['title']];
          }
          $node->field_headshot = $images;
        }
        $node->save();
      }
    }
    $this->logger()->notice(dt("Imported @count people.", ["@count" => count($person)]));
  }

  protected function importTeams(mixed $team): void
  {
    # TODO switch this to pull data from the live site
    foreach ($team as $definition) {
      if (!empty($definition['title'])) {
        $definition["type"] = "team";
        $node = $this->getOrCreateNode($definition);

        if (array_key_exists("field_team_description", $definition)) {
          $description = $this->parseDescription($definition["field_team_description"]);
          if (empty($description)) {
            $this->logger->warning(dt("No description found for @file", ['@file' => $definition["title"]]));
          }
          $node->field_team_description = $description;
        }

        if (array_key_exists("field_section", $definition)) {
          $terms = $this->getTermByName("team-section", $definition["field_section"]);
          $node->field_section = ["target_id" => array_keys($terms)[0]];
        }

        if (array_key_exists("field_photographs", $definition)) {
          $image_ids = $this->getImages(
            $node->get("field_photographs")->getValue(),
            $definition['field_photographs'],
            "teams/carousel"
          );
          $images = [];
          foreach ($image_ids as $image_id) {
            $images[] = ['target_id' => $image_id, 'alt' => "Carousel photo of " . $definition['title']];
          }
          $node->field_photographs = $images;
        }

        if (array_key_exists("field_team_photo", $definition)) {
          $image_id = $this->getImages([], [$definition["field_team_photo"]], "teams/team_photos");
          if (count($image_id) > 0) {
            $node->field_team_photo = ['target_id' => $image_id, 'alt' => "Carousel photo of " . $definition['title']];
          }
        }

        if (array_key_exists("field_support_team", $definition)) {
          $node->field_support_team = [];
          foreach ($definition["field_support_team"] as $team_member) {
            $roles = [];
            foreach ($team_member["role"] as $role) {
              $roles[] = $this->getTermByName("role", $role);
            }
            $person = $this->getNodeByTitle($team_member["person"]);
            if ($person == null) {
              $this->logger->warning(sprintf(
                "Could not find a person named %s (team: %s)",
                $team_member["person"],
                $definition['title']
              ));
              continue;
            }
            $paragraph = Paragraph::create([
              'type' => 'volunteer',
              'field_person' => array(
                "value" => [
                  "target_id" => $person->id()
                ],
              ),
              'field_roles' => array(
                "value" => $roles,
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

  private function importArticles($definitions): void
  {
    foreach ($definitions as $url) {
      $node = $this->_import($url, "article");
      $node->save();
    }
  }

  private function _import($url, $type): Node
  {
    $xpath = fetchPage($url);

    $title = extractTitle($xpath);
    $content = extractMainContent($xpath);
    $carouselImageUrls = extractCarouselImages($xpath);

    $definition["title"] = $title;
    $definition["type"] = $type;
    $node = getOrCreateNode($definition);
    $node->body = $content;

    $image_ids = getImages(
      [],
      $carouselImageUrls,
      "news"
    );
    $images = [];
    foreach ($image_ids as $image_id) {
      $images[] = ['target_id' => $image_id, 'alt' => "News image of " . $definition['title']];
    }
    $node->field_image = $images;
    return $node;
  }

  private function importPages($definitions): void
  {
    foreach ($definitions as $url) {
      $node = $this->_import($url, "page");
      $node->save();
    }
  }


}

function extractTitle($xpath): string|null
{
  $h1 = $xpath->query('//h1');
  if ($h1->length >= 0) {
    return $h1[-1]->nodeValue;
  }
  return "Not found";
}


function extractMainContent($xpath): array
{
//  $content = $xpath->query('//div[@class="main-content"]');
  $content = $xpath->query("//div[contains(@class, 'main-content')]");
  if ($content->length === 0) {
    return ["Content not found"];
  }
  return array_filter(array_map(function ($x) {
    return $x->nodeValue;
  }, iterator_to_array($content)));
}

function extractCarouselImages($xpath): array
{
  $images = $xpath->query('//div[@class="swiper-slide"]/img');
  if ($images->length === 0) {
    return [];
  }
  return array_map(function ($x) {
    return $x->getAttribute("src");
  }, iterator_to_array($images));
}


function fetchPage($url): DOMXPath
{
  $document = file_get_contents($url);
  file_put_contents("/tmp/" . basename($url) . ".html", $document);
  $dom = new DOMDocument();
  @$dom->loadHTML($document);
  return new DOMXPath($dom);
}


function slugify($in)
{
  return strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $in));
}

function addTerms($vid, $terms, $parent = null): int
{
  $count = count($terms);
  foreach ($terms as $term => $children) {
    $_term = getTermByName($vid, $term);
    if (!$_term) {
      $_term = Term::create(["vid" => $vid, "name" => $term, "parent" => $parent]);
      $_term->enforceIsNew();
      $_term->save();
    }
    if (count($children) > 0) {
      $count += addTerms($vid, $children, $_term);
    }
  }
  return $count;
}

function getTermByName(string $vid, string $term): array
{
  return Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term, 'vid' => $vid]);
}


function getOrCreateNode($seedData)
{
  $node = getNodeByTitle($seedData['title']);
  if ($node != null) {
    return $node;
  }
  return Node::create(['type' => $seedData["type"], 'title' => $seedData["title"],]);
}

function getNodeByTitle(string $title)
{
  $nodes = Drupal::entityTypeManager()->getStorage("node")->loadByProperties(['title' => $title]);
  if (count($nodes) > 0) {
    return reset($nodes);
  }
  return null;
}

function getImages($existingImages, mixed $field_images, $target = "headshots"): array
{
  $ids = array_map(function ($x) {
    return $x["target_id"];
  }, $existingImages);
  if (!is_dir("public://$target")) {
    mkdir("public://$target", 0775, true);
  }
  foreach ($field_images as $headshot) {
    if (!empty($headshot)) {
      $slug = slugify(basename($headshot));
      $image_target_path = "public://$target/$slug.jpeg";
      if (file_exists($image_target_path)) {
        $image_objects = Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $image_target_path]);
        if (count($image_objects) > 0) {
          $ids[] = reset($image_objects)->id();
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
