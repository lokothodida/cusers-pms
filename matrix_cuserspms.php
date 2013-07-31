<?php
/* Centralized Users: Private Messages */

# thisfile
  $thisfile = basename(__FILE__, ".php");

# language
  i18n_merge($thisfile) || i18n_merge($thisfile, 'en_US');

# requires
  require_once(GSPLUGINPATH.$thisfile.'/php/class.php');
  
# class instantiation
  $mcuserspms = new MatrixCUsersPMs;

# register plugin
  register_plugin(
    $mcuserspms->pluginInfo('id'),
    $mcuserspms->pluginInfo('name'),
    $mcuserspms->pluginInfo('version'),
    $mcuserspms->pluginInfo('author'),
    $mcuserspms->pluginInfo('url'), 
    $mcuserspms->pluginInfo('description'),
    $mcuserspms->pluginInfo('page'),
    array($mcuserspms, 'admin')
  );

# activate actions/filters
  # front-end
    add_action('error-404', array($mcuserspms, 'display')); // display for plugin
  # back-end
    add_action($mcuserspms::PAGE.'-sidebar', 'createSideMenu' , array($mcuserspms->pluginInfo('id'), $mcuserspms->pluginInfo('sidebar'))); // sidebar link
?>