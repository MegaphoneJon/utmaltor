<?php

private $smarty;

class CRM_Utmaltor_Logic_Alter {
  function __construct($params) {
    $this->smarty = CRM_Utmaltor_Logic_Smarty::singleton($params);
  }

  public static function url($urlMatches, $smarty = $this->smarty) {
    $url = $urlMatches[1];
    $url = self::fixUrl($url);
    $url = self::alterSource($url, $smarty);
    $url = self::alterMedium($url, $smarty);
    $url = self::alterCampaign($url, $smarty);
    return $url;
  }

  private static function fixUrl($url) {
    return str_replace('&amp;', '&', $url);
  }

  private static function alterSource($url, $smarty) {
    $key = 'utm_source';
    $value = CRM_Core_BAO_Setting::getItem('UTMaltor Preferences', 'utmaltor_source');
    $value = $smarty->parse($value);
    $override = (boolean) CRM_Core_BAO_Setting::getItem('UTMaltor Preferences', 'utmaltor_source_override');
    return self::setKey($url, $key, $value, $override);
  }

  private static function alterMedium($url, $smarty) {
    $key = 'utm_medium';
    $value = CRM_Core_BAO_Setting::getItem('UTMaltor Preferences', 'utmaltor_medium');
    $value = $smarty->parse($value);
    $override = (boolean) CRM_Core_BAO_Setting::getItem('UTMaltor Preferences', 'utmaltor_medium_override');
    return self::setKey($url, $key, $value, $override);
  }

  private static function alterCampaign($url, $smarty) {
    $key = 'utm_campaign';
    $value = CRM_Core_BAO_Setting::getItem('UTMaltor Preferences', 'utmaltor_campaign');
    $value = $smarty->parse($value);
    $override = (boolean) CRM_Core_BAO_Setting::getItem('UTMaltor Preferences', 'utmaltor_campaign_override');
    return self::setKey($url, $key, $value, $override);
  }

  private static function setKey($url, $key, $value, $override = FALSE) {
    if ($override) {
      return self::setValue($url, $key, $value);
    }
    if ((strpos($url, $key) === FALSE) || (strpos($url, $key) !== FALSE && !self::getValue($url, $key))) {
      return self::setValue($url, $key, $value);
    }
    return $url;
  }

  private static function getValue($url, $key) {
    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $arr);
    if (array_key_exists($key, $arr)) {
      return trim($arr[$key]);
    }
    return "";
  }

  private static function setValue($url, $key, $value) {
    $urlParts = parse_url($url);
    if (array_key_exists('query', $urlParts)) {
      parse_str($urlParts['query'], $query);
    }
    else {
      $query = array();
    }
    if (!array_key_exists('path', $urlParts)) {
      $urlParts['path'] = '/';
    }
    $urlParts['query'] = http_build_query($query ? array_merge($query, array($key => $value)) : array($key => $value));
    $newUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . '?' . $urlParts['query'];
    if (array_key_exists('fragment', $urlParts) && $urlParts['fragment']) {
      $newUrl .= '#' . $urlParts['fragment'];
    }
    $tokens = array(
      '%7B' => '{',
      '%7D' => '}',
      '{contact_checksum}=' => '{contact.checksum}', // #3 Token {contact_checksum} breaks down links
      '{contact.checksum}=' => '{contact.checksum}', // #3 Token {contact_checksum} breaks down links
    );
    $newUrl = str_replace(array_keys($tokens), array_values($tokens), $newUrl);
    return $newUrl;
  }

}
