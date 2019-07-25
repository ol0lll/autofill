<?php

namespace Drupal\Tests\autofill\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Autofill module.
 *
 * @group autofill
 */

class AutofillJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'node',
    'autofill',
  ];

  /**
   * The content type
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $contentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->contentType = $this->drupalCreateContentType(['type' => 'article']);
    $this->setupFields();
  }

  /**
   * Tests the autofill of a new field based on the node title.
   */
  public function testAutofillFromAnotherField() {
    $this->drupalLogin($this->rootUser);
    $this->configureAutofillFields();

    // Start the actual test.
    $this->drupalGet('node/add/' . $this->contentType->id());
    $this->getSession()->getPage()->fillField('title[0][value]', 'My test title');
    // The autofill field should have the same value as the title.
    $this->assertSession()->fieldValueEquals('field_autofill[0][value]', 'My test title');
    $this->getSession()->getPage()->findButton('Save')->click();

    // Open the created node again. When changing the title, the autofill
    // field should remain unchanged, because it's already filled.
    $this->drupalGet('node/1/edit');
    $this->getSession()->getPage()->fillField('title[0][value]', 'My adjusted test title');
    $this->assertSession()->fieldValueEquals('field_autofill[0][value]', 'My test title');

    // If the autofill field was manipulated once it should not be autofilled
    // anymore. Manipulation is done by pressing backspace in the textfield.
    $this->drupalGet('node/add/' . $this->contentType->id());
    $autofill_field = $this->getSession()->getPage()->findField('field_autofill[0][value]');
    $autofill_field->keyPress(8);
    $this->getSession()->getPage()->fillField('title[0][value]', 'My adjusted test title');
    $this->assertSession()->fieldValueEquals('field_autofill[0][value]', '');
  }

  /**
   * Creates the necessary fields.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setupFields() {
    FieldStorageConfig::create([
      'field_name' => 'field_autofill',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_autofill',
      'bundle' => $this->contentType->id(),
      'label' => 'Autofill field',
    ])->save();
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_autofill')
      ->save();
  }

  /**
   * Configures the autofill fields.
   */
  protected function configureAutofillFields() {
    // Open the "Manage form display" page.
    $this->drupalGet('admin/structure/types/manage/'. $this->contentType->id() . '/form-display');

    // Configure the autofill field.
    $this->click('[name="field_autofill_settings_edit"]');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $autofill_enable_checkbox = $this->assertSession()->elementExists('css', 'input[name="fields[field_autofill][settings_edit_form][third_party_settings][autofill][enabled]"]');
    $autofill_enable_checkbox->check();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $autofill_source_field_select = $this->assertSession()->elementExists('css','select[name="fields[field_autofill][settings_edit_form][third_party_settings][autofill][source_field]"]');

    // Set the "title" to be the autofill source field.
    $autofill_source_field_select->selectOption('title');
    $this->getSession()->getPage()->findButton('Update')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Save the configuration.
    $this->getSession()->getPage()->findButton('Save')->click();

  }

}
