<?php

class MatrixCUsersPMs {
  /* constants */
  const FILE         = 'matrix_cuserspms';
  const VERSION      =  '0.1';
  const AUTHOR       = 'Lawrence Okoth-Odida';
  const URL          = 'http://lokida.co.uk';
  const PAGE         = 'plugins';
  const TABLE_PMS    = 'cusers-pms';
  const TABLE_CONFIG = 'cusers-pms-config';
  
  /* properties */
  private $plugin;
  private $directories;
  private $uri;
  private $slug;
  private $config;
  private $totalMsgs;
  private $matrix;
  private $core;
  private $coreConfig;
  
  /* methods */
  # constructor
  public function __construct() {
    // plugin details
    $this->plugin = array();
    $this->plugin['id']          = self::FILE;
    $this->plugin['name']        = i18n_r(self::FILE.'/PLUGIN_TITLE');
    $this->plugin['version']     = self::VERSION;
    $this->plugin['author']      = self::AUTHOR;
    $this->plugin['url']         = self::URL;
    $this->plugin['description'] = i18n_r(self::FILE.'/PLUGIN_DESC');
    $this->plugin['page']        = self::PAGE;
    $this->plugin['sidebar']     = i18n_r(self::FILE.'/PLUGIN_SIDEBAR');
    
    if ($this->checkDependencies()) {
      i18n_init();
      $this->matrix       = new TheMatrix;
      $this->core         = new MatrixCUsers;
      $this->coreConfig   = $this->core->getConfig();
      $this->directories  = array();
      
      // directories
      $this->directories['plugins']['core']    = array('dir' => GSPLUGINPATH.self::FILE.'/');
      $this->directories['plugins']['php']     = array('dir' => GSPLUGINPATH.self::FILE.'/php/');
      $this->directories['plugins']['img']     = array('dir' => GSPLUGINPATH.self::FILE.'/img/');
      
      // create tables
      $this->createTables();
      
      // config
      $config = $this->matrix->query('SELECT * FROM '.self::TABLE_CONFIG, 'SINGLE');
      $this->config = array();
      $this->config['max-pms']         = $config['max-pms'];
      $this->config['pms-slug']        = $config['slug'];
      $this->config['template']        = $config['template'];
      $this->config['inbox-url']       = $this->matrix->getSiteURL().$this->config['pms-slug'].'/';
      $this->config['inbox-msg-url']   = $this->matrix->getSiteURL().$this->config['pms-slug'].'/inbox/%msg%/';
      $this->config['outbox-url']      = $this->matrix->getSiteURL().$this->config['pms-slug'].'/outbox/';
      $this->config['outbox-msg-url']  = $this->matrix->getSiteURL().$this->config['pms-slug'].'/outbox/%msg%/';
      $this->config['create-msg-url']  = $this->matrix->getSiteURL().$this->config['pms-slug'].'/create/';
      $this->config['reply-url']       = $this->matrix->getSiteURL().$this->config['pms-slug'].'/create/%msg%/';
      $this->config['header']          = $config['header'];
      
      // load messages
      $this->parseURI();
    }
  }
  
  # get plugin info
  public function pluginInfo($info) {
    if (isset($this->plugin[$info])) {
      return $this->plugin[$info];
    }
    else return null;
  }
  
  # get path
  public function getPluginPath($dir, $type = true) {
    if (isset($this->directories['plugins'][$dir]['dir'])) {
      if ($type) return $this->directories['plugins'][$dir]['dir'];
      else return str_replace(GSROOTPATH, $this->matrix->getSiteURL(), $this->directories['plugins'][$dir]['dir']);
    }
    else return false;
  }
  
  # check dependencies
  private function checkDependencies() {
    if (
      (class_exists('TheMatrix') && TheMatrix::VERSION >= '1.02') &&
      (class_exists('MatrixCUsers') && MatrixCUsers::VERSION >= '1.01') && 
      function_exists('i18n_init')
    ) return true;
    else return false;
  }
  
  # missing dependencies
  private function missingDependencies() {
    $dependencies = array();
    
    if (!(class_exists('TheMatrix') && TheMatrix::VERSION >= '1.02')) {
      $dependencies[] = array('name' => 'The Matrix (1.02+)', 'url' => 'https://github.com/n00dles/DM_matrix/');
    }
    if (!(class_exists('MatrixCUsers') && MatrixCUsers::VERSION >= '1.01')) {
      $dependencies[] = array('name' => 'Centralized Users (1.01+)', 'url' => 'http://get-simple.info/extend/plugin/centralised-users/657/');
    }
    if (!function_exists('i18n_init')) {
      $dependencies[] = array('name' => 'I18N (3.2.3+)', 'url' => 'http://get-simple.info/extend/plugin/i18n/69/');
    }
    
    return $dependencies;
  }
  
