<?php

namespace Drupal\inline_entity_form\Tests;

/**
 * IEF complex field widget on user entity tests.
 *
 * @group inline_entity_form
 */
class ComplexWidgetUserWebTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inline_entity_form_test',
    'field',
    'field_ui',
  ];

  /**
   * URL to add new content.
   *
   * @var string
   */
  protected $formContentAddUrl;

  /**
   * Entity form display storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $entityFormDisplayStorage;

  /**
   * Prepares environment for tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_test_user_complex content',
      'edit any ief_test_user_complex content',
      'delete any ief_test_user_complex content',
      'administer content types',
      'administer users',
      'administer permissions',
    ]);
    $this->drupalLogin($this->user);

    $this->formContentAddUrl = 'node/add/ief_test_user_complex';
    $this->entityFormDisplayStorage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
  }

  /**
   * Tests if form behaves correctly when field is empty.
   */
  public function testEmptyFieldIef() {
    $this->drupalGet($this->formContentAddUrl);

    $user_fields = array(
      'mail' => 'Email',
      'name' => 'Username',
      'pass[pass1]' => 'Password',
      'pass[pass2]' => 'Password confirm',
      'status' => 'Status',
      'roles[authenticated]' => 'Roles',
      'notify' => 'Notify user',
      'timezone' => 'Timezone',
      'field_user_reference_multiple[form][inline_entity_form][field_first_name][0][value]' => 'First name',
    );

    foreach ($user_fields as $field_name => $message) {
      $this->assertNoFieldByName($field_name, NULL, $message . ' field does not appear.');
    }
    $this->assertFieldByXpath('//input[@type="submit" and @value="Add new user"]', NULL, 'Found "Add new user" submit button');
    $this->assertFieldByXpath('//input[@type="submit" and @value="Add existing user"]', NULL, 'Found "Add existing user" submit button');

    // Now submit 'Add new user' button.
    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Add new user" and @data-drupal-selector="edit-field-user-reference-multiple-actions-ief-add"]'));

    foreach ($user_fields as $field_name => $message) {
      $this->assertFieldByName($field_name, NULL, $message . ' field on inline form exists.');
    }
    $this->assertFieldByXpath('//input[@type="submit" and @value="Create user"]', NULL, 'Found "Create user" submit button');
    $this->assertFieldByXpath('//input[@type="submit" and @value="Cancel"]', NULL, 'Found "Cancel" submit button');
  }

  /**
   * Tests creation of entities.
   */
  public function testEntityCreation() {
    $this->drupalGet($this->formContentAddUrl);

    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Add new user" and @data-drupal-selector="edit-field-user-reference-multiple-actions-ief-add"]'));
    $this->assertResponse(200, 'Opening new inline form was successful.');

    // TODO: currently this fail. It only works if the form has no error.
//    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Create user" and @data-drupal-selector="edit-field-user-reference-multiple-form-inline-entity-form-actions-ief-add-save"]'));
//    $this->assertResponse(200, 'Submitting empty form was successful.');
//    $this->assertText('Username field is required.', 'Validation failed for empty "Username" field.');
//    $this->assertText('Password field is required.', 'Validation failed for empty "Password" field.');

    // Create user in IEF.
    $this->drupalGet($this->formContentAddUrl);
    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Add new user" and @data-drupal-selector="edit-field-user-reference-multiple-actions-ief-add"]'));
    $this->assertResponse(200, 'Opening new inline form was successful.');

    $edit = [
      'name' => 'Doe',
      'pass[pass1]' => 'mypassword',
      'pass[pass2]' => 'mypassword',
      'field_user_reference_multiple[form][inline_entity_form][field_first_name][0][value]' => 'John',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName('//input[@type="submit" and @value="Create user" and @data-drupal-selector="edit-field-user-reference-multiple-form-inline-entity-form-actions-ief-add-save"]'));
    $this->assertResponse(200, 'Creating user via inline form was successful.');

    // Tests if correct fields appear in the table.
    $this->assertTrue((bool) $this->xpath('//td[@class="inline-entity-form-user-label" and contains(.,"Doe")]'), 'Username field appears in the table');

    // Tests if edit and remove buttons appear.
    $this->assertTrue((bool) $this->xpath('//input[@type="submit" and @value="Edit"]'), 'Edit button appears in the table.');
    $this->assertTrue((bool) $this->xpath('//input[@type="submit" and @value="Remove"]'), 'Remove button appears in the table.');

    // Test edit functionality.
    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Edit"]'));
    $edit = [
      'name' => 'Another name',
      'field_user_reference_multiple[form][inline_entity_form][entities][0][form][field_first_name][0][value]' => 'Another first name',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName('//input[@type="submit" and @value="Update user"]'));
    $this->assertTrue((bool) $this->xpath('//td[@class="inline-entity-form-user-label" and contains(.,"Another name")]'), 'Username field appears in the table');

    // Create ief_test_user_complex node.
    $edit = ['title[0][value]' => 'Some title'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200, 'Saving parent entity was successful.');

    // Checks values of created entities.
    $node = $this->drupalGetNodeByTitle('Some title');
    $this->assertTrue($node, 'Created ief_test_user_complex node ' . $node->label());
    /** @var \Drupal\user\Entity\User $user */
    $user = $node->get('field_user_reference_multiple')->referencedEntities()[0];
    $this->assertTrue($user->getAccountName() == 'Another name', 'Username in referenced user set to Another name');
    $this->assertTrue($user->get('field_first_name')->value == 'Another first name', 'First name in referenced user set to Another first name');
  }

}
