<?php
/**
 * SMF to bbPress database conversion functions.
 *
 * Copyright (c) Normen Hansen <http://bitwaves.de/>
 *
 * Based on the previous work from lonemadmax, ITEISA, Bruno Torres and The phpBB Group.
 *
 * Copyright (c) lonemadmax <http://bbpress.org/forums/profile/lonemadmax>
 *
 * Copyright (c) ITEISA <http://www.iteisa.com/>
 *                     Jaime GÓMEZ OBREGÓN (jaime@iteisa.com)
 *
 * Copyright (c) Bruno Torres <http://www.brunotorres.net/> and
 *                     The phpBB Group <http://www.phpbb.com/>
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice(s).
 *
 */

define('I18N_QUOTE', 'Cite');  // Prefix to quotations.
define('I18N_WROTE', 'said'); // Prefix to attributed quotations, like this: "John Doe wrote".
define('I18N_CODE',  'Code');  // Prefix to code blocks.

$bpsmf_smilies = array(
        ':lol:'     => 'X-D',
        ':shock:'   => '8-O',
        ':oops:'    => ':-//',
        ':cry:'     => ':\'(',
        ':wink:'    => ';-)',
        ':roll:'    => '',
        ':twisted:' => '}:-)',
        ':evil:'    => '}:-@',
        ':arrow:'   => '->',
        ':mrgreen:' => ':-D',
        ':green:'   => ':-D',
        ':smile:'   => ':-)',
        ':mad:'     => ':-x',
        ':neutral:' => ':-|',
        ':razz:'    => ':-P',
        ':cool:'    => '8-)',
        ':sad:'     => ':-(',
        ':eek:'     => ':-O',
        ':idea:'    => '',
        ':|'        => ':-|',
        ':!:'       => '(!)',
        ':!!!:'     => '(!)',
        ':?:'       => ':-?',
        ':???:'     => ':-?',
);

$bpsmf_templates = '
<!-- BEGIN ulist_open --><ul><!-- END ulist_open -->
<!-- BEGIN ulist_close --></ul><!-- END ulist_close -->

<!-- BEGIN olist_open --><ol type="{LIST_TYPE}"><!-- END olist_open -->
<!-- BEGIN olist_close --></ol><!-- END olist_close -->

<!-- BEGIN li_open --><li><!-- END li_open -->
<!-- BEGIN li_close --></li><!-- END li_close -->

<!-- BEGIN ruler --><hr /><!-- END ruler -->

<!-- BEGIN quote_username_open --><blockquote><cite>{USERNAME} {L_WROTE}:</cite><br /><!-- END quote_username_open -->
<!-- BEGIN quote_open --><blockquote><!-- END quote_open -->
<!-- BEGIN quote_close --></blockquote><!-- END quote_close -->

<!-- BEGIN code_open --><p><code><!-- END code_open -->
<!-- BEGIN code_close --></code></p><!-- END code_close -->

<!-- BEGIN b_open --><strong><!-- END b_open -->
<!-- BEGIN b_close --></strong><!-- END b_close -->

<!-- BEGIN u_open --><span style="text-decoration: underline;"><!-- END u_open -->
<!-- BEGIN u_close --></span><!-- END u_close -->

<!-- BEGIN i_open --><em><!-- END i_open -->
<!-- BEGIN i_close --></em><!-- END i_close -->

<!-- BEGIN center_open --><p style="text-align: center;"><!-- END center_open -->
<!-- BEGIN center_close --></p><!-- END center_close -->

<!-- BEGIN s_open --><span style="text-decoration: line-through;"><!-- END s_open -->
<!-- BEGIN s_close --></span><!-- END s_close -->

<!-- BEGIN sup_open --><sup><!-- END sup_open -->
<!-- BEGIN sup_close --></sup><!-- END sup_close -->

<!-- BEGIN tt_open --><tt><!-- END tt_open -->
<!-- BEGIN tt_close --></tt><!-- END tt_close -->

<!-- BEGIN color_open --><!-- END color_open -->
<!-- BEGIN color_close --><!-- END color_close -->

<!-- BEGIN size_open --><!-- END size_open -->
<!-- BEGIN size_close --><!-- END size_close -->

<!-- BEGIN table_open --><table><!-- END table_open -->
<!-- BEGIN table_close --></table><!-- END table_close -->
<!-- BEGIN tr_open --><tr><!-- END tr_open -->
<!-- BEGIN tr_close --></tr><!-- END tr_close -->
<!-- BEGIN td_open --><td><!-- END td_open -->
<!-- BEGIN td_close --></td><!-- END td_close -->

<!-- BEGIN img --><img src="{URL}" alt="" /><!-- END img -->

<!-- BEGIN url --><a href="{URL}">{DESCRIPTION}</a><!-- END url -->

<!-- BEGIN email --><a href="mailto:{EMAIL}" class="email">{EMAIL}</a><!-- END email -->

<!-- BEGIN flash --><object type="application/x-shockwave-flash" data="{URL}"><param name="movie" value="{URL}" /><p>Su navegador no soporta objetos flash</p></object><!-- END flash -->
';

/**
 * creates groups for forums
 */
function bpsmf_create_forum_groups() {
    //TODO: check if forum group exists
    echo "<h3>Creating forum groups:</h3>";
    echo "<ol>";
    for($count=0;$count<=100;$count+=1) {
        $forum=bp_forums_get_forum($count);
        if($forum) {
            $myarray = array(
                    'creator_id'=>1,
                    'name'=>$forum->forum_name,
                    'description'=>$forum->forum_desc,
                    'enable_forum'=>true,
                    'slug'=>$forum->forum_slug,
                    'status'=>'public',
                    'date_created'=>gmdate( "Y-m-d H:i:s" ) );

            echo "<li>$forum->forum_name</li>";

            $group_id=groups_create_group($myarray);
            groups_update_groupmeta( $group_id, 'forum_id', $forum->forum_id );
            groups_update_groupmeta( $group_id, 'total_member_count', 1 );
        }
    }
    echo "</ol>";
}

/**
 * sets the "forum_parent" of all forums to "1"
 */
function bpsmf_set_forum_parent() {
    global $wpdb;
    $wpdb->update( $wpdb->base_prefix."bb_"."forums",
            array(
            "forum_parent"=>1
            ),
            array(
            "forum_parent"=>0
            )
    );
}

/**
 * creates user nice name (slug)
 */
function bpsmf_create_user_nicenames() {
    do_action("bbpress_init");

    global $wpdb;

    $users = $wpdb->get_results( "SELECT ID, user_login, user_nicename FROM $wpdb->users WHERE user_nicename IS NULL OR user_nicename = ''" );

    if ( $users ) {
        foreach ( $users as $user ) {
            $user_nicename = $_user_nicename = str_replace (" ", "", sanitize_user( $user->user_login, true ));
            while ( is_numeric($user_nicename) || $existing_user = get_user_by( "user_nicename", $user_nicename ) )
                $user_nicename = bb_slug_increment($_user_nicename, $existing_user->user_nicename, 50);

            $wpdb->query( "UPDATE $wpdb->users SET user_nicename = '$user_nicename' WHERE ID = $user->ID;" );
        }
    }

    return 'Done adding nice-names to existing users: ' . __FUNCTION__;
}

/**
 * updates the bb-config.php file with the db credentials of WP
 */
