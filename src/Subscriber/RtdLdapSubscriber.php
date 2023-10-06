<?php

namespace Drupal\rtd_ldap\Subscriber;

use Drupal\cas_attributes\Subscriber\CasAttributesSubscriber;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\ldap_servers\LdapUserManager;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas_attributes\Form\CasAttributesSettings;

/**
 * Provides a RtdLdapSubscriber.
 */
class RtdLdapSubscriber extends CasAttributesSubscriber {

  /**
   * LDAP Server service.
   *
   * @var \Drupal\ldap_servers\ServerFactory
   */
  protected $ldapServer;

  protected $username;

  /**
   * RtdLdapSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \Drupal\Core\Utility\Token $token_service
   *   Token service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Symfony's RequestStack service.
   * @param \Drupal\ldap_servers\ServerFactory $ldap
   *   LDAP server factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Token $token_service, RequestStack $request_stack, LdapUserManager $ldap) {
    parent::__construct($config_factory, $token_service, $request_stack);
    $this->ldapServer = $ldap;
    $this->username = '';
  }

  public function onPreLogin(CasPreLoginEvent $event) {
    $account = $event->getAccount();
    $this->username = $event->getCasPropertyBag()->getUsername();

    // Map fields.
    if ($this->settings->get('field.sync_frequency') === CasAttributesSettings::SYNC_FREQUENCY_EVERY_LOGIN) {
      $field_mappings = $this->getFieldMappings($event->getCasPropertyBag()->getAttributes());
      if (!empty($field_mappings)) {
        // If field already has data, only set new value if configured to
        // overwrite existing data.
        $overwrite = $this->settings->get('field.overwrite');
        foreach ($field_mappings as $field_name => $field_value) {
          if ($overwrite || empty($account->get($field_name))) {
            $account->set($field_name, $field_value);
          }
        }
      }
    }

    // Map roles.
    $roleMappingResults = $this->doRoleMapCheck($event->getCasPropertyBag()->getAttributes());
    if ($this->settings->get('role.sync_frequency') === CasAttributesSettings::SYNC_FREQUENCY_EVERY_LOGIN) {
      foreach ($roleMappingResults['remove'] as $rid) {
        $account->removeRole($rid);
      }
      foreach ($roleMappingResults['add'] as $rid) {
        $account->addRole($rid);
      }
    }

    if (empty($roleMappingResults['add']) && $this->settings->get('role.deny_login_no_match')) {
      $event->cancelLogin();
    }
  }

  /**
   * Override method originally supplied by CasAttributeSubscriber.
   *
   * The difference between this method and the original is that here
   * we pass token data to the token replace function call.
   *
   * @return array
   *   User account fields with with processed tokens.
   */
  protected function getFieldMappings() {
    $map = $this->settings;
    $mappings = $this->settings->get('field.mappings');
    if (empty($mappings)) {
      return [];
    }

    $field_data = [];

    // Add LDAP data.
    $username = $this->username;
    $token_data = [
      'rtd_ldap' => $this->getLdapData($username),
    ];

    foreach ($mappings as $field_name => $attribute_token) {
      // $attribute_token must be a string. Skip iteration it's an array.
      if (is_array($attribute_token)) {
        continue;
      }
      $result = trim($this->tokenService->replace($attribute_token, ['rtd_ldap' => $token_data], ['clear' => TRUE]));
      $result = html_entity_decode($result);

      // Only update the fields if there is data to set.
      if (!empty($result)) {
        $field_data[$field_name] = $result;
      }
    }

    return $field_data;
  }

  /**
   * Get the LDAP data for the user whose username is passed as parameter.
   *
   * @param string $username
   *   The identifier used to fetch LDAP data.
   *
   * @return array|bool
   *   LDAP user data or false if no LDAP server is available.
   */
  protected function getLdapData($username) {
    $ldap_server = $this->ldapServer->setServerById('asu_ldap');
    $ldap_entry = [];

    if (!empty($ldap_server) && !empty($username)) {
      $ldap_entry = $this->ldapServers->queryAllBaseDnLdapForUsername($username);
      $ldap_entry = $ldap_entry->getAttributes();
    }

    return $ldap_entry;
  }

}
