<div class="wrap">
    <?php
    if ( isset( $_POST['import'] ) && check_admin_referer('bpsmf_admin_settings')) {
        if(isset($_POST['smf_db_name'])&&isset($_POST['smf_db_user'])&&isset($_POST['smf_db_password'])&&isset($_POST['smf_db_host'])&&isset($_POST['smf_db_prefix'])){
            echo "</h3></p>WARNING! If you stop loading this page the process will finish in the background, dont reload/restart it immediately or your imported forums will get messed up!</h3></p>";
            $import_options=array(
                'smf_db_name'=>$_POST['smf_db_name'],
                'smf_db_user'=>$_POST['smf_db_user'],
                'smf_db_password'=>$_POST['smf_db_password'],
                'smf_db_host'=>$_POST['smf_db_host'],
                'smf_db_prefix'=>$_POST['smf_db_prefix']
            );
            bpsmf_do_import($import_options);
        }else{
            echo "</h3></p>ERROR! Please specify all parameters!</h3></p>";
        }
    }
    else if ( isset( $_POST['creategroups'] ) && check_admin_referer('bpsmf_admin_settings')) {
        bpsmf_create_forum_groups();
        echo "<p><h3>Created forum groups!</h3></p>";
        echo "<p><a href='admin.php?page=bpsmf_admin_settings'>Go back</a></p>";
    }
    else if ( isset( $_POST['forumparents'] ) && check_admin_referer('bpsmf_admin_settings')) {
        bpsmf_set_forum_parent();
        echo "<p><h3>Updated forum parents!</h3></p>";
        echo "<p><a href='admin.php?page=bpsmf_admin_settings'>Go back</a></p>";
    }
    else if ( isset( $_POST['usernicename'] ) && check_admin_referer('bpsmf_admin_settings')) {
        bpsmf_create_user_nicenames();
        echo "<p><h3>Created User Slugs!</h3></p>";
        echo "<p><a href='admin.php?page=bpsmf_admin_settings'>Go back</a></p>";
    }
    else if ( isset( $_POST['updatebbconfig'] ) && check_admin_referer('bpsmf_admin_settings')) {
        bpsmf_update_bbpress_config();
        echo "<p><h3>Updated bbpress config file!</h3></p>";
        echo "<p><a href='admin.php?page=bpsmf_admin_settings'>Go back</a></p>";
    }
    else {
        ?>

    <h2>BuddyPress SMF importer</h2>
    <p>This plugin can convert a SMF 1.1x (www.simplemachines.org) forum database to BBPress "Bix" 0.73 (www.bbpress.org) format.
        After the conversion the bbpress database must be upgraded with the included BBPress 1.0.2 version.</p>
    <p>The file <code>/wp-content/plugins/buddypress-smf-import/bbpress/bb-config.php</code> should be writable by the server or you will have to edit it by hand after importing the data!</p>
    <p>After importing enable the "SMF Password Compatibility" plugin, it will convert the users passwords on their next login.</p>

    <h3>1) Import SMF database</h3>
    <form action="admin.php?page=bpsmf_admin_settings" method="post" id="buddypress-smf-import-admin-form">
        <p>SMF DB Name: <input type="text" name="smf_db_name" value="database" /></p>
        <p>SMF DB User: <input type="text" name="smf_db_user" value="user" /></p>
        <p>SMF DB Pass: <input type="text" name="smf_db_password" value="password" /></p>
        <p>SMF DB Host: <input type="text" name="smf_db_host" value="localhost" /></p>
        <p>SMF DB Prefix: <input type="text" name="smf_db_prefix" value="smf_" /></p>
        <p><b>WARNING: Importing the SMF database deletes all buddypress forums and forum posts!</b></p>
        <p class="submit">
            <input class="button-primary" type="submit" name="import" value="Import"/>
        </p>

        <h3>2) Setup BBPress in BuddyPress</h3>
        <ul>
            <li>Upgrade the BBPress database <a href=<?php site_url(); ?>/wp-content/plugins/buddypress-smf-import/bbpress/bb-admin/upgrade.php>here</a> if not already done.</li>
            <li>Go <a href="admin.php?page=bb-forums-setup">here</a>, press "Use existing bbpress installation" and add <code>wp-content/plugins/buddypress-smf-import/bbpress/</code> to the path thats presented, then press "Complete Installation".</li>
        </ul>

        <h3>3) Fixes for problems</h3>
        <p>These will only work after the forums have been activated</p>
        <p class="submit">
            <input class="button-primary" type="submit" name="usernicename" value="Generate User Slugs"/>
            <i>Use this if you cannot see imported users profiles</i>
        </p>
        <p class="submit">
            <input class="button-primary" type="submit" name="forumparents" value="Update Forum Parents"/>
            <i>Use this if your forums dont show up in the "BuddyPress Forum Extras" forum index</i>
        </p>
        <p class="submit">
            <input class="button-primary" type="submit" name="updatebbconfig" value="Rewrite bbconfig file"/>
            <i>Use this if you get database errors after updating the plugin.</i>
        </p>

        <h3>4) Add Groups for Forums (optional)</h3>
        <p>You can create groups for the forums with this button:</p>
        <p class="submit">
            <input class="button-primary" type="submit" name="creategroups" value="Create Groups from Forums"/>
            <i>Only do this once!</i>
        </p>

        <h3>5) Redirect for old forum topics (optional)</h3>
        <p><i>This only works when forum groups have been created.</i></p>
        <p>You can copy the file in <code>/buddypress-smf-import/redirect/index.php</code> into your old forum directory to redirect your users to the new topics. <i>(Manual editing might be required to set the relative path)</i></p>

        <?php
        wp_nonce_field( 'bpsmf_admin_settings' );
        ?>
    </form>
    <h3>About</h3>
    <p>This plugin was written by Normen Hansen for <a href="http://www.jmonkeyengine.com">jMonkeyEngine.com</a>, based on a php import script developed by lonemadmax based on the previous work of Jaime GÓMEZ OBREGÓN from ITEISA, Bruno Torres and The phpBB Group.</p>
        <?php
    }
    ?>
</div>