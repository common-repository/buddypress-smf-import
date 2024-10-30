<?php
/**
 * Links SMF forum requests to correct wordpress group-forum topic
 * Needs smf_forum_id in wp_bb_topicmeta
 * (c) 2010 Normen Hansen
 */

/**
 * SETTINGS:
 */

//relative path to WordPress site root from this scripts location
$path_to_wordpress = "../";


require($path_to_wordpress.'wp-load.php');
$my_base_url = site_url().'/';
do_action( 'bbpress_init' );
$my_topic_string = (string)$_GET["topic"];
if($my_topic_string){
$my_topic_id_bp = split("\.",$my_topic_string);
$my_topic_id = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM ".$wpdb->base_prefix."bb_topicmeta WHERE meta_key = 'smf_topic_id' AND meta_value =  $my_topic_id_bp[0] "))->topic_id;
$my_topic = get_topic( $my_topic_id );
    $my_group_slug = $wpdb->get_row( $wpdb->prepare(
	                "SELECT slug FROM  {$bp->groups->table_name} g, {$bp->groups->table_name_groupmeta} gm WHERE g.id = gm.group_id AND gm.meta_key = 'forum_id' AND gm.meta_value = %d", $my_topic->forum_id ));
    $my_url = "groups/$my_group_slug->slug/forum/topic/$my_topic->topic_slug";
}
else{
    $my_url = "forums/";
}
//echo "using topic id: $my_topic_id<br>";
//echo "topic title: $my_topic->topic_title<br>";
//echo "group title: $my_group_slug->slug<br>";
//echo "generated URL: $my_url<br>";
bp_core_redirect("$my_base_url$my_url");
?>
