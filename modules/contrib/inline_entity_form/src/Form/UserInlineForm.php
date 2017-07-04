<?php

namespace Drupal\inline_entity_form\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * User inline form handler.
 */
class UserInlineForm extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => $this->t('user'),
      'plural' => $this->t('users'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity = $entity_form['#entity'];
    // Get standard User form to take account and other elements.
    // We need to do this, because a bunch of standard fields for a user
    // cannot be loaded by a common way. For example 'name' or 'mail'.
    // They have no widgets, but defined in AccountForm.
    // @see EntityFormDisplay->collectRenderDisplay().
    $form = \Drupal::service('entity.form_builder')
      ->getForm($entity, 'default');
    $keys = ['account', 'contact', 'language', 'timezone'];
    foreach ($keys as $key) {
      if (!empty($form[$key])) {
        $entity_form[$key] = $form[$key];
      }
    }

    $entity_form = parent::entityForm($entity_form, $form_state);

    // We should save user otherwise values will be not set in
    // AccountForm->form.
    $entity_form['#save_entity'] = TRUE;
    return $entity_form;
  }

  /**
   * Builds an updated entity object based upon the submitted form values.
   */
  protected function buildEntity(array $entity_form, ContentEntityInterface $entity, FormStateInterface $form_state) {
    // Reuse logic from class EntityForm.
    $this->copyFormValuesToEntity($entity, $entity_form, $form_state);

    // Invoke all specified builders for copying form values to entity fields.
    if (isset($entity_form['#entity_builders'])) {
      foreach ($entity_form['#entity_builders'] as $function) {
        call_user_func_array($function, [
          $entity->getEntityTypeId(),
          $entity,
          &$entity_form,
          &$form_state,
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(ContentEntityInterface $entity, array $entity_form, FormStateInterface $form_state) {
    // First, extract values from widgets.
    $extracted = $this->getFormDisplay($entity, $entity_form['#form_mode'])
      ->extractFormValues($entity, $entity_form, $form_state);

    $user_input = $form_state->getUserInput();

    // We cannot use 'values' of the form state, because they are empty.
    // And they are empty because form was created manually in method
    // entityForm of this class.
    foreach ($user_input as $name => $values) {
      // $form_state is mixed and consists of values of parent and child
      // entities. We have to exclude values of properties 'uid', 'created' and
      // 'changed'. 'langcode' has been excluded because it's null, but
      // shouldn't be null.
      // @todo case with langcode requires additional investigation.
      if (!in_array($name, ['langcode', 'uid', 'created', 'changed', 'role'])) {
        // Handle password.
        // We need this hack because element with type password_confirm
        // cannot be processed normally in function validatePasswordConfirm.
        if ($name == 'pass') {
          if (!empty($values['pass1'])) {
            $entity->set('pass', $values['pass1']);
          }
          else {
            $entity->set('pass', '');
          }
        }
        // Other fields.
        elseif ($entity->hasField($name) && !isset($extracted[$name])) {
          $entity->set($name, $values);
        }
      }
    }
    // Handle field role separately.
    // @see AccountForm->buildEntity().
    $roles = [];
    if (!empty($user_input['roles'])) {
      $roles = array_keys(array_filter($user_input['roles']));
    }
    $entity->set('roles', $roles);
  }

  /**
   * {@inheritdoc}
   */
  public static function submitCleanFormState(&$entity_form, FormStateInterface $form_state) {
    // Handle saving of the value for module contact.
    // @see contact_user_profile_form_submit().
    if (\Drupal::service('module_handler')->moduleExists('contact')) {
      $entity = $entity_form['#entity'];
      $contact = !empty($form_state->getUserInput()['contact']) ? 1 : 0;
      if ($entity->id()) {
        \Drupal::service('user.data')
          ->set('contact', $entity->id(), 'enabled', $contact);
      }
    }
    parent::submitCleanFormState($entity_form, $form_state);
  }

}
