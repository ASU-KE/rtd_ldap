## RTD LDAP module for Drupal 8

### Installation Instructions:
1. Install and enable CAS, CAS Attributes, LDAP Servers, and LDAP Help modules.
2. Configure the CAS module to allow users to authenticate via CAS. 
3. Add and configure an LDAP server to connect to an LDAP directory.
4. Add user account fields.
5. Go to the CAS Attributes settings page, and use RTD LDAP tokens to prepopulate user account fields.

### Hidden tokens:
A list of available tokens is displayed on the CAS Attributes settings page. Additional tokens are available but
they're not listed there. These hidden tokens have the following form [rtd-ldap:?] where the question mark
is a placeholder for an LDAP attribute (e.g. [rtd-ldap:mailcode]). Please be aware that some LDAP attributes are not
applicable to all users (i.e. students vs staff.

### Note:
RTD LDAP was built as a replacement for the CAS LDAP module, which at the time of this writing did not have a
Drupal 8 release. Should CAS LDAP have a Drupal 8 release, RTD LDAP will be discontinued in favor of CAS LDAP. Refer
to [this ticket](https://www.drupal.org/project/cas_attributes/issues/2935811) on drupal.org's issue queue to learn
 more about the status of CAS LDAP.