function bpsmf_update_bbpress_config() {
    global $wpdb;

    //read the entire string
    $sample_path=getcwd().'/../wp-content/plugins/buddypress-smf-import/bbpress/bb-config-sample.php';
    $str=file_get_contents($sample_path);

    //replace something in the file string - this is a VERY simple example
    $str=str_replace('bbpress',DB_NAME,$str);
    $str=str_replace('username',DB_USER,$str);
    $str=str_replace('password',DB_PASSWORD,$str);
    $str=str_replace('localhost',DB_HOST,$str);
    $str=str_replace('utf8',DB_CHARSET,$str);
    $str=str_replace("'bb_'","'".$wpdb->base_prefix."bb_'",$str);

    $config_path=getcwd()."/../wp-content/plugins/buddypress-smf-import/bbpress/bb-config.php";
    if(!file_put_contents($config_path,$str)) {
        echo "<h3>Error writing bbpress config file</h3>";
        echo "<p>Before upgrading the database, create /wp-content/plugins/buddypress-smf-import/bbpress/bb-config.php with the following content:</p>";
        echo "<code>".$str."</code>";
    }

}

/**
 * deletes the current forum db tables and creates bbpress 0.73 compatible ones
 * @param <type> $prefix the database prefix (wp_)
 * @param <type> $encoding the encoding for the new tables (utf8)
 */
