<?php

/**
* @file
* Builds placeholder replacement tokens for LDAP user data.
*/

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function rtd_ldap_token_info() {

  $types['rtd-ldap'] = [
    'name' => t('RTD LDAP'),
    'description' => t('Tokens that contain LDAP user data.'),
    'needs-data' => 'rtd_ldap'
  ];

  $rtd_ldap['givenName'] = [
    'name' => "Given name",
    'description' => "LDAP: First name.",
  ];
  $rtd_ldap['middlename'] = [
    'name' => "Middle name",
    'description' => "LDAP: Middle name.",
  ];
  $rtd_ldap['sn'] = [
    'name' => "Surname",
    'description' => "LDAP: Last name.",
  ];
  $rtd_ldap['title'] = [
    'name' => "Title",
    'description' => "LDAP: Job title.",
  ];
  $rtd_ldap['department'] = [
    'name' => "Department",
    'description' => "LDAP: Department name.",
  ];
  $rtd_ldap['departmentcode'] = [
    'name' => "Department Code",
    'description' => "LDAP: Department code.",
  ];

  return [
    'types' => $types,
    'tokens' => ['rtd-ldap' => $rtd_ldap],
  ];
}

/**
 * Implements hook_tokens().
 */
function rtd_ldap_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  if ($type == 'rtd-ldap' && isset($data['rtd_ldap'])) {
    foreach ($tokens as $token_key => $token) {
      if (isset($data['rtd_ldap']['rtd_ldap'][$token_key][0])) {
        $replacements[$token] = $data['rtd_ldap']['rtd_ldap'][$token_key][0];
      }
    }
  }

  return $replacements;
}