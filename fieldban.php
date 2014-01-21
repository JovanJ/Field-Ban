<?php

/**
 ************************************************************************
 * Field Ban plugin * Version 1.0 * Copyright @ 2014 Jovan Jojkić
 ************************************************************************
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 ************************************************************************
 * http://www.apache.org/licenses/LICENSE-2.0
 ************************************************************************
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
  ************************************************************************
**/
    //Disallow direct Initialization for extra security.

    if(!defined("IN_MYBB"))
    {
        die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
    }

    $plugins->add_hook('usercp_do_profile_end', 'fieldban_start');

    function fieldban_info()
    {
    return array(
            "name"  => "Field Ban",
            "description"=> "Plugin that helps prevent spam on your forum.",
            "website"        => "http://www.fluidsea.com",
            "author"        => "Jovan J.",
            "authorsite"    => "http://www.fluidsea.com",
            "version"        => "1.0",
            "guid"             => "",
            "compatibility" => "16*"
        );
    }

    function fieldban_activate() {
    global $db,$mybb;

    $fieldban_group = array(
            'gid'    => 'NULL',
            'name'  => 'fieldban',
            'title'      => 'Field Ban Settings',
            'description'    => 'Settings For Field Ban Plugin.',
            'disporder'    => "1",
            'isdefault'  => "0",
        );
    $db->insert_query('settinggroups', $fieldban_group);
     $gid = $db->insert_id();

    $fieldban_setting = array(
            'sid'            => 'NULL',
            'name'        => 'fieldban_enable',
            'title'            => 'Word Filters',
            'description'    => 'Enter any words or phrases that should trigger the filter separated by a comma.',
            'optionscode'    => 'textarea',
            'value'        => '',
            'disporder'        => 1,
            'gid'            => intval($gid),
        );
    $db->insert_query('settings', $fieldban_setting);

    $fieldban_setting = array(
            'sid'            => 'NULL',
            'name'        => 'fieldban_banreason',
            'title'            => 'Ban Reason',
            'description'    => '',
            'optionscode'    => 'text',
            'value'        => '',
            'disporder'        => 2,
            'gid'            => intval($gid),
        );
    $db->insert_query('settings', $fieldban_setting);
      rebuild_settings();
    }

    function fieldban_deactivate()
      {
      global $db,$mybb;
        $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('fieldban_enable','fieldban_banreason')");
        $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='fieldban'");
        rebuild_settings();
     }

    function fieldban_start(){
        global $db,$mybb;

        require_once MYBB_ROOT . "inc/class_moderation.php";
        $moderation = new Moderation;

        $getFields = $db->simple_select("profilefields", "fid");

        while($field = $db->fetch_array($getFields))
        {
            $fid = "fid".$field['fid'];
        }
            $getValues = $db->simple_select("userfields", $fid, "ufid = " . $mybb->user['uid']);
            $getWords = explode(',', $mybb->settings['fieldban_enable']);

           while($values = $db->fetch_array($getValues))
            {
                foreach ($getWords as $word) {  
                    foreach ($values as $value) {              
                          if (stripos($value,$word) !== false) {

                            $getThreads = $db->simple_select("threads", "tid", "uid =  " . $mybb->user['uid']);
                            while($tid = $db->fetch_field($getThreads, "tid"))
                             {
                                 $moderation->delete_thread($tid);
                             }

                             $getPosts = $db->simple_select("posts", "pid", "uid =  " . $mybb->user['uid']);
                             while($pid = $db->fetch_field($getPosts, "pid"))
                             {
                                $moderation->delete_post($pid);
                             }
                             $db->delete_query("userfields", "ufid =  " . $mybb->user['uid']);
                             $update = array(
                                 "website" => "",
                                 "birthday" => "",
                                 "icq" => "",
                                 "aim" => "",
                                  "yahoo" => "",
                                 "msn" => ""
                                 );
                             $db->update_query("users", $update, "uid =  " . $mybb->user['uid']);

                            $bannedgroup = 7;
                            $insert_array = array(
                                'uid' => $mybb->user['uid'],
                                'gid' => $bannedgroup,
                                'oldgroup' => $mybb->user['usergroup'],
                                'oldadditionalgroups' => $mybb->user['additionalgroups'],
                                'olddisplaygroup' => $mybb->user['displaygroup'],
                                'admin' => 1,
                                'dateline' => TIME_NOW,
                                'bantime' => '---',
                                'lifted' => 0,
                                'reason' => $db->escape_string($mybb->settings['fieldban_banreason'])
                            );

                            $db->insert_query('banned', $insert_array);

                            $update_array = array(
                                'usergroup' => $bannedgroup,
                                'displaygroup' => 0,
                                'additionalgroups' => '',
                            );
                            $db->update_query('users', $update_array, "uid =  " . $mybb->user['uid']);

                        }
                    }
                }
            }
        }
?>
