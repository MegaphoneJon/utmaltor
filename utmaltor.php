<?php

require_once 'utmaltor.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function utmaltor_civicrm_config(&$config) {
  _utmaltor_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function utmaltor_civicrm_xmlMenu(&$files) {
  _utmaltor_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function utmaltor_civicrm_install() {
  _utmaltor_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function utmaltor_civicrm_uninstall() {
  _utmaltor_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function utmaltor_civicrm_enable() {
  _utmaltor_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function utmaltor_civicrm_disable() {
  _utmaltor_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function utmaltor_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _utmaltor_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function utmaltor_civicrm_managed(&$entities) {
  _utmaltor_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function utmaltor_civicrm_caseTypes(&$caseTypes) {
  _utmaltor_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function utmaltor_civicrm_angularModules(&$angularModules) {
  _utmaltor_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function utmaltor_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _utmaltor_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_container().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_container
 */
function utmaltor_civicrm_container($container) {
  // Add a Symfony listener on civi.flexmailer.run. See Flexmailer dev docs for details.
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    [\Civi\FlexMailer\FlexMailer::EVENT_RUN, '_utmaltor_RunEvent_alterUrl'],
    \Civi\FlexMailer\FlexMailer::WEIGHT_START
  );
}

/**
 * Alter the URLs in the mailing body.
 */
function _utmaltor_RunEvent_alterUrl(\Civi\FlexMailer\Event\RunEvent $event) {
  $mailing = $event->getMailing();
  $params = ['mailing_id' => $mailing->id, 'campaign_id' => $mailing->campaign_id, 'subject' => $mailing->subject];
  $mailing->body_html = _utmaltor_findUrls($mailing->body_html, $params);
  $mailing->body_text = _utmaltor_findUrls($mailing->body_text, $params);

  /* If using the traditional mailer, the mailing will be pulled straight from the db,
  not from the changes we just made.  So let's update the db. Need to do it with direct sql
  so we can avoid updating the modified_date timestamp. */
  if ($mailing->template_type && $mailing->template_type == 'traditional') {
    $sql = 'UPDATE civicrm_mailing SET body_html = %1, body_text = %2, modified_date = modified_date WHERE id = %3';
    $params = [
      1 => [$mailing->body_html, 'String'],
      2 => [$mailing->body_text, 'String'],
      3 => [$mailing->id, 'Integer'],
    ];
    \CRM_Core_DAO::executeQuery($sql, $params);
  }
}

/**
 * This identifies and modifies the URLs found in the passed $text.
 * @var $text string The body of the email to search for URLs.
 * @var $params array Parameters to pass to the Smarty singleton for rendering as a token.
 */
function _utmaltor_findUrls(&$text, $params) {
  $domains = CRM_Core_BAO_Setting::getItem('UTMaltor Preferences', 'utmaltor_domains');
  $domains = str_replace('.', '\.', $domains);
  $re = '/(http[^\s"]+(' . $domains . ')[^\s"<]*)/imu';
  $callback = new CRM_Utmaltor_Logic_Alter($params);
  $text = preg_replace_callback($re, [$callback, 'url'], $text);
  return $text;
}