function bpsmf_prepare_db($prefix="wp_", $encoding="utf8") {
    global $bp, $wpdb;

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."term_relationships`;"));
    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."term_taxonomy`;"));
    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."terms`;"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."users`;"));
    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."usermeta`;"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."meta`;"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."forums`;"));
    $wpdb->query( $wpdb->prepare("CREATE TABLE `".$prefix."forums` (
  `forum_id` int(10) NOT NULL auto_increment,
  `forum_name` varchar(150) NOT NULL default '',
  `forum_desc` text NOT NULL,
  `forum_order` int(10) NOT NULL default '0',
  `topics` bigint(20) NOT NULL default '0',
  `posts` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`forum_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=".$encoding." AUTO_INCREMENT=2 ;"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."posts`;"));
    $wpdb->query( $wpdb->prepare("CREATE TABLE `".$prefix."posts` (
  `post_id` bigint(20) NOT NULL auto_increment,
  `forum_id` int(10) NOT NULL default '1',
  `topic_id` bigint(20) NOT NULL default '1',
  `poster_id` int(10) NOT NULL default '0',
  `post_text` text NOT NULL,
  `post_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `poster_ip` varchar(15) NOT NULL default '',
  `post_status` tinyint(1) NOT NULL default '0',
  `post_position` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`post_id`),
  KEY `topic_id` (`topic_id`),
  KEY `poster_id` (`poster_id`),
  FULLTEXT KEY `post_text` (`post_text`)
) ENGINE=MyISAM  DEFAULT CHARSET=".$encoding." AUTO_INCREMENT=2 ;"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."tagged`;"));
    $wpdb->query( $wpdb->prepare("CREATE TABLE `".$prefix."tagged` (
  `tagged_id` bigint(20) unsigned NOT NULL auto_increment,
  `tag_id` bigint(20) unsigned NOT NULL default '0',
  `user_id` bigint(20) unsigned NOT NULL default '0',
  `topic_id` bigint(20) unsigned NOT NULL default '0',
  `tagged_on` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`tagged_id`),
  KEY `tag_id_index` (`tag_id`),
  KEY `user_id_index` (`user_id`),
  KEY `topic_id_index` (`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=".$encoding.";"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."tags`;"));
    $wpdb->query( $wpdb->prepare("CREATE TABLE `".$prefix."tags` (
  `tag_id` bigint(20) unsigned NOT NULL auto_increment,
  `tag` varchar(30) NOT NULL default '',
  `raw_tag` varchar(50) NOT NULL default '',
  `tag_count` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`tag_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=".$encoding." AUTO_INCREMENT=2 ;"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."topicmeta`;"));
    $wpdb->query( $wpdb->prepare("CREATE TABLE `".$prefix."topicmeta` (
  `meta_id` bigint(20) NOT NULL auto_increment,
  `topic_id` bigint(20) NOT NULL default '0',
  `meta_key` varchar(255) default NULL,
  `meta_value` longtext,
  PRIMARY KEY  (`meta_id`),
  KEY `user_id` (`topic_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=MyISAM  DEFAULT CHARSET=".$encoding." AUTO_INCREMENT=3 ;"));

    $wpdb->query( $wpdb->prepare("INSERT INTO `".$prefix."topicmeta` (`meta_id`, `topic_id`, `meta_key`, `meta_value`) VALUES
(2, 0, 'uri', '".site_url()."/wp-content/plugins/buddypress-smf-import/bbpress/');"));

    $wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS `".$prefix."topics`;"));
    $wpdb->query( $wpdb->prepare("CREATE TABLE `".$prefix."topics` (
  `topic_id` bigint(20) NOT NULL auto_increment,
  `topic_title` varchar(100) NOT NULL default '',
  `topic_poster` bigint(20) NOT NULL default '0',
  `topic_poster_name` varchar(40) NOT NULL default 'Anonymous',
  `topic_last_poster` bigint(20) NOT NULL default '0',
  `topic_last_poster_name` varchar(40) NOT NULL default '',
  `topic_start_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `topic_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `forum_id` int(10) NOT NULL default '1',
  `topic_status` tinyint(1) NOT NULL default '0',
  `topic_resolved` varchar(15) NOT NULL default 'no',
  `topic_open` tinyint(1) NOT NULL default '1',
  `topic_last_post_id` bigint(20) NOT NULL default '1',
  `topic_sticky` tinyint(1) NOT NULL default '0',
  `topic_posts` bigint(20) NOT NULL default '0',
  `tag_count` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`topic_id`),
  KEY `forum_id` (`forum_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=".$encoding." AUTO_INCREMENT=2 ;"));

}

/**
 * This method can convert a SMF 1.1 (www.simplemachines.org) forum
 * database to bbPress "Bix" 0.73 (www.bbpress.org) format.
 */
function bpsmf_do_import($import_options) {
    global $bp, $wpdb;
//    do_action("bbpress_init");

    $smf_db=$import_options['smf_db_name'];
    $smf_db_user=$import_options['smf_db_user'];
    $smf_db_pass=$import_options['smf_db_password'];
    $smf_db_host=$import_options['smf_db_host'];
    $smf_db_prefix=$import_options['smf_db_prefix']?$import_options['smf_db_prefix']:"smf_";

    set_time_limit(0);
    $bbcode_tpl = null;

    $phpbb_tables['forums'] = $smf_db_prefix . 'boards';
    $phpbb_tables['users'] = $smf_db_prefix . 'members';
    $phpbb_tables['topics'] = $smf_db_prefix . 'topics';
    $phpbb_tables['posts'] = $smf_db_prefix . 'messages';

    $phpbb_tables['tags'] = $smf_db_prefix . 'tags';
    $phpbb_tables['tagmeta'] = $smf_db_prefix . 'tags_log';

    $bbpress_tables['forums'] = $wpdb->base_prefix . 'bb_forums';
    $bbpress_tables['users'] = $wpdb->base_prefix . 'users';
    $bbpress_tables['usermeta'] = $wpdb->base_prefix . 'usermeta';
    $bbpress_tables['topics'] = $wpdb->base_prefix . 'bb_topics';
    $bbpress_tables['topicmeta'] = $wpdb->base_prefix . 'bb_topicmeta';
    $bbpress_tables['posts'] = $wpdb->base_prefix . 'bb_posts';

    $bbpress_tables['tags'] = $wpdb->base_prefix . 'bb_tags';
    $bbpress_tables['tagged'] = $wpdb->base_prefix . 'bb_tagged';

    $next_bbpress_post = 1;
    //maps post ids
    $smf2bbpress_posts = array();
    $smf_posts_subjects = array();
    $smf_posts_posters = array();
    $smf_posts_times = array();

    //stores topics first posts
    $smf_topic_posts = array();

    $next_bbpress_forum_order = 1;
    $next_bbpress_forum = 1;
    //maps forum ids
    $smf2bbpress_forums = array();

    $next_bbpress_topic = 1;
    //maps topic ids
    $smf2bbpress_topics = array();

    $bbpress_users = array();
    $next_bbpress_user = 0;
    //maps user ids
    $smf2bbpress_users = array();

    //maps tag ids
    $smf2bbpress_tags = array();
    $next_bbpress_tag = 1;
    $next_bbpress_tagged = 1;

    $info_skipped_users = 0;
    $info_skipped_topics = 0;
    $info_skipped_posts = 0;
    $info_skipped_tagged = 0;

    echo "<div id='container'>";
    echo "<h3>Starting conversion</h3>";
    echo "<ol>";
    echo "<p>memory limit: " . ini_get('memory_limit') . " / ";
    echo "time limit: " . ini_get('max_execution_time') . "</p>";

    echo "<li><h3>Update bbpress config file</h3></li>";
    flush();

    bpsmf_update_bbpress_config();

    echo "<li><h3>Deleting current forum data</h3></li>";
    flush();

    bpsmf_prepare_db($wpdb->base_prefix."bb_", DB_CHARSET);
//
//            echo "<li><h3>Counting existing bbPress forums</h3></li>";
//            flush();
//
//            $export_sql = "SELECT forum_id FROM " . $bbpress_tables['forums'];
//            $export_result = $wpdb->get_results($export_sql);
//
////            while($export_row = mysql_fetch_object($export_result)) {
//            foreach ( (array) $export_result as $export_row ){
//                if($export_row->forum_id >= $next_bbpress_forum)
//                    $next_bbpress_forum = $export_row->forum_id + 1;
//                if($export_row->forum_order >= $next_bbpress_forum_order)
//                    $next_bbpress_forum_order = $export_row->forum_order + 1;
//            }
//
//            echo "<li><h3>Counting existing bbPress topics</h3></li>";
//            flush();
//
//            $export_sql = "SELECT topic_id FROM " . $bbpress_tables['topics'];
//            $export_result = $wpdb->get_results($export_sql);
//
////            while($export_row = mysql_fetch_object($export_result)) {
//            foreach ( (array) $export_result as $export_row ){
//                if($export_row->topic_id >= $next_bbpress_topic)
//                    $next_bbpress_topic = $export_row->topic_id + 1;
//            }

    echo "<li><h3>Getting existing WordPress users</h3></li>";
    flush();

    $export_sql = "SELECT ID, user_login FROM " . $bbpress_tables['users'];
    $export_result = $wpdb->get_results(($export_sql));
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve data from database:</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
        return;
    }

//            while($export_row = mysql_fetch_object($export_result)) {
    foreach ( (array) $export_result as $export_row ) {
        $bbpress_users[$export_row->user_login] = $export_row->ID;
        if($export_row->ID >= $next_bbpress_user)
            $next_bbpress_user = $export_row->ID + 1;
    }

//            echo "<li><h3>Counting existing bbPress posts</h3></li>";
//            flush();
//
//            $export_sql = "SELECT post_id FROM " . $bbpress_tables['posts'];
//            $export_result = $wpdb->get_results(($export_sql));
//
////            while($export_row = mysql_fetch_object($export_result)) {
//            foreach ( (array) $export_result as $export_row ){
//                if($export_row->post_id >= $next_bbpress_post)
//                    $next_bbpress_post = $export_row->post_id + 1;
//            }
//

    echo "<li><h3>Connecting to the SMF database host</h3></li>";
    flush();

    if(!@mysql_connect($smf_db_host, $smf_db_user, $smf_db_pass)) {
        echo("<p><h3>Unable to connect to SMF database, check your SMF database login data!</h3>");
        return;
    }

    echo "<li><h3>Selecting the SMF database</h3></li>";
    flush();

    if(!@mysql_select_db($smf_db)) {
        echo("<p><h3>Unable to select SMF database, check your SMF database info!</h3>");
        return;
    }

    echo "<li><h3>Importing forums...</h3></li>";
    flush();

    $export_sql = "SELECT ID_BOARD, name, description, boardOrder, numTopics, numPosts FROM " . $phpbb_tables['forums'];
    $export_result = mysql_query($export_sql);
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve data from database:</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
        return;
    }

    mysql_query($export_sql);

    while($export_row = mysql_fetch_object($export_result)) {
        $smf2bbpress_forums[$export_row->ID_BOARD] = $next_bbpress_forum;
        $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['forums'] . " (forum_id, forum_name, forum_desc, forum_order, topics, posts) VALUES (" . $next_bbpress_forum . ", '" . addslashes($export_row->name) . "', '" . addslashes($export_row->description) . "', " . $next_bbpress_forum_order . ", " . $export_row->numTopics . ", " . $export_row->numPosts . ");\n"));
        $next_bbpress_forum++;
        $next_bbpress_forum_order++;
    }


    echo "<li><h3>Importing users...</h3></li>";
    flush();

    $export_sql = "SELECT ID_MEMBER, memberName, passwd, emailAddress, websiteURL, dateRegistered, realName FROM " . $phpbb_tables['users'];
    $export_result = mysql_query($export_sql);
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve data from database:</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
        return;
    }

    while($export_row = mysql_fetch_object($export_result)) {
        $user_login = $export_row->memberName;
        if($bbpress_users[$user_login]) {
            $info_skipped_users++;
            //User already exists in bbPress
            $smf2bbpress_users[$export_row->ID_MEMBER] = $bbpress_users[$user_login];
        } else {
            //Import user
            $smf2bbpress_users[$export_row->ID_MEMBER] = $next_bbpress_user;
            $regdate = date("Y-m-d H:i:s", $export_row->dateRegistered);
            $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['users'] . " (ID, user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_status, display_name) VALUES (" . $next_bbpress_user . ", '" . addslashes($export_row->memberName) . "', '" . $export_row->passwd . "', '', '" . $export_row->emailAddress . "', '" . $export_row->websiteURL . "', '$regdate', 0, '" . addslashes($export_row->realName) . "');\n"));
            $next_bbpress_user++;
        }
    }


    echo "<li><h3>Importing user metadata...</h3></li>";
    flush();

    //Users meta data
    //Obs: All users imported will be flagged as "no role for this site"
    //You can change this later from bbPress Admin Panel
    //using your already registered admin user

    $export_sql = "SELECT ID_MEMBER, memberName, location, usertitle FROM " . $phpbb_tables['users'];
    $export_result = mysql_query($export_sql);
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve data from database:</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
        return;
    }

    while($export_row = mysql_fetch_object($export_result)) {
        if(! $bbpress_users[$export_row->memberName]) {
            $user_id = $smf2bbpress_users[$export_row->ID_MEMBER];
            //capabilities
            $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['usermeta'] . "(user_id, meta_key, meta_value) VALUES (" . $user_id . ", '" . $wpdb->base_prefix . "capabilities', 'a:0:{}');\n"));
            //From
            $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['usermeta'] . "(user_id, meta_key, meta_value) VALUES (" . $user_id . ", 'from', '" . addslashes($export_row->location) . "');\n"));
            //Occupation
            $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['usermeta'] . "(user_id, meta_key, meta_value) VALUES (" . $user_id . ", 'occ', '" . addslashes($export_row->usertitle) . "');\n"));
        }
    }


    echo "<li><h3>Get needed SMF posts data</h3></li>";
    flush();

    $export_sql = "SELECT ID_MSG, posterTime, subject, posterName FROM " . $phpbb_tables['posts'];
    $export_result = mysql_query($export_sql);
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve data from database:</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
        return;
    }

    while($export_row = mysql_fetch_object($export_result)) {
        $msg_id = $export_row->ID_MSG;
        $smf_posts_times[$msg_id] = date("Y-m-d H:i:s", $export_row->posterTime);
        $smf_posts_subjects[$msg_id] = addslashes($export_row->subject);
        $smf_posts_posters[$msg_id] = addslashes($export_row->posterName);
        $smf2bbpress_posts[$msg_id] = $next_bbpress_post;
        $next_bbpress_post++;
    }


    echo "<li><h3>Importing topics...</h3></li>";
    flush();

    $export_sql = "SELECT * FROM " . $phpbb_tables['topics'];
    $export_result = mysql_query($export_sql);
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve data from database:</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
        return;
    }

    while($export_row = mysql_fetch_object($export_result)) {
        $first_msg_id = $export_row->ID_FIRST_MSG;
        $last_msg_id = $export_row->ID_LAST_MSG;
        $smf_topic_posts[$first_msg_id]=true;

        $topic_open = 0;
        if($export_row->locked == 0) {
            $topic_open = 1;
        }

        $smf2bbpress_topics[$export_row->ID_TOPIC] = $next_bbpress_topic;
        if( !is_null($smf2bbpress_users[$export_row->ID_MEMBER_STARTED])
                && !is_null($smf2bbpress_users[$export_row->ID_MEMBER_UPDATED])
                && !is_null($smf2bbpress_posts[$last_msg_id]) ) {
            $wpdb->query( $wpdb->prepare("INSERT INTO " . $bbpress_tables['topics'] . " (topic_id, topic_title, topic_poster, topic_poster_name, topic_last_poster, topic_last_poster_name, topic_start_time, topic_time, forum_id, topic_status, topic_open, topic_last_post_id, topic_sticky, topic_posts, tag_count) VALUES (
	      " . $next_bbpress_topic . ",
	      '" . $smf_posts_subjects[$first_msg_id] . "',
	      " . $smf2bbpress_users[$export_row->ID_MEMBER_STARTED] . ",
	      '" . $smf_posts_posters[$first_msg_id] . "',
	      " . $smf2bbpress_users[$export_row->ID_MEMBER_UPDATED] . ",
	      '" . $smf_posts_posters[$last_msg_id] . "',
	      '" . $smf_posts_times[$first_msg_id] . "',
	      '" . $smf_posts_times[$last_msg_id] . "',
	      " . $smf2bbpress_forums[$export_row->ID_BOARD] . ",
	      0,
	      ".($topic_open).",
	      " . $smf2bbpress_posts[$last_msg_id] . ",
	      ".$export_row->isSticky.",
	      " . ($export_row->numReplies + 1) . ", 0);\n"));

            #normen -- old topic id meta data
            $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['topicmeta'] . " (topic_id, meta_key, meta_value) VALUES (
	      " . $next_bbpress_topic . ",
	      'smf_topic_id',
	      " . $export_row->ID_TOPIC . ");\n"));

        }else {
            $info_skipped_topics++;
            //echo "skipping topic " . $smf_posts_subjects[$first_msg_id] . " (" . $smf2bbpress_users[$export_row->ID_MEMBER_STARTED] . ")<br>";
        }
        $next_bbpress_topic++;
    }

    echo "<li><h3>Importing posts...</h3></li>";
    flush();

    $export_sql = "SELECT ID_MSG, ID_TOPIC, ID_BOARD, ID_MEMBER, posterIP, body FROM " . $phpbb_tables['posts'];
    $export_result = mysql_query($export_sql);
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve data from database:</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
        return;
    }

    //TODO: str_replace <br /> not very elegant, why are those there?
    // '" . addslashes(bpsmf_bbencode_second_pass(bpsmf_bbencode_first_pass(bpsmf_smilies_pass(nl2br($export_row->body)), $export_row->ID_MSG), $export_row->ID_MSG)) . "',
    while($export_row = mysql_fetch_object($export_result)) {
        if( !is_null($smf2bbpress_users[$export_row->ID_MEMBER])
                && !is_null($smf2bbpress_forums[$export_row->ID_BOARD])
                && !is_null($smf2bbpress_topics[$export_row->ID_TOPIC]) ) {
            $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['posts'] . " (post_id, forum_id, topic_id, poster_id, post_text, post_time, poster_ip, post_status, post_position) VALUES (
	      " . $smf2bbpress_posts[$export_row->ID_MSG] . ",
	      " . $smf2bbpress_forums[$export_row->ID_BOARD] . ",
	      " . $smf2bbpress_topics[$export_row->ID_TOPIC] . ",
	      " . $smf2bbpress_users[$export_row->ID_MEMBER] . ",
	      '" . addslashes(bpsmf_bbencode_second_pass(bpsmf_bbencode_first_pass(bpsmf_smilies_pass(str_replace("<br />", "\n", $export_row->body)), $export_row->ID_MSG), $export_row->ID_MSG)) . "',
	      '" . $smf_posts_times[$export_row->ID_MSG] . "',
	      '" . $export_row->posterIP . "',
	      0,
              ".($smf_topic_posts[$export_row->ID_MSG]?"1":"2")."
	      );\n"));
//            bb_update_post_positions($smf2bbpress_topics[$export_row->ID_TOPIC]);
        }else {
            $info_skipped_posts++;

            //echo "<b>skipping post " . $smf2bbpress_posts[$export_row->ID_MSG]  . " (" . $smf2bbpress_users[$export_row->ID_MEMBER] . " / " . $smf_posts_times[$export_row->ID_MSG] . ")</b><br/>";
            //. $export_row->body . "<br/><br/>";
        }
    }

    //TODO: no current count
    echo "<li><h3>Importing tags...</h3></li>";
    flush();

    $export_sql = "SELECT ID_TAG, tag, approved FROM " . $phpbb_tables['tags'];
    $export_result = mysql_query($export_sql);
    if(!$export_result) {
        echo("<p><h3>Unable to retrieve tag data from database, skipping tags!</h3>
                    <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
    }
    else{
        while($export_row = mysql_fetch_object($export_result)) {
            $smf2bbpress_tags[$export_row->ID_TAG]=$next_bbpress_tag;
            $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['tags'] . " (tag_id, tag, raw_tag, tag_count) VALUES (
                  " . $next_bbpress_tag . ",
                  '" . sanitize_title($export_row->tag) . "',
                  '" . $export_row->tag . "',
                  0
                  );\n"));
            $next_bbpress_tag++;
        }

        //TODO: no current count
        echo "<li><h3>Importing tagged info...</h3></li>";
        flush();

        $export_sql = "SELECT ID, ID_TAG, ID_TOPIC, ID_MEMBER FROM " . $phpbb_tables['tagmeta'];
        $export_result = mysql_query($export_sql);
        if(!$export_result) {
            echo("<p><h3>Unable to retrieve data from database:</h3>
                        <p><code>".$export_sql."</code></p><blockquote>".mysql_error()."</blockquote></p>");
            return;
        }

        while($export_row = mysql_fetch_object($export_result)) {
            if(!is_null($smf2bbpress_tags[$export_row->ID_TAG])
                    &&!is_null($smf2bbpress_users[$export_row->ID_MEMBER])
                    &&!is_null($smf2bbpress_topics[$export_row->ID_TOPIC])){
                $wpdb->query( $wpdb->prepare( "INSERT INTO " . $bbpress_tables['tagged'] . " (tagged_id, tag_id, user_id, topic_id, tagged_on) VALUES (
                      " . $next_bbpress_tagged . ",
                      " . $smf2bbpress_tags[$export_row->ID_TAG] . ",
                      " . $smf2bbpress_users[$export_row->ID_MEMBER] . ",
                      " . $smf2bbpress_topics[$export_row->ID_TOPIC] . ",
                      '0000-00-00 00:00:00'
                      );\n"));

                $local_export_sql = "SELECT tag_id, tag_count FROM " . $bbpress_tables['tags'] . " WHERE tag_id=".$smf2bbpress_tags[$export_row->ID_TAG];
                $local_export_result = $wpdb->get_results($local_export_sql);

                foreach ( (array) $local_export_result as $local_export_row ) {
                    $wpdb->update( $bbpress_tables['tags'],
                            array(
                            "tag_count"=>($local_export_row->tag_count+1)
                            ),
                            array(
                            "tag_id"=>$local_export_row->tag_id
                            )
                    );
                }

                $next_bbpress_tagged++;
            }
            else{
                $info_skipped_tagged++;
            }
        }
    }
    echo "<p><h3>Done importing!</h3></p>";
    if( $info_skipped_users > 0 )
        echo "<p><i>Skipped ".$info_skipped_users." users that existed in WP already.</i></p>";
    if( $info_skipped_topics > 0 || $info_skipped_posts > 0 )
        echo "<p><i>Skipped ".$info_skipped_topics." topics and ".$info_skipped_posts." posts due to missing users, boards or topics.</i></p>";
    if( $info_skipped_tagged > 0 )
        echo "<p><i>Skipped ".$info_skipped_tagged." tag assignments due to missing users or topics.</i></p>";
    echo "<p><h3>Now go <a href=".site_url()."/wp-content/plugins/buddypress-smf-import/bbpress/bb-admin/upgrade.php>here</a> and upgrade the database!</h3></p>";
    echo "<p><i>After upgrading you can import the bbpress forums in BuddyPress->Forums Setup.</i></p>";

    echo "</ol>";
    echo "</div>";
}

function bpsmf_get_topic_position(){

}

/**
 * Does second-pass bbencoding. This should be used before displaying the message in
 * a thread. Assumes the message is already first-pass encoded, and we are given the
 * correct UID as used in first-pass encoding.
 */
function bpsmf_bbencode_second_pass($text, $uid) {
    global $bbcode_tpl, $bpsmf_templates;

    $text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1&#058;", $text);

    // pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
    // This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
    $text = " " . $text;

    // First: If there isn't a "[" and a "]" in the message, don't bother.
    if (!(strpos($text, "[") && strpos($text, "]")) ) {
        // Remove padding, return.
        $text = substr($text, 1);
        return $text;
    }

    // Only load the templates ONCE..
    if (!defined("BBCODE_TPL_READY")) {
        $tpl = $bpsmf_templates;

        // replace \ with \\ and then ' with \'.
        $tpl = str_replace('\\', '\\\\', $tpl);
        $tpl  = str_replace('\'', '\\\'', $tpl);

        // strip newlines.
        $tpl  = str_replace("\n", '', $tpl);

        // Turn template blocks into PHP assignment statements for the values of $bbcode_tpls..
        $tpl = preg_replace('#<!-- BEGIN (.*?) -->(.*?)<!-- END (.*?) -->#', "\n" . '$bbcode_tpls[\'\\1\'] = \'\\2\';', $tpl);

        $bbcode_tpls = array();
        eval($tpl);
        $bbcode_tpl = $bbcode_tpls;

        $bbcode_tpl['olist_open'] = str_replace('{LIST_TYPE}', '\\1', $bbcode_tpl['olist_open']);
        $bbcode_tpl['color_open'] = str_replace('{COLOR}', '\\1', $bbcode_tpl['color_open']);
        $bbcode_tpl['size_open'] = str_replace('{SIZE}', '\\1', $bbcode_tpl['size_open']);
        $bbcode_tpl['quote_open'] = str_replace('{L_QUOTE}', utf8_decode(I18N_QUOTE), $bbcode_tpl['quote_open']);
        $bbcode_tpl['quote_username_open'] = str_replace('{L_QUOTE}', utf8_decode(I18N_QUOTE), $bbcode_tpl['quote_username_open']);
        $bbcode_tpl['quote_username_open'] = str_replace('{L_WROTE}', utf8_decode(I18N_WROTE), $bbcode_tpl['quote_username_open']);
        $bbcode_tpl['quote_username_open'] = str_replace('{USERNAME}', '\\1', $bbcode_tpl['quote_username_open']);
        $bbcode_tpl['code_open'] = str_replace('{L_CODE}', utf8_decode(I18N_CODE), $bbcode_tpl['code_open']);
        $bbcode_tpl['img'] = str_replace('{URL}', '\\1', $bbcode_tpl['img']);
        $bbcode_tpl['flash'] = str_replace('{URL}', '\\1', $bbcode_tpl['flash']);

        // URLs are done in several different ways.
        $bbcode_tpl['url1'] = str_replace('{URL}', '\\1', $bbcode_tpl['url']);
        $bbcode_tpl['url1'] = str_replace('{DESCRIPTION}', '\\1', $bbcode_tpl['url1']);
        $bbcode_tpl['url2'] = str_replace('{URL}', 'http://\\1', $bbcode_tpl['url']);
        $bbcode_tpl['url2'] = str_replace('{DESCRIPTION}', '\\1', $bbcode_tpl['url2']);
        $bbcode_tpl['url3'] = str_replace('{URL}', '\\1', $bbcode_tpl['url']);
        $bbcode_tpl['url3'] = str_replace('{DESCRIPTION}', '\\2', $bbcode_tpl['url3']);
        $bbcode_tpl['url4'] = str_replace('{URL}', 'http://\\1', $bbcode_tpl['url']);
        $bbcode_tpl['url4'] = str_replace('{DESCRIPTION}', '\\3', $bbcode_tpl['url4']);
        $bbcode_tpl['email'] = str_replace('{EMAIL}', '\\1', $bbcode_tpl['email']);

        define('BBCODE_TPL_READY', true);
    }

    // [CODE] and [/CODE] for posting code (HTML, PHP, C etc etc) in your posts.
    $text = bpsmf_bbencode_second_pass_code($text, $uid, $bbcode_tpl);

    // [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
    $text = str_replace("[quote:$uid]", $bbcode_tpl['quote_open'], $text);
    $text = str_replace("[/quote:$uid]", $bbcode_tpl['quote_close'], $text);

    // New one liner to deal with opening quotes with usernames...
    // replaces the two line version that I had here before..
    $text = preg_replace("/\[quote:$uid=(.*?)\]/si", $bbcode_tpl['quote_username_open'], $text);

    // [list] and [list=x] for (un)ordered lists.
    // unordered lists
    $text = str_replace("[list:$uid]", $bbcode_tpl['ulist_open'], $text);
    // li tags
    $text = str_replace("[li:$uid]", $bbcode_tpl['li_open'], $text);
    $text = str_replace("[/li:$uid]", $bbcode_tpl['li_close'], $text);
    // ending tags
    $text = str_replace("[/list:u:$uid]", $bbcode_tpl['ulist_close'], $text);
    $text = str_replace("[/list:o:$uid]", $bbcode_tpl['olist_close'], $text);
    // Ordered lists
    $text = preg_replace("/\[list=([a1]):$uid\]/si", $bbcode_tpl['olist_open'], $text);

    // colours
    $text = preg_replace("/\[color=(\#[0-9A-F]{6}|[a-z]+):$uid\]/si", $bbcode_tpl['color_open'], $text);
    $text = str_replace("[/color:$uid]", $bbcode_tpl['color_close'], $text);

    // size
    $text = preg_replace("/\[size=([1-2]?[0-9]):$uid\]/si", $bbcode_tpl['size_open'], $text);
    $text = str_replace("[/size:$uid]", $bbcode_tpl['size_close'], $text);

    // [hr]
    $text = str_replace("[hr:$uid]", $bbcoode_tpl['ruler'], $text);

    // [b] and [/b] for bolding text.
    $text = str_replace("[b:$uid]", $bbcode_tpl['b_open'], $text);
    $text = str_replace("[/b:$uid]", $bbcode_tpl['b_close'], $text);

    // [u] and [/u] for underlining text.
    $text = str_replace("[u:$uid]", $bbcode_tpl['u_open'], $text);
    $text = str_replace("[/u:$uid]", $bbcode_tpl['u_close'], $text);

    // [i] and [/i] for italicizing text.
    $text = str_replace("[i:$uid]", $bbcode_tpl['i_open'], $text);
    $text = str_replace("[/i:$uid]", $bbcode_tpl['i_close'], $text);

    // [tt] and [/tt] for monospaced text.
    $text = str_replace("[tt:$uid]", $bbcode_tpl['tt_open'], $text);
    $text = str_replace("[/tt:$uid]", $bbcode_tpl['tt_close'], $text);

    // [sup] and [/sup]
    $text = str_replace("[sup:$uid]", $bbcode_tpl['sup_open'], $text);
    $text = str_replace("[/sup:$uid]", $bbcode_tpl['sup_close'], $text);

    // [s] and [/s]
    $text = str_replace("[s:$uid]", $bbcode_tpl['s_open'], $text);
    $text = str_replace("[/s:$uid]", $bbcode_tpl['s_close'], $text);

    // [center] and [/center]
    $text = str_replace("[center:$uid]", $bbcode_tpl['center_open'], $text);
    $text = str_replace("[/center:$uid]", $bbcode_tpl['center_close'], $text);

    // [shadow] and [/shadow]
    $text = str_replace("[shadow:$uid]", $bbcode_tpl['shadow_open'], $text);
    $text = str_replace("[/shadow:$uid]", $bbcode_tpl['shadow_close'], $text);

    // Tables
    $text = str_replace("[table:$uid]", $bbcode_tpl['table_open'], $text);
    $text = str_replace("[/table:$uid]", $bbcode_tpl['table_close'], $text);
    $text = str_replace("[tr:$uid]", $bbcode_tpl['tr_open'], $text);
    $text = str_replace("[/tr:$uid]", $bbcode_tpl['tr_close'], $text);
    $text = str_replace("[td:$uid]", $bbcode_tpl['td_open'], $text);
    $text = str_replace("[/td:$uid]", $bbcode_tpl['td_close'], $text);

    // Patterns and replacements for URL and email tags..
    $patterns = array();
    $replacements = array();

    // [img]image_url_here[/img] code..
    // This one gets first-passed..
    $patterns[] = "#\[img:$uid\]([^?].*?)\[/img:$uid\]#i";
    $replacements[] = $bbcode_tpl['img'];

    // [flash]flash_url_here[/flash] code..
    $patterns[] = "#\[flash:$uid\]([^?].*?)\[/flash:$uid\]#i";
    $replacements[] = $bbcode_tpl['flash'];

    // matches a [url]xxxx://www.phpbb.com[/url] code..
    $patterns[] = "#\[url\]([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\[/url\]#is";
    $replacements[] = $bbcode_tpl['url1'];

    // [url]www.phpbb.com[/url] code.. (no xxxx:// prefix).
    $patterns[] = "#\[url\]((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*?)\[/url\]#is";
    $replacements[] = $bbcode_tpl['url2'];

    // [url=xxxx://www.phpbb.com]phpBB[/url] code..
    $patterns[] = "#\[url=([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
    $replacements[] = $bbcode_tpl['url3'];

    // [url=www.phpbb.com]phpBB[/url] code.. (no xxxx:// prefix).
    $patterns[] = "#\[url=((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
    $replacements[] = $bbcode_tpl['url4'];

    // [email]user@domain.tld[/email] code..
    $patterns[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
    $replacements[] = $bbcode_tpl['email'];

    $text = preg_replace($patterns, $replacements, $text);

    // Remove our padding from the string..
    $text = substr($text, 1);

    return $text;
}

function bpsmf_bbencode_first_pass($text, $uid) {
    // pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
    // This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
    $text = " " . $text;

    // [CODE] and [/CODE] for posting code (HTML, PHP, C etc etc) in your posts.
    $text = bpsmf_bbencode_first_pass_pda($text, $uid, '[code]', '[/code]', '', true, '');

    // [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
    $text = bpsmf_bbencode_first_pass_pda($text, $uid, '[quote]', '[/quote]', '', false, '');
    $text = bpsmf_bbencode_first_pass_pda($text, $uid, '/\[quote author=(.*) link[^]]*\]/is', '[/quote]', '', false, '', "[quote:$uid=\\1]");

    // [list] and [list=x] for (un)ordered lists.
    $open_tag = array();
    $open_tag[0] = "[list]";

    // unordered..
    $text = bpsmf_bbencode_first_pass_pda($text, $uid, $open_tag, "[/list]", "[/list:u]", false, 'bpsmf_replace_listitems');

    $open_tag[0] = "[list=1]";
    $open_tag[1] = "[list=a]";

    // ordered.
    $text = bpsmf_bbencode_first_pass_pda($text, $uid, $open_tag, "[/list]", "[/list:o]",  false, 'bpsmf_replace_listitems');

    // [li] and [/li]
    $text = preg_replace("#\[li\](.*?)\[/li\]#si", "[li:$uid]\\1[/li:$uid]", $text);

    // [color] and [/color] for setting text color
    $text = preg_replace("#\[color=(\#[0-9A-F]{6}|[a-z\-]+)\](.*?)\[/color\]#si", "[color=\\1:$uid]\\2[/color:$uid]", $text);

    // [size] and [/size] for setting text size
    $text = preg_replace("#\[size=([1-2]?[0-9])pt\](.*?)\[/size\]#si", "[size=\\1:$uid]\\2[/size:$uid]", $text);

    // [b] and [/b] for bolding text.
    $text = preg_replace("#\[b\](.*?)\[/b\]#si", "[b:$uid]\\1[/b:$uid]", $text);

    // [u] and [/u] for underlining text.
    $text = preg_replace("#\[u\](.*?)\[/u\]#si", "[u:$uid]\\1[/u:$uid]", $text);

    // [i] and [/i] for italicizing text.
    $text = preg_replace("#\[i\](.*?)\[/i\]#si", "[i:$uid]\\1[/i:$uid]", $text);

    // [center] and [/center]
    $text = preg_replace("#\[center\](.*?)\[/center\]#si", "[center:$uid]\\1[/center:$uid]", $text);

    // [s] and [/s]
    $text = preg_replace("#\[s\](.*?)\[/s\]#si", "[s:$uid]\\1[/s:$uid]", $text);

    // [shadow] and [/shadow]
    $text = preg_replace("#\[shadow[^]]*\](.*?)\[/shadow\]#si", "[shadow:$uid]\\1[/shadow:$uid]", $text);

    // [sup] and [/sup]
    $text = preg_replace("#\[sup\](.*?)\[/sup\]#si", "[sup:$uid]\\1[/sup:$uid]", $text);

    // [tt] and [/tt]
    $text = preg_replace("#\[tt\](.*?)\[/tt\]#si", "[tt:$uid]\\1[/tt:$uid]", $text);

    // Tables
    $text = preg_replace("#\[table\](.*?)\[/table\]#si", "[table:$uid]\\1[/table:$uid]", $text);
    $text = preg_replace("#\[tr\](.*?)\[/tr\]#si", "[tr:$uid]\\1[/tr:$uid]", $text);
    $text = preg_replace("#\[td\](.*?)\[/td\]#si", "[td:$uid]\\1[/td:$uid]", $text);

    // [hr]
    $text = preg_replace("#\[hr\]#si", "[hr:$uid]", $text);

    // [img]image_url_here[/img] code..
    $text = preg_replace("#\[img[^]]*\]((http|ftp|https|ftps)://)([^[\n\r\t<]*?)\[/img\]#sie", "'[img:$uid]\\1' . str_replace(' ', '%20', '\\3') . '[/img:$uid]'", $text);

    // [flash]flash_url_here[/flash] code..
    $text = preg_replace("#\[flash.[0-9]*,[0-9]*\]((http|ftp|https|ftps)://)([^[\n\r\t<]*?)\[/flash\]#sie", "'[flash:$uid]\\1' . str_replace(' ', '%20', '\\3') . '[/flash:$uid]'", $text);

    // Remove our padding from the string.
    return substr($text, 1);
    ;
}

/**
 * $text - The text to operate on.
 * $uid - The UID to add to matching tags.
 * $open_tag - The opening tag to match. Can be an array of opening tags.
 * $close_tag - The closing tag to match.
 * $close_tag_new - The closing tag to replace with.
 * $mark_lowest_level - boolean - should we specially mark the tags that occur
 *                                      at the lowest level of nesting? (useful for [code], because
 *                                              we need to match these tags first and transform HTML tags
 *                                              in their contents..
 * $func - This variable should contain a string that is the name of a function.
 *                              That function will be called when a match is found, and passed 2
 *                              parameters: ($text, $uid). The function should return a string.
 *                              This is used when some transformation needs to be applied to the
 *                              text INSIDE a pair of matching tags. If this variable is FALSE or the
 *                              empty string, it will not be executed.
 * If open_tag is an array, then the pda will try to match pairs consisting of
 * any element of open_tag followed by close_tag. This allows us to match things
 * like [list=A]...[/list] and [list=1]...[/list] in one pass of the PDA.
 *
 * NOTES:       - this function assumes the first character of $text is a space.
 *              - every opening tag and closing tag must be of the [...] format.
 */
function bpsmf_bbencode_first_pass_pda($text, $uid, $open_tag, $close_tag, $close_tag_new, $mark_lowest_level, $func, $open_regexp_replace = false) {
    $open_tag_count = 0;

    if (!$close_tag_new || ($close_tag_new == ''))
        $close_tag_new = $close_tag;

    $close_tag_length = strlen($close_tag);
    $close_tag_new_length = strlen($close_tag_new);
    $uid_length = strlen($uid);
    $use_function_pointer = ($func && ($func != ''));
    $stack = array();

    if (is_array($open_tag)) {
        if (0 == count($open_tag)) {
            // No opening tags to match, so return.
            return $text;
        }
        $open_tag_count = count($open_tag);
    }
    else {
        // only one opening tag. make it into a 1-element array.
        $open_tag_temp = $open_tag;
        $open_tag = array();
        $open_tag[0] = $open_tag_temp;
        $open_tag_count = 1;
    }

    $open_is_regexp = false;

    if ($open_regexp_replace) {
        $open_is_regexp = true;
        if (!is_array($open_regexp_replace)) {
            $open_regexp_temp = $open_regexp_replace;
            $open_regexp_replace = array();
            $open_regexp_replace[0] = $open_regexp_temp;
        }
    }

    if ($mark_lowest_level && $open_is_regexp) {
        die('Unsupported operation for bbcode_first_pass_pda().');
    }

    // Start at the 2nd char of the string, looking for opening tags.
    $curr_pos = 1;
    while ($curr_pos && ($curr_pos < strlen($text))) {
        $curr_pos = strpos($text, "[", $curr_pos);

        // If not found, $curr_pos will be 0, and the loop will end.
        if ($curr_pos) {
            // We found a [. It starts at $curr_pos.
            // check if it's a starting or ending tag.
            $found_start = false;
            $which_start_tag = "";
            $start_tag_index = -1;

            for ($i = 0; $i < $open_tag_count; $i++) {
                // Grab everything until the first "]"...
                $possible_start = substr($text, $curr_pos, strpos($text, ']', $curr_pos + 1) - $curr_pos + 1);

                // We're going to try and catch usernames with "[' characters.
                if( preg_match('#\[quote=\\\"#si', $possible_start, $match) && !preg_match('#\[quote=\\\"(.*?)\\\"\]#si', $possible_start) ) {
                    // OK we are in a quote tag that probably contains a ] bracket.
                    // Grab a bit more of the string to hopefully get all of it..
                    if ($close_pos = strpos($text, '"]', $curr_pos + 9)) {
                        if (strpos(substr($text, $curr_pos + 9, $close_pos - ($curr_pos + 9)), '[quote') === false) {
                            $possible_start = substr($text, $curr_pos, $close_pos - $curr_pos + 2);
                        }
                    }
                }

                // Now compare, either using regexp or not.
                if ($open_is_regexp) {
                    $match_result = array();
                    if (preg_match($open_tag[$i], $possible_start, $match_result)) {
                        $found_start = true;
                        $which_start_tag = $match_result[0];
                        $start_tag_index = $i;
                        break;
                    }
                }
                else {
                    // straightforward string comparison.
                    if (0 == strcasecmp($open_tag[$i], $possible_start)) {
                        $found_start = true;
                        $which_start_tag = $open_tag[$i];
                        $start_tag_index = $i;
                        break;
                    }
                }
            }

            if ($found_start) {
                // We have an opening tag.
                // Push its position, the text we matched, and its index in the open_tag array on to the stack, and then keep going to the right.
                $match = array("pos" => $curr_pos, "tag" => $which_start_tag, "index" => $start_tag_index);
                array_push($stack, $match);
                // Rather than just increment $curr_pos
                // Set it to the ending of the tag we just found
                // Keeps error in nested tag from breaking out
                // of table structure..
                $curr_pos += strlen($possible_start);
            }
            else {
                // check for a closing tag..
                $possible_end = substr($text, $curr_pos, $close_tag_length);
                if (0 == strcasecmp($close_tag, $possible_end)) {
                    // We have an ending tag.
                    // Check if we've already found a matching starting tag.
                    if (sizeof($stack) > 0) {
                        // There exists a starting tag.
                        $curr_nesting_depth = sizeof($stack);
                        // We need to do 2 replacements now.
                        $match = array_pop($stack);
                        $start_index = $match['pos'];
                        $start_tag = $match['tag'];
                        $start_length = strlen($start_tag);
                        $start_tag_index = $match['index'];

                        if ($open_is_regexp) {
                            $start_tag = preg_replace($open_tag[$start_tag_index], $open_regexp_replace[$start_tag_index], $start_tag);
                        }

                        // everything before the opening tag.
                        $before_start_tag = substr($text, 0, $start_index);

                        // everything after the opening tag, but before the closing tag.
                        $between_tags = substr($text, $start_index + $start_length, $curr_pos - $start_index - $start_length);

                        // Run the given function on the text between the tags..
                        if ($use_function_pointer) {
                            $between_tags = $func($between_tags, $uid);
                        }

                        // everything after the closing tag.
                        $after_end_tag = substr($text, $curr_pos + $close_tag_length);

                        // Mark the lowest nesting level if needed.
                        if ($mark_lowest_level && ($curr_nesting_depth == 1)) {
                            if ($open_tag[0] == '[code]') {
                                $code_entities_match = array('#<#', '#>#', '#"#', '#:#', '#\[#', '#\]#', '#\(#', '#\)#', '#\{#', '#\}#');
                                $code_entities_replace = array('&lt;', '&gt;', '&quot;', '&#58;', '&#91;', '&#93;', '&#40;', '&#41;', '&#123;', '&#125;');
                                $between_tags = preg_replace($code_entities_match, $code_entities_replace, $between_tags);
                            }
                            $text = $before_start_tag . substr($start_tag, 0, $start_length - 1) . ":$curr_nesting_depth:$uid]";
                            $text .= $between_tags . substr($close_tag_new, 0, $close_tag_new_length - 1) . ":$curr_nesting_depth:$uid]";
                        }
                        else {
                            if ($open_tag[0] == '[code]') {
                                $text = $before_start_tag . '&#91;code&#93;';
                                $text .= $between_tags . '&#91;/code&#93;';
                            }
                            else {
                                if ($open_is_regexp) {
                                    $text = $before_start_tag . $start_tag;
                                }
                                else {
                                    $text = $before_start_tag . substr($start_tag, 0, $start_length - 1) . ":$uid]";
                                }
                                $text .= $between_tags . substr($close_tag_new, 0, $close_tag_new_length - 1) . ":$uid]";
                            }
                        }

                        $text .= $after_end_tag;

                        // Now.. we've screwed up the indices by changing the length of the string.
                        // So, if there's anything in the stack, we want to resume searching just after it.
                        // otherwise, we go back to the start.
                        if (sizeof($stack) > 0) {
                            $match = array_pop($stack);
                            $curr_pos = $match['pos'];
                        }
                        else {
                            $curr_pos = 1;
                        }
                    }
                    else {
                        // No matching start tag found. Increment pos, keep going.
                        ++$curr_pos;
                    }
                }
                else {
                    // No starting tag or ending tag.. Increment pos, keep looping.,
                    ++$curr_pos;
                }
            }
        }
    } // while

    return $text;
}

/**
 * Does second-pass bbencoding of the [code] tags. This includes
 * running htmlspecialchars() over the text contained between
 * any pair of [code] tags that are at the first level of
 * nesting. Tags at the first level of nesting are indicated
 * by this format: [code:1:$uid] ... [/code:1:$uid]
 * Other tags are in this format: [code:$uid] ... [/code:$uid]
 */
function bpsmf_bbencode_second_pass_code($text, $uid, $bbcode_tpl) {
    $code_start_html = $bbcode_tpl['code_open'];
    $code_end_html =  $bbcode_tpl['code_close'];

    // First, do all the 1st-level matches. These need an htmlspecialchars() run,
    // so they have to be handled differently.
    $match_count = preg_match_all("#\[code:1:$uid\](.*?)\[/code:1:$uid\]#si", $text, $matches);

    for ($i = 0; $i < $match_count; $i++) {
        $before_replace = $matches[1][$i];
        $after_replace = $matches[1][$i];

        // Replace 2 spaces with "&nbsp; " so non-tabbed code indents without making huge long lines.
        $after_replace = str_replace("  ", "&nbsp; ", $after_replace);
        // now Replace 2 spaces with " &nbsp;" to catch odd #s of spaces.
        $after_replace = str_replace("  ", " &nbsp;", $after_replace);

        // Replace tabs with "&nbsp; &nbsp;" so tabbed code indents sorta right without making huge long lines.
        $after_replace = str_replace("\t", "&nbsp; &nbsp;", $after_replace);

        // now Replace space occurring at the beginning of a line
        $after_replace = preg_replace("/^ {1}/m", '&nbsp;', $after_replace);

        // normen-returns
        $after_replace = str_replace("<br />", "\n",  $after_replace);

        $str_to_match = "[code:1:$uid]" . $before_replace . "[/code:1:$uid]";

        $replacement = $code_start_html.$after_replace.$code_end_html;
        $text = str_replace($str_to_match, $replacement, $text);
    }

    // Now, do all the non-first-level matches. These are simple.
    $text = str_replace("[code:$uid]", $code_start_html, $text);
    $text = str_replace("[/code:$uid]", $code_end_html, $text);

    return $text;
}

/**
 * This is used to change a [*] tag into a [*:$uid] tag as part
 * of the first-pass bbencoding of [list] tags. It fits the
 * standard required in order to be passed as a variable
 * function into bpsmf_bbencode_first_pass_pda().
 */
function bpsmf_replace_listitems($text, $uid) {
    return str_replace("[*]", "[*:$uid]", $text);
}

function bpsmf_smilies_pass($message) {
    global $bpsmf_smilies;

    $orig = $repl = array();
    foreach ($bpsmf_smilies as $smily => $translation) {
        $orig[] = "/(?<=.\W|\W.|^\W)" . str_replace('/', '\\'.'/', preg_quote($smily))."(?=.\W|\W.|\W$)/";
        $repl[] = $translation;
    }

    if (count($orig))
        $message = substr(preg_replace($orig, $repl, ' ' . $message . ' '), 1, -1);

    return $message;
}
?>