  # create tables
  public function createTables() {
    $tables = array(self::TABLE_PMS => array(), self::TABLE_CONFIG =>array());
    include($this->directories['plugins']['php']['dir'].'admin/tables.php');
    $this->core->buildSchema($tables);
  }
  
  # parse out the uri
  public function parseURI() {
    // load essential globals for changing the 404 error messages
    global $id, $uri, $data_index;
    
    // parse uri
    $tmpuri = trim(str_replace('index.php', '', $_SERVER['REQUEST_URI']), '/#');
    $tmpuri = str_replace('?id=', '', $tmpuri);
    $tmpuri = preg_split('#(&|\?|\/&|\/\?)#', $tmpuri);
    $tmpuri = reset($tmpuri);
    $tmpuri = explode('/', $tmpuri);
    $slug = end($tmpuri);
    $this->slug = $slug;
    
    // fix slug for pretty urls
    if (!$this->matrix->getPrettyURLS()) {
      end($_GET);
      if (key($_GET) == 'page') prev($_GET);
      $this->slug = current($_GET);
    }
    
    $this->uri = $tmpuri;
    return $tmpuri;
  }
  
  # page type
  public function pageType() {
    $return = null;
    
    // inbox
    if ($this->slug == $this->config['pms-slug']) {
      $return = 'inbox';
    }
    
    // other pages
    elseif (
      in_array($this->config['pms-slug'], $this->uri) ||
      isset($_GET[$this->config['pms-slug']])
    ) {
      if ($this->slug == 'outbox') {
        $return = 'outbox';
      }
      elseif ($this->slug == 'create') {
        $return = 'create';
      }
      elseif (is_numeric($this->slug)) {
        end($this->uri);
        prev($this->uri);
        if (current($this->uri) == 'inbox') {
          $return = 'in-msg';
        }
        elseif (current($this->uri) == 'create') {
          $return = 'reply';
        }
        elseif (current($this->uri) == 'outbox') {
          $return = 'out-msg';
        }
      }
    }
    return $return;
  }
  
  # get message url
  public function getMessageURL($id) {
    return str_replace('%msg%', $id, $this->config['inbox-msg-url']);
  }
  
  # get inbox url
  public function getInboxURL() {
    return $this->config['inbox-url'];
  }
  
  # get outbox url
  public function getOutboxURL() {
    return $this->config['outbox-url'];
  }
  
  # get create message url
  public function getCreateMessageURL($id = '') {
    if ($id == '') return $this->config['create-msg-url'];
    else return str_replace('%msg%', $id, $this->config['reply-url']);
  }
  
