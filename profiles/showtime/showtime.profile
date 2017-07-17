<?php

/**
 * @file
 * Defines the Showtime Profile install screen by modifying the install form.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_install_tasks().
 */
function showtime_install_tasks() {
  return [
    'showtime_install_extensions' => [
      'display_name' => t('Install Extensions'),
      'display' => TRUE,
      'type' => 'batch',
    ],
    'showtime_install_terms' => [
      'display_name' => t('Install Terms'),
      'type' => 'normal',
    ],
  ];
}

/**
 * Implements hook_install_tasks_alter().
 */
function showtime_install_tasks_alter(array &$tasks, array $install_state) {
  $tasks['install_finished']['function'] = 'showtime_post_install_redirect';
}

/**
 * Install task callback; prepares a batch job to install Showtime extensions.
 *
 * @param array $install_state
 *   The current install state.
 *
 * @return array
 *   The batch job definition.
 */
function showtime_install_extensions(array &$install_state) {
  $batch = [];
  $modules = [
    'showtime_deploy',
    'showtime_subs_rep',
    'showtime_subscription',
    'showtime_subscription_listing',
    'showtime_theme_settings',
  ];
  foreach ($modules as $module) {
    $batch['operations'][] = ['showtime_install_module', (array) $module];
  }
  return $batch;
}

/**
 * Batch API callback. Installs a module.
 *
 * @param string|array $module
 *   The name(s) of the module(s) to install.
 */
function showtime_install_module($module) {
  \Drupal::service('module_installer')->install((array) $module);
}

/**
 * Install task callback; Adds defualt terms to taxonomy.
 *
 * @param array $install_state
 *   The current install state.
 */
function showtime_install_terms(array &$install_state) {
  $vid = 'season';
  $terms = [
    '2014 - 2015',
    '2015 - 2016',
    '2016 - 2017',
    '2017 - 2018',
  ];

  foreach ($terms as $weight => $term) {
    $tid = Term::create([
      'name' => $term,
      'vid' => $vid,
      'weight' => $weight,
    ])->save();
  }
}

/**
 * Redirects the user to a particular URL after installation.
 *
 * @param array $install_state
 *   The current install state.
 *
 * @return array
 *   A renderable array with a success message and a redirect header, if the
 *   extender is configured with one.
 */
function showtime_post_install_redirect(array &$install_state) {
  $redirect = \Drupal::request()->getSchemeAndHttpHost();

  $output = [
    '#title' => t('Ready to go!'),
    'info' => [
      '#markup' => t('Congratulations, you installed your site! If you are not redirected in 5 seconds, <a href="@url">click here</a> to proceed to your site.', [
        '@url' => $redirect,
      ]),
    ],
    '#attached' => [
      'http_header' => [
        ['Cache-Control', 'no-cache'],
      ],
    ],
  ];

  // The installer doesn't make it easy (possible?) to return a redirect
  // response, so set a redirection META tag in the output.
  $meta_redirect = [
    '#tag' => 'meta',
    '#attributes' => [
      'http-equiv' => 'refresh',
      'content' => '0;url=' . $redirect,
    ],
  ];
  $output['#attached']['html_head'][] = [$meta_redirect, 'meta_redirect'];

  return $output;
}
