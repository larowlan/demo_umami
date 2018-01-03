<?php

namespace Drupal\demo_umami_content;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a helper class for importing default content.
 */
class InstallHelper implements ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new InstallHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler, StateInterface $state) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * Imports articles.
   *
   * @return $this
   */
  public function importArticles() {
    $module_path = $this->moduleHandler->getModule('demo_umami_content')
      ->getPath();
    if (($handle = fopen($module_path . '/default_content/articles.csv', "r")) !== FALSE) {
      $uuids = [];
      $header = fgetcsv($handle);
      while (($data = fgetcsv($handle)) !== FALSE) {
        $data = array_combine($header, $data);
        // Prepare content.
        $values = [
          'type' => 'article',
          'title' => $data['title'],
        ];
        // Fields mapping starts.
        // Set Body Field.
        if (!empty($data['body'])) {
          $values['body'] = [['value' => $data['body'], 'format' => 'basic_html']];
        }
        // Set node alias if exists.
        if (!empty($data['slug'])) {
          $values['path'] = [['alias' => '/' . $data['slug']]];
        }
        // Set field_tags if exists.
        if (!empty($data['tags'])) {
          $values['field_tags'] = [];
          $tags = explode(',', $data['tags']);
          foreach ($tags as $term) {
            $values['field_tags'][] = ['target_id' => $this->getTerm($term)];
          }
        }
        // Set article author.
        if (!empty($data['author'])) {
          $values['uid'] = $this->getUser($data['author']);
        }
        // Set Image field.
        if (!empty($data['image'])) {
          $path = $module_path . '/default_content/images/' . $data['image'];
          $values['field_image'] = ['target_id' => $this->getImage($path)];
        }

        // Create Node.
        $node = $this->entityTypeManager->getStorage('node')->create($values);
        $node->save();
        $uuids[$node->uuid()] = 'node';
      }
      $this->storeCreatedContentUuids($uuids);
      fclose($handle);
    }
    return $this;
  }

  /**
   * Imports recipes.
   *
   * @return $this
   */
  public function importRecipes() {
    $module_path = $this->moduleHandler->getModule('demo_umami_content')->getPath();

    if (($handle = fopen($module_path . '/default_content/recipes.csv', "r")) !== FALSE) {
      $header = fgetcsv($handle);
      $uuids = [];
      while (($data = fgetcsv($handle)) !== FALSE) {
        $data = array_combine($header, $data);
        $values = [
          'type' => 'recipe',
        // Title field.
          'title' => $data['title'],
        ];
        // Set article author.
        if (!empty($data['author'])) {
          $values['uid'] = $this->getUser($data['author']);
        }
        // Set field_image field.
        if (!empty($data['image'])) {
          $image_path = $module_path . '/default_content/images/' . $data['image'];
          $values['field_image'] = ['target_id' => $this->getImage($image_path)];
        }
        // Set field_summary Field.
        if (!empty($data['summary'])) {
          $values['field_summary'] = [['value' => $data['summary'], 'format' => 'basic_html']];
        }
        // Set field_recipe_category if exists.
        if (!empty($data['recipe_category'])) {
          $values['field_recipe_category'] = [];
          $tags = array_filter(explode(',', $data['recipe_category']));
          foreach ($tags as $term) {
            $values['field_recipe_category'][] = ['target_id' => $this->getTerm($term, 'recipe_category')];
          }
        }
        // Set field_preparation_time Field.
        if (!empty($data['preparation_time'])) {
          $values['field_preparation_time'] = [['value' => $data['preparation_time']]];
        }
        // Set field_cooking_time Field.
        if (!empty($data['cooking_time'])) {
          $values['field_cooking_time'] = [['value' => $data['cooking_time']]];
        }
        // Set field_difficulty Field.
        if (!empty($data['difficulty'])) {
          $values['field_difficulty'] = $data['difficulty'];
        }
        // Set field_number_of_servings Field.
        if (!empty($data['number_of_servings'])) {
          $values['field_number_of_servings'] = [['value' => $data['number_of_servings']]];
        }
        // Set field_ingredients Field.
        if (!empty($data['ingredients'])) {
          $ingredients = explode(',', $data['ingredients']);
          $values['field_ingredients'] = [];
          foreach ($ingredients as $ingredient) {
            $values['field_ingredients'][] = ['value' => $ingredient];
          }
        }
        // Set field_recipe_instruction Field.
        if (!empty($data['recipe_instruction'])) {
          $values['field_recipe_instruction'] = [['value' => $data['recipe_instruction'], 'format' => 'basic_html']];
        }
        // Set field_tags if exists.
        if (!empty($data['tags'])) {
          $values['field_tags'] = [];
          $tags = array_filter(explode(',', $data['tags']));
          foreach ($tags as $term) {
            $values['field_tags'][] = ['target_id' => $this->getTerm($term)];
          }
        }

        $node = $this->entityTypeManager->getStorage('node')->create($values);
        $node->save();
        $uuids[$node->uuid()] = 'node';
      }
      $this->storeCreatedContentUuids($uuids);
      fclose($handle);
    }
    return $this;
  }

  /**
   * Imports pages.
   *
   * @return $this
   */
  public function importPages() {
    if (($handle = fopen($this->moduleHandler->getModule('demo_umami_content')->getPath() . '/default_content/pages.csv', "r")) !== FALSE) {
      $headers = fgetcsv($handle);
      $uuids = [];
      while (($data = fgetcsv($handle)) !== FALSE) {
        $data = array_combine($headers, $data);

        // Prepare content.
        $values = [
          'type' => 'page',
          'title' => $data['title'],
        ];
        // Fields mapping starts.
        // Set Body Field.
        if (!empty($data['body'])) {
          $values['body'] = [['value' => $data['body'], 'format' => 'basic_html']];
        }
        // Set node alias if exists.
        if (!empty($data['slug'])) {
          $values['path'] = [['alias' => '/' . $data['slug']]];
        }
        // Set article author.
        if (!empty($data['author'])) {
          $values['uid'] = $this->getUser($data['author']);
        }

        // Create Node.
        $node = $this->entityTypeManager->getStorage('node')->create($values);
        $node->save();
        $uuids[$node->uuid()] = 'node';
      }
      $this->storeCreatedContentUuids($uuids);
      fclose($handle);
    }
    return $this;
  }

  /**
   * Imports block contents.
   *
   * @return $this
   */
  public function importBlockContent() {
    $block_content_metadata = [
      'umami_recipes_banner' => [
        'uuid' => '4c7d58a3-a45d-412d-9068-259c57e40541',
        'info' => 'Umami Recipes Banner',
        'type' => 'banner_block',
        'field_title' => [
          'value' => 'Baked Camembert with garlic, calvados and salami',
        ],
        'field_content_link' => [
          'target_id' => 10,
        ],
        'field_summary' => [
          'value' => 'Nullam id dolor id nibh ultricies vehicula ut id elit. Nullam id dolor id nibh ultricies vehicula ut id elit. Nullam id dolor id nibh ultricies vehicula ut id elit.',
        ],
        'field_banner_image' => [
          'target_id' => $this->getImage(drupal_get_path('theme', 'umami') . '/' . 'images/jpg/placeholder--atharva-lele-210748-pshopped.jpg'),
        ],
      ],
    ];

    // Create block content.
    foreach ($block_content_metadata as $block) {
      $block_content = $this->entityTypeManager->getStorage('block_content')->create($block);
      $block_content->save();
    }
    return $this;
  }

  /**
   * Deletes any content imported by this module.
   *
   * @return $this
   */
  public function deleteImportedContent() {
    $uuids = $this->state->get('demo_umami_content_uuids', []);
    $by_entity_type = array_reduce(array_keys($uuids), function ($carry, $uuid) use ($uuids) {
      $entity_type_id = $uuids[$uuid];
      $carry[$entity_type_id][] = $uuid;
      return $carry;
    }, []);
    foreach ($by_entity_type as $entity_type_id => $entity_uuids) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entities = $storage->loadByProperties(['uuid' => $entity_uuids]);
      $storage->delete($entities);
    }
    return $this;
  }

  /**
   * Looks up a user by name.
   *
   * @param string $name
   *   Username.
   *
   * @return int
   *   User ID.
   */
  protected function getUser($name) {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $users = $user_storage->loadByProperties(['name' => $name]);;
    if (empty($users)) {
      // Creating user without any email/password.
      $user = $user_storage->create([
        'name' => $name,
      ]);
      $user->enforceIsNew();
      $user->save();
      $this->storeCreatedContentUuids([$user->uuid() => 'user']);
      return $user->id();
    }
    $user = reset($users);
    return $user->id();
  }

  /**
   * Looks up a term by name.
   *
   * @param string $term_name
   *   Term name.
   * @param string $vocabulary_id
   *   Vocabulary ID.
   *
   * @return int
   *   Term ID.
   */
  protected function getTerm($term_name, $vocabulary_id = 'tags') {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'name' => $term_name,
      'vid' => $vocabulary_id,
    ]);
    if (!$terms) {
      $term = $term_storage->create([
        'name' => $term_name,
        'vid' => $vocabulary_id,
      ]);
      $term->save();
      $this->storeCreatedContentUuids([$term->uuid() => 'taxonomy_term']);
      return $term->id();
    }
    $term = reset($terms);
    return $term->id();
  }

  /**
   * Looks up a file entity based on an image path.
   *
   * @param string $path
   *   Image path.
   *
   * @return int
   *   File ID.
   */
  protected function getImage($path) {
    $uri = $this->fileUnmanagedCopy($path);
    $file = $this->entityTypeManager->getStorage('file')->create([
      'uri' => $uri,
      'status' => 1,
    ]);
    $file->save();
    $this->storeCreatedContentUuids([$file->uuid() => 'file']);
    return $file->id();
  }

  /**
   * Stores record of content entities created by this import.
   *
   * @param array $uuids
   *   Array of UUIDs where the key is the UUID and the value is the entity
   *   type.
   */
  protected function storeCreatedContentUuids(array $uuids) {
    $uuids = $this->state->get('demo_umami_content_uuids', []) + $uuids;
    $this->state->set('demo_umami_content_uuids', $uuids);
  }

  /**
   * Wrapper around file_unmanaged_copy().
   *
   * @param string $path
   *   Path to image.
   *
   * @return bool|null
   */
  protected function fileUnmanagedCopy($path) {
    $filename = basename($path);
    return file_unmanaged_copy($path, 'public://' . $filename, FILE_EXISTS_REPLACE);
  }

}