  # inbox
  public function inbox() {
    global $data_index;
    
    // metadata
    $data_index->title    = i18n_r(self::FILE.'/INBOX');
    $data_index->date     = time();
    $data_index->metak    = '';
    $data_index->meta     = '';
    $data_index->url      = $this->slug;
    $data_index->parent   = '';
    $data_index->private  = '';
    
    // content
    ob_start();
    
    // sent message
    if (!empty($_POST['send'])) {
      // defaults
      $msg = array();
      $_POST['from'] = $_SESSION['cuser']['id'];
      $_POST['date'] = time();
      
      // send messages
      $send = array();
      foreach ($_POST['to'] as $to) {
        $details = $_POST;
        $details['to'] = $to;
        $senderMsgs = $this->matrix->query('SELECT id FROM '.self::TABLE_PMS.' WHERE to = '.$to, 'MULTI', $cache=false);
        if (count($senderMsgs) < $this->config['max-pms']) {
          $send[] = $this->matrix->createRecord(self::TABLE_PMS, $details);
        }
        else $send[] = false;
      }
      
      
      // statuses
      if (!in_array(false, $send)) {
        $msg['status'] = 'success';
        $msg['msg'] = i18n_r(self::FILE.'/MESSAGE_SENTSUCCESS');
      }
      else {
        $msg['status'] = 'error';
        $msg['msg'] = i18n_r(self::FILE.'/MESSAGE_SENTERROR');
      }
      // error message
      echo '<div class="'.$msg['status'].'">'.$msg['msg'].'</div>';
    }
    elseif (!empty($_POST['delete']) && !empty($_POST['messages'])) {
      $delete = array();
      foreach ($_POST['messages'] as $message) {
        $delete[] = $this->matrix->deleteRecord(self::TABLE_PMS, $message);
      }
      
      // statuses
      if (!in_array(false, $delete)) {
        $delete['status'] = 'success';
        $delete['msg'] = i18n_r(self::FILE.'/MESSAGE_DELETESUCCESS');
      }
      else {
        $delete['status'] = 'error';
        $delete['msg'] = i18n_r(self::FILE.'/MESSAGE_DELETEERROR');
      }
      // error message
      echo '<div class="'.$delete['status'].'">'.$delete['msg'].'</div>';
    }
    
    $msgs = array();
    $msgs = $this->matrix->query('SELECT * FROM '.self::TABLE_PMS.' WHERE to = '.$_SESSION['cuser']['id']);
    ?>
    <form method="post">
    <div class="table">
      <table class="pms inbox">
        <thead>
          <tr>
            <th colspan="2" class="th1"><?php echo i18n_r(self::FILE.'/SENDER'); ?></th>
            <th class="th1"><?php echo i18n_r(self::FILE.'/DATE'); ?></th>
            <th class="th1"><?php echo i18n_r(self::FILE.'/SUBJECT'); ?></th>
            <th class="th1"><?php echo i18n_r(MatrixCUsers::FILE.'/DELETE'); ?></th>
          </tr>
        </thead>
        <tbody class="content">
        <?php foreach ($msgs as $msg) { ?>
          <tr>
            <td class="td2" style="width: 1%;">
              <img src="<?php echo $this->getPluginPath('img', false).'msg'; ?><?php if ($msg['read'] == 1) echo '_read'; ?>.png">
            </td>
            <td class="td1" style="width: 20%;"><a href="<?php echo $this->core->getProfileURL($this->core->users[$msg['from']]['username']); ?>"><?php echo $this->core->users[$msg['from']]['displayname']; ?></a></td>
            <td class="td2" style="width: 18%;"><?php echo date($this->coreConfig['date-format'], $msg['date']); ?></td>
            <td class="td1" style="width: 60%;"><a href="<?php echo $this->getMessageURL($msg['id']); ?>"><?php echo $msg['subject']; ?></a></td>
            <td class="td2" style="width: 1%; text-align: center;"><input type="checkbox" name="messages[]" value="<?php echo $msg['id']; ?>"></td>
          </tr>
        <?php } ?>
        <?php if (empty($msgs)) { ?>
          <tr>
            <td colspan="100%" class="td1"><?php echo i18n_r(self::FILE.'/INBOX_EMPTY'); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php if ($this->totalMsgs > 0) { ?>
    <input type="submit" name="delete" value="<?php echo i18n_r(MatrixCUsers::FILE.'/DELETE'); ?>">
    <?php } ?>
    </form>
    <?php
    $data_index->content .= ob_get_contents();
    ob_end_clean();
  }
  
  # outbox
  public function outbox() {
    global $data_index;
    
    // metadata
    $data_index->title    = i18n_r(self::FILE.'/OUTBOX');
    $data_index->date     = time();
    $data_index->metak    = '';
    $data_index->meta     = '';
    $data_index->url      = $this->slug;
    $data_index->parent   = '';
    $data_index->private  = '';
    
    // content
    ob_start();
    
    $msgs = array();
    $msgs = $this->matrix->query('SELECT * FROM '.self::TABLE_PMS.' WHERE from = '.$_SESSION['cuser']['id'].' ORDER BY date DESC');
    ?>
    <div class="table">
      <table class="pms">
        <thead>
          <tr>
            <th class="th1"><?php echo i18n_r(self::FILE.'/SENDER'); ?></th>
            <th class="th1"><?php echo i18n_r(self::FILE.'/DATE'); ?></th>
            <th class="th1"><?php echo i18n_r(self::FILE.'/SUBJECT'); ?></th>
          </tr>
        </thead>
        <tbody class="content">
        <?php foreach ($msgs as $msg) { ?>
          <tr>
            <td class="td1" style="width: 20%;"><a href="<?php echo $this->core->getProfileURL($this->core->users[$msg['from']]['username']); ?>"><?php echo $this->core->users[$msg['from']]['displayname']; ?></a></td>
            <td class="td2" style="width: 20%;"><?php echo date($this->coreConfig['date-format'], $msg['date']); ?></td>
            <td class="td1" style="width: 60%;"><a href="<?php echo $this->getMessageURL($msg['id']); ?>"><?php echo $msg['subject']; ?></a></td>
          </tr>
        <?php } ?>
        <?php if (empty($msgs)) { ?>
          <tr>
            <td colspan="100%" class="td1"><?php echo i18n_r(self::FILE.'/OUTBOX_EMPTY'); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
    $data_index->content .= ob_get_contents();
    ob_end_clean();
  }
  
  # create message
  public function createMessage($reply=array('id' => null, 'from' => null, 'subject' => null, 'content' => null)) {
    global $data_index;
    
    // ensures that you can't pull data from a rogue message
    if (isset($reply['to']) && $reply['to'] != $_SESSION['cuser']['id']) {
      foreach ($reply as $key => $value) {
        $reply[$key] = null;
      }
    }
    
    // metadata
    $data_index->title    = i18n_r(self::FILE.'/CREATE_MESSAGE');
    $data_index->date     = time();
    $data_index->metak    = '';
    $data_index->meta     = '';
    $data_index->url      = $this->slug;
    $data_index->parent   = '';
    $data_index->private  = '';
    
    // content
    ob_start();
    $users = $this->matrix->query('SELECT id, displayname FROM '.MatrixCUsers::TABLE_USERS);
    ?>
    <form method="post" action="<?php echo $this->config['inbox-url']; ?>">
    <div class="table">
      <table class="pms createMessage">
        <tbody>
          <tr>
            <th class="td2" style="width: 20%;"><?php echo i18n_r(self::FILE.'/SUBJECT'); ?></th>
            <td class="td1" style="width: 80%;">
            <?php
              if ($reply['subject']) {
                $reply['subject'] = 'Re: '.$reply['subject'];
              }
              $this->matrix->displayField(self::TABLE_PMS, 'subject', $reply['subject']);
            ?>
            </td>
          </tr>
          <tr>
            <th class="td2" style="width: 20%;"><?php echo i18n_r(self::FILE.'/RECIPIENT'); ?></th>
            <td class="td1" style="width: 80%;">
              <select name="to[]" multiple="multple" required>
                <?php foreach ($users as $user) { ?>
                <option value="<?php echo $user['id']; ?>"><?php echo $user['displayname']; ?></option>
                <?php } ?>
              </select>
            </td>
          </tr>
          <tr>
            <th class="td2" style="width: 20%;"><?php echo i18n_r(self::FILE.'/CONTENT'); ?></th>
            <td class="td1" style="width: 80%;">
            <?php
              if ($reply['content']) {
                $reply['content'] = '[quote='.$this->core->users[$reply['to']]['displayname'].']'.$reply['content'].'[/quote]';
              }
              $this->matrix->displayField(self::TABLE_PMS, 'content', $reply['content']);
            ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <input type="submit" class="submit" name="send" value="<?php echo i18n_r(self::FILE.'/SEND'); ?>">
    </form>
    <?php
    $data_index->content .= ob_get_contents();
    ob_end_clean();
  }
  
  # view message
  public function viewMessage($id) {
    global $data_index;

    $msg = $this->matrix->query('SELECT * FROM '.self::TABLE_PMS.' WHERE id = '.$this->slug, 'SINGLE');
    if ($msg && $msg['to'] == $_SESSION['cuser']['id']) {
      // update to show it has been read
      $this->matrix->updateRecord(self::TABLE_PMS, $id, array('read' => 1));
      
      // metadata
      $data_index->title    = i18n_r(self::FILE.'/VIEW_MESSAGE');
      $data_index->date     = time();
      $data_index->metak    = '';
      $data_index->meta     = '';
      $data_index->url      = $this->slug;
      $data_index->parent   = '';
      $data_index->private  = '';
    
      // content
      ob_start();
      ?>
      <form method="post" action="<?php echo $this->getCreateMessageURL($msg['id']); ?>">
      <div class="table">
        <table class="pms createMessage">
          <tbody>
            <tr>
              <th class="td2" style="width: 20%;"><?php echo i18n_r(self::FILE.'/SUBJECT'); ?></th>
              <td class="td1" style="width: 80%;"><?php echo $msg['subject']; ?></td>
            </tr>
            <tr>
              <th class="td2" style="width: 20%;"><?php echo i18n_r(self::FILE.'/DATE'); ?></th>
              <td class="td1" style="width: 80%;"><?php echo date($this->coreConfig['date-format'], $msg['date']); ?></td>
            </tr>
            <tr>
              <th class="td2" style="width: 20%;"><?php echo i18n_r(self::FILE.'/CONTENT'); ?></th>
              <td class="td1" style="width: 80%;"><?php echo $this->core->parser->bbcode($msg['content']); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
      <input type="submit" class="submit" name="send" value="<?php echo i18n_r(self::FILE.'/REPLY'); ?>">
      </form>
      <?php
      $data_index->content .= ob_get_contents();
      ob_end_clean();
    }
  }
  
  # display
  public function display() {
    if ($this->checkDependencies()) {
      global $data_index;
      $this->parseURI();
      
      if (in_array($this->config['pms-slug'], $this->uri) && $this->core->loggedIn()) {
        // parse uri and determine display type
        $type = $this->pageType();
  
        // messages
        $this->msgs = $this->matrix->query('SELECT id, date FROM '.self::TABLE_PMS.' WHERE to = '.$_SESSION['cuser']['id'].' ORDER BY date DESC', 'MULTI', $cache=false);
        $this->totalMsgs = count($this->msgs);
        
        // global meta
        $data_index->template = $this->config['template'];
        $data_index->content  = $this->core->getConfig('header-css').$this->config['header'];
        
        ob_start();
        ?>
        <div class="panel">
          <div class="left">
            <a href="<?php echo $this->getInboxURL(); ?>"><?php echo i18n_r(self::FILE.'/INBOX'); ?></a> 
            <a href="<?php echo $this->getOutboxURL(); ?>"><?php echo i18n_r(self::FILE.'/OUTBOX'); ?></a> 
            <a href="<?php echo $this->getCreateMessageURL(); ?>"><?php echo i18n_r(MatrixCUsers::FILE.'/CREATE'); ?></a> 
          </div>
          <div class="right">
            <?php echo $this->totalMsgs; ?>/<?php echo $this->config['max-pms']; ?></a> 
          </div>
        </div>
        <?php
        $header = ob_get_contents();
        ob_end_clean();
        
        // display
        if ($type == 'inbox') {
          $data_index->content .= $header;
          $this->inbox();
        }
        elseif ($type == 'create') {
          $data_index->content .= $header;
          $this->createMessage();
        }
        elseif ($type == 'reply') {
          $data_index->content .= $header;
          $reply = $this->matrix->query('SELECT * FROM '.self::TABLE_PMS.' WHERE id = '.$this->slug, 'SINGLE');
          $this->createMessage($reply);
        }
        elseif ($type == 'in-msg') {
          $data_index->content .= $header;
          $this->viewMessage($this->slug);
        }
        elseif ($type == 'outbox') {
          $data_index->content .= $header;
          $this->outbox();
        }
      }
    }
  }
  
  # admin
  public function admin() {
    if ($this->checkDependencies()) {
      // save changes
      if ($_SERVER['REQUEST_METHOD']=='POST') {
        // update the record
        $update = $this->matrix->updateRecord(self::TABLE_CONFIG, 0, $_POST);
        
        // success message
        if ($update) {
          $undo = 'load.php?id='.self::FILE.'&config&undo';
          $this->matrix->getAdminError(i18n_r(self::FILE.'/CONFIG_UPDATESUCCESS'), true, true, $undo);
        }
        // error message
        else {
          $this->matrix->getAdminError(i18n_r(self::FILE.'/PAGES_UPDATEERROR'), false);
        }
      }
      // undo changes
      elseif (isset($_GET['undo'])) {
        // undo the record update
        $undo = $this->matrix->undoRecord(self::TABLE_CONFIG, 0);
        
        // success message
        if ($undo) {
          $this->matrix->getAdminError(i18n_r(self::FILE.'/CONFIG_UNDOSUCCESS'), true);
        }
        // error message
        else {
          $this->matrix->getAdminError(i18n_r(self::FILE.'/CONFIG_UNDOERROR'), false);
        }
        // refresh the index to reflect the changes
        $this->matrix->refreshIndex();
      }
    ?>
    <h3><?php echo i18n_r(self::FILE.'/PRIVATEMSGS'); ?> (<?php echo i18n_r(self::FILE.'/CONFIG'); ?>)</h3>
    
    <form method="post">
      <?php $this->matrix->displayForm(self::TABLE_CONFIG, 0); ?>
      <input type="submit" class="submit" name="save" value="<?php echo i18n_r('BTN_SAVECHANGES'); ?>">
    </form>
    <?php
    }
    else {
      $dependencies = $this->missingDependencies();
      include(GSPLUGINPATH.self::FILE.'/php/admin/dependencies.php');
    }
  }
}

?>
