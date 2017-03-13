<?php
/**
 * DokuWiki Plugin Slack (Action Component)
 *
 *  @author  Olivier Schwab <olivier.schwab72@gmail.com> 
 *  @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_slack extends DokuWiki_Action_Plugin {

    function register(&$controller) {
       $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');
    }

    function handle_action_act_preprocess(&$event, $param) {

        global $INFO;

        if(is_array($event->data)){
            list($act) = array_keys($event->data);
        } else {
            $act = $event->data;
        }



        dbglog('#SLACK handle_action_act_preprocess ' . $act);


        if ($act=='save')
            $this->handle();

        return;
    }

    private function handle() {
        global $SUM;
        global $INFO;

        $fullname = $INFO['userinfo']['name'];
        $username = $INFO['client'];
        $page     = $INFO['namespace'] . $INFO['id'];
        $summary  = $SUM;
        $minor    = (boolean) $_REQUEST['minor'];
        dbglog('#SLACK -------------------------------------------------');
        dbglog('#SLACK Page event on ' . $page . ' by ' . $username) ;

        /* Namespace filter */
        $ns = $this->getConf('slack_namespaces');
        if (!empty($ns)) {
            $namespaces = explode(',', $ns);
            $current_namespace = explode(':', $INFO['namespace']);
            if (!in_array($current_namespace[0], $namespaces)) {
                dbglog('#SLACK ' . $current_namespace[0] . ' : Namespace not in list');
                return;
            }
        }

        $say = '*' . $fullname . '* updated the Wiki page <' . $this->urlize() . '|' . $INFO['id']  . '> ' ;
        if ($minor) { 
            if  ($this->getConf('notify_minor_edit')) {
                $say = $say . ' [minor edit]';
            }
            else  {
                dbglog('#SLACK Minor edit notification disabled');
                return;
            }
        }

        if ($summary) $say = $say . ' _' . $summary .'_' ;

        dbglog('#SLACK message : ' . $say);
        $wgSlackChannel =  $this->getConf('slack_channel');
        $wgSlackWebhookURL=$this->getConf('slack_url');
        $wgSlackUserName = $this->getConf('slack_name');


        dbglog('#SLACK Channel  = '. $wgSlackChannel );
        dbglog('#SLACK WebhookURL = '. $wgSlackWebhookURL);
        dbglog('#SLACK UserName  = '. $wgSlackUserName );

        $ch = curl_init();

        $url = sprintf('%s', $wgSlackWebhookURL);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $payload = array(
            'channel' => $wgSlackChannel,
            'username' => $wgSlackUserName,
            'text' => $say,
            'icon_emoji' => ":pencil:",
            "mrkdwn" => true
            );
        $data = array('payload' => json_encode($payload));


        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if( ! $result = curl_exec($ch))
        {
            dbglog("#SLACK cURL error : " . curl_error($ch));
        }      
        else   
        {
            dbglog("#SLACK cURL OK");
        }
        curl_close($ch);

    }

    /* Make our URLs! */
    private function urlize() {

        global $INFO;
        global $conf;
        $page = $INFO['id'];

        switch($conf['userewrite']) {
            case 0:
                $url = DOKU_URL . "doku.php?id=" . $page;
                break;
            case 1:
                if ($conf['useslash']) {
                    $page = str_replace(":", "/", $page);
                }
                $url = DOKU_URL . $page;
                break;
            case 2:
                if ($conf['useslash']) {
                    $page = str_replace(":", "/", $page);
                }
                $url = DOKU_URL . "doku.php/" . $page;
                break;
        }
        return $url;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
