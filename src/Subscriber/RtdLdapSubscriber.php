<?php

namespace Drupal\rtd_ldap\Subscriber;

use Drupal\cas_attributes\Subscriber\CasAttributeSubscriber;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\ldap_servers\ServerFactory;

/**
 * Provides a RtdLdapSubscriber.
 */
class RtdLdapSubscriber extends CasAttributeSubscriber {

  /**
   * LDAP Server service.
   *
   * @var \Drupal\ldap_servers\ServerFactory
   */
  protected $ldapServer;

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
  public function __construct(ConfigFactoryInterface $config_factory, Token $token_service, RequestStack $request_stack, ServerFactory $ldap) {
    parent::__construct($config_factory, $token_service, $request_stack);
    $this->ldapServer = $ldap;
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
    $mappings = unserialize($this->settings->get('field.field_mapping'));
    if (empty($mappings)) {
      return [];
    }

    $field_data = [];

    // Add LDAP data.
    $username = $this->getCasUsername();
    $token_data = [
      'rtd_ldap' => $this->getLdapData($username),
    ];

    foreach ($mappings as $field_name => $attribute_token) {
      $result = trim($this->tokenService->replace($attribute_token, $token_data, ['clear' => TRUE]));
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
    $ldap_servers = $this->ldapServer->getEnabledServers();
    $ldap_entry = [];

    if (!empty($ldap_servers) && !empty($username)) {
      $ldap_server = array_shift($ldap_servers);
      $ldap_entry = $ldap_server->matchUsernameToExistingLdapEntry($username);
    }

    return $ldap_entry;
  }

  /**
   * Fetch cas username from current session.
   *
   * @return string|null
   *   The cas username.
   */
  private function getCasUsername() {
    $username = NULL;
    $current_session = $this->requestStack->getCurrentRequest()->getSession();
    $cas_property_bag_raw = $current_session->get('cas_attributes_properties');

    if (!empty($cas_property_bag_raw)) {
      $cas_property_bag = unserialize($cas_property_bag_raw);
      $username = $cas_property_bag->getUsername();
    }

    return $username;
  }

}
