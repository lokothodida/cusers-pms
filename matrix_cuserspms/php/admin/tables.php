<?php

// main private messages table
$tables[self::TABLE_PMS]['name'] = self::TABLE_PMS;
$tables[self::TABLE_PMS]['fields'] = array(
  array(
    'name' => 'from',
    'label' => i18n_r(self::FILE.'/FROM'),
    'required' => 'required',
    'type' => 'int',
    'tableview' => 0,
  ),
  array(
    'name' => 'to',
    'required' => 'required',
    'label' => i18n_r(MatrixCUsers::FILE.'/USERNAME'),
    'type' => 'int',
    'tableview' => 0,
  ),
  array(
    'name' => 'date',
    'label' => i18n_r(self::FILE.'/DATE'),
    'type' => 'datetimelocal',
  ),
  array(
    'name' => 'subject',
    'label' => i18n_r(self::FILE.'/SUBJECT'),
    'type' => 'textlong',
    'required' => 'required',
    'desc' => i18n_r(self::FILE.'/SUBJECT'),
  ),
  array(
    'name' => 'content',
    'label' => i18n_r(self::FILE.'/LEVEL'),
    'type' => 'bbcodeeditor',
  ),
  array(
    'name' => 'read',
    'label' => i18n_r(self::FILE.'/READ'),
    'default' => 0,
    'type' => 'int',
  ),
);
$tables[self::TABLE_PMS]['maxrecords'] = 0;
$tables[self::TABLE_PMS]['id'] = 0;
$tables[self::TABLE_PMS]['records'] = array();

// cusers private messages settings
$tables[self::TABLE_CONFIG]['name'] = self::TABLE_CONFIG;
$tables[self::TABLE_CONFIG]['fields'] = array(
  array(
    'name' => 'slug',
    'label' => i18n_r(self::FILE.'/SLUG'),
    'type' => 'slug',
    'default' => 'pms',
    'required' => 'required',
    'class' => 'leftsec',
  ),
  array(
    'name' => 'max-pms',
    'label' => i18n_r(self::FILE.'/MAX_PMS'),
    'type' => 'int',
    'default' => 20,
    'required' => 'required',
    'class' => 'leftsec',
  ),
  array(
    'name' => 'template',
    'label' => i18n_r(self::FILE.'/TEMPLATE'),
    'type' => 'template',
    'default' => 'template.php',
    'class' => 'leftsec',
  ),
  array(
    'name' => 'max-title',
    'label' => i18n_r(self::FILE.'/MAX_SUBJECT'),
    'type' => 'int',
    'default' => 100,
    'desc' => i18n_r(self::FILE.'/MAX_SUBJECT'),
    'required' => 'required',
    'class' => 'rightsec',
  ),
  array(
    'name' => 'max-chars',
    'label' => i18n_r(self::FILE.'/MAX_CHARS'),
    'type' => 'int',
    'default' => 250,
    'desc' => i18n_r(self::FILE.'/MAX_CHARS'),
    'required' => 'required',
    'class' => 'rightsec',
  ),
  array(
    'name' => 'header',
    'label' => i18n_r(MatrixCUsers::FILE.'/HEADER'),
    'type' => 'codeeditor',
  ),
);
$tables[self::TABLE_CONFIG]['maxrecords'] = 1;
$tables[self::TABLE_CONFIG]['id'] = 0;
$tables[self::TABLE_CONFIG]['records'] = array();

$css = <<<EOF
<style>
  .panel {
    background:#fff;
    border-bottom:1px solid #c8c8c8;
    border-left:1px solid #e4e4e4;
    border-right:1px solid #c8c8c8;
    -moz-box-shadow: 2px 1px 10px rgba(0,0,0, .07);
    -webkit-box-shadow: 2px 1px 10px rgba(0,0,0, .07);
    box-shadow: 2px 1px 10px rgba(0,0,0, .07);
    margin: 0 0 10px 0;
    padding: 5px;
    overflow: hidden;
  }
  .panel .left { float: left; }
  .panel .right { float: right; }
</style>
EOF;

$tables[self::TABLE_CONFIG]['records'][] = array(
  'header' => $css,
);

?>