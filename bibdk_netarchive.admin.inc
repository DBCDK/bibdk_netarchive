<?php

/**
 * @file
 * Admin settings for moreInfo client
 */

/**
 * Implements hook_form_FORM_ID_alter (ting_client_admin_webservices_settings)
 * Add fields to webservice client settings
 * */
function bibdk_netarchive_form_ting_client_admin_webservices_settings_alter(&$form, &$form_state) {

  $form['moreinfo']['bibdk_netarchive_script_path'] = array(
    '#type' => 'textfield',
    '#title' => t('PDF cover script path'),
    '#description' => t('Path to python pdf cover creation script, e.g. "/path/to/php_exec_dir/netArchive.py"<br/>See also Apache configuration : ScriptAlias'),
    '#required' => FALSE,
    '#default_value' => variable_get('bibdk_netarchive_script_path', ''),
  );

}

