<?php
/*
Copyright (c) 2012 Tobias Florek. All rights reserved.
Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDER ``AS IS' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class Settings extends GenericBackendPage {
    function __construct() {
        $this->label = 'CCK-Settings';

        add_action('admin_init',                    array($this, 'admin_init'));
        add_action('wp_ajax_wpc_regen_post_count',  array($this, 'post_count'));
        add_action('wp_ajax_wpc_regen_fields',      array($this, 'regen_fields'));

        add_action('wp_ajax_wpc_db_migrate',        array($this, 'db_migrate'));

        add_action('wp_ajax_wpc_patch',             array($this, 'ajax_patch'));
        add_action('wp_ajax_wpc_patch_all',         array($this, 'ajax_patch_all'));

        $this->core_patch_dir = dirname(__FILE__).'/core-patches';
        parent::__construct();
    }

    function admin_init() {

    }

    function echo_backend_page() {
        global $wpc_content_types, $wpc_relationships;

        $icon ='options-general';

        $filter = function($type) {
            return !empty($type->generated_values);
        };
        $all_types_options = join("\n", array_map(function ($type) {
            return "<option value='$type->id'>$type->label</option>";
        }, array_filter($wpc_content_types + $wpc_relationships, $filter)));

        if ( !empty($all_types_options) ) {
    ?>
        <div class='wrap'>
            <div id='icon-$icon' class='icon32'><br /></div>
            <h2>CCK Settings</h2>

            <h3>Regenerate generated fields</h3>
            <hgroup>
                <select id='wpc_regen_content_type'>
                    <option value='all'>All</option>
                    <?php echo $all_types_options ?>
                </select>
                <a href='#' class='button' id='wpc_regen_start'>Regenerate</a>
                <a href='#' class='button' id='wpc_regen_stop' style='display: none;'>Stop</a>
            </hgroup>
            <div id="wpc_regen_progressbar_div" style="height:40px">
                <progress id='wpc_regen_progressbar' min='0' max='100' value='0' style="display: none; margin: 10px 0; width: 100%"></progress>
            </div>

            <div id="wpc_regen_log_div" style='display: none;'>
                <h4>Log</h4>
                <ul id='wpc_regen_log' style="font-family: menlo,monaco,consolas,monospace"></ul>
            </div>
    <?php } ?>
            <h3>Migrate DB</h3>
            <hgroup>
                <a href='#' class='button' id='wpc_db_migrate_start'>migrate</a>
            </hgroup>

            <h3>Patch core Wordpress</h3>
            <?php
                $all_patches = $this->core_patches();
                if (empty($all_patches))
                    echo "<p>There are no patches to apply.</p>\n";
                else {
                    $patchlink = function($patch) {
                        return "<li><a href='#' class='wpc_patch' id='wpc_patch_$patch'>".
                                basename($patch, '.patch').
                            "</a></li>";
                    };
                    echo "<p>Select patch to apply.\n";
                    echo "<ul>".join("\n", array_map($patchlink, $all_patches) )."</ul>\n";
                    echo 'Or <a href="#" id="wpc_patch_all">apply all patches</a>.</p>';
                }
            ?>
            <div id="wpc_patch_log_div" style='display: none;'>
                <h4>Log</h4>
                <ul id='wpc_patch_log' style="font-family: menlo,monaco,consolas,monospace"></ul>
            </div>
        </div>
        <script type="text/javascript">
            var wpc_settings_nonce  = "<?php echo wp_create_nonce('wpc_settings_nonce') ?>";
        </script>
            <?php
    }

    function db_migrate(){
        function create_column_sql_for_field($field, $table, $parent_id, $wpc_type) {
            global $wpdb;

            $sql = "";

            if ( !empty($field) ) switch ($field->type) {
                case 'TextField':
                case 'SelectField':
                case 'TextAreaField':
                case 'RichTextField':
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$field->id` text DEFAULT NULL;";
                    break;
                case 'CheckBoxField':
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$field->id` tinyint(1) DEFAULT NULL;";
                    break;
                case 'TimeField':
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$field->id` time DEFAULT NULL;";
                    break;
                case 'DateField':
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$field->id` date DEFAULT NULL;";
                    break;
                case 'YearField':
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$field->id` YEAR DEFAULT NULL;";
                    break;
                case 'FileField':
                case 'ImageField':
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$field->id` int(11) DEFAULT NULL;";
                    break;
                case 'FormattedString':
                    break;
                default:
                    _log("unhandeled field type '$field->type' for $field->id / $parent_id");
                    break;
            }

            if (!empty($sql) ) {
                $wpdb->query($sql);

                if ($wpc_type == "post_type") {
                    $sql = "SELECT wp_posts.ID, wp_postmeta.meta_value FROM wp_postmeta
                            INNER JOIN wp_posts on wp_posts.ID = wp_postmeta.post_id
                            WHERE wp_posts.post_type = '$parent_id'
                            AND wp_postmeta.meta_key = '$field->id'
                            AND wp_postmeta.post_id IN (
                              SELECT wp_posts.ID FROM wp_posts
                              WHERE wp_posts.post_type = '$parent_id'
                            )";

                    $rows = $wpdb->get_results($sql);

                    foreach ($rows as $row) {
                        $sql = "";

                        switch ($field->type) {
                            case 'CheckBoxField':
                                $sql = "UPDATE $table SET `$field->id` = ('$row->meta_value' = 'true') WHERE `post_id` = $row->ID";
                                //_log($sql);
                                break;

                                case 'TextField':
                                case 'SelectField':
                                case 'TextAreaField':
                                case 'RichTextField':
                                $val = preg_replace("/([^\\\])'/", "$1\\'", $row->meta_value);

                                $sql = "UPDATE $table SET `$field->id` = '$val' WHERE `post_id` = $row->ID";
                                break;

                            default:
                                $sql = "UPDATE $table SET `$field->id` = '$row->meta_value' WHERE `post_id` = $row->ID";
                                break;
                        }

                        if ( !empty($sql) )
                            $wpdb->query($sql);
                    }
                }
                if ($wpc_type == "relation")  {
                    $sql = "SELECT wp_wpc_relations.relation_id AS id, wp_wpc_relations.post_from_id, wp_wpc_relations.post_to_id, wp_wpc_relations_meta.meta_key AS 'key', wp_wpc_relations_meta.meta_value AS value FROM wp_wpc_relations
                            INNER JOIN wp_wpc_relations_meta ON wp_wpc_relations_meta.relation_id = wp_wpc_relations.relation_id
                            WHERE wp_wpc_relations.relationship_id = '$parent_id'
                            AND wp_wpc_relations_meta.meta_key = '$field->id'";

                    $rows = $wpdb->get_results($sql);

                    foreach ($rows as $row) {
                        $sql = "INSERT INTO $table (`id`, `post_from_id`, `post_to_id`, `$row->key`) VALUES ($row->id, $row->post_from_id, $row->post_to_id, '$row->value') ON DUPLICATE KEY UPDATE `$row->key` = '$row->value'";
                        #_log($sql);
                        $wpdb->query($sql);
                    }
                }
            }

            return ;
        }
        global $wpdb, $wpc_content_types, $wpc_relationships;

        $ret = array('errors' => array());

        if (! empty($_POST) || check_admin_referer('wpc_settings_nonce', 'nonce')) {
            $ret['new_nonce'] = wp_create_nonce('wpc_settings_nonce');

            _log("\n\nSTARTING MIGRATION");

            foreach ($wpc_content_types as $content_type) {
                $sql = "DROP TABLE IF EXISTS `$content_type->table`";
                $wpdb->query($sql);

                $sql = "CREATE TABLE `$content_type->table` (
                          `post_id` int(11) unsigned NOT NULL DEFAULT '0',
                          PRIMARY KEY (`post_id`),
                          UNIQUE KEY `id_wp` (`post_id`)
                        ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;";
                $wpdb->query($sql);


                $sql = "SELECT wp_posts.ID FROM wp_posts
                        WHERE wp_posts.post_type = '$content_type->id'";

                $posts = $wpdb->get_results($sql);

                foreach ($posts as $post) {
                    $sql = "INSERT INTO $content_type->table (`post_id`) VALUES ($post->ID)";
                    $wpdb->query($sql);
                }

                foreach ($content_type->fields as $fields) {
                    create_column_sql_for_field($fields, $content_type->table, $content_type->id, "post_type");
                }
            }
            foreach ($wpc_relationships as $relation) {
                $sql = "DROP TABLE IF EXISTS `$relation->table`";
                $wpdb->query($sql);

                $sql = "CREATE TABLE `$relation->table` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `post_from_id` int(11) unsigned DEFAULT NULL,
                          `post_to_id` int(11) unsigned DEFAULT NULL,
                          PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;";

                $wpdb->query($sql);

                $sql = "SELECT wp_wpc_relations.relation_id AS id, wp_wpc_relations.post_from_id, wp_wpc_relations.post_to_id FROM wp_wpc_relations
                        WHERE wp_wpc_relations.relationship_id = '$relation->id'";

                $rows = $wpdb->get_results($sql);
                #_log($sql);

                foreach ($rows as $row) {
                    $sql = "INSERT INTO $relation->table (`id`, `post_from_id`, `post_to_id`) VALUES ($row->id, $row->post_from_id, $row->post_to_id)";
                    #_log($sql);
                    $wpdb->query($sql);
                }

                foreach ($relation->fields as $fields) {
                    create_column_sql_for_field($fields, $relation->table, $relation->id, "relation");
                }
            }

            //_log("\n\nADDING NEW COLUMNS");
            //$wpdb->query("ALTER TABLE `wp_wpc_event` ADD COLUMN semester TEXT");
            //$wpdb->query("ALTER TABLE `wp_wpc_event` ADD COLUMN notice TEXT");
            //$wpdb->query("ALTER TABLE `wp_wpc_event` ADD COLUMN semester_year YEAR");
        } else
            array_push($ret['errors'], 'U can\'t touch this!');

        echo json_encode($ret);
        die();
    }

    function core_patches() {
        return array_map('basename', glob("$this->core_patch_dir/*.patch"));
    }

    /**
     * @return array associative array with slugs of failed patches
     */
    function core_patch_many($patches) {
        return array_keys(array_filter(
            array_map(array($this, 'core_patch'), array_combine($patches, $patches)),
            function($success) {return ! $success;}
        ));
    }

    /**
     * patch wordpress core with a specific patch
     * @return bool whether the patch was successfully applied
     */
    function core_patch($patchslug) {
        $wp_basedir = ABSPATH;
        $patchfile = $this->core_patch_dir."/$patchslug";
        // sanity check
        if (! file_exists($patchfile))
            return false;

        _log('start');

        $patch_cmd = 'patch -fs -d '.escapeshellarg($wp_basedir).' -p0 < '.
            escapeshellarg($patchfile);
        $test_cmd  = $patch_cmd.' --dry-run';

        if ($this->_core_patch_run_cmd($test_cmd))
            return $this->_core_patch_run_cmd($patch_cmd);
        else
            return false;
    }

    protected function _core_patch_run_cmd($cmd) {
        $out = array();
        $ret;
        _log($cmd);
        exec($cmd, $out, $ret);
        _log($out);

        return $ret === 0;
    }

    function ajax_patch_all() {
        $this->ajax_do_patch($this->core_patches());
    }

    function ajax_patch() {
        if (! empty($_POST['id']))
            $this->ajax_do_patch(array($_POST['id']));
    }
    protected function ajax_do_patch($patches) {
        $ret = array('errors' => array());

        if (! empty($_POST) || check_admin_referer('wpc_settings_nonce', 'nonce')) {
            $ret['new_nonce'] = wp_create_nonce('wpc_settings_nonce');

            $ret['errors'] = $this->core_patch_many($patches);
        }
        echo json_encode($ret);
        die();
    }

    function collection_for_slug($type) {
        global $wpc_content_types, $wpc_relationships;

        $collection = false;

        if (isset($wpc_content_types[$type]))
            $collection = WPCRecordCollection::records_for_type($type);
        elseif (isset($wpc_relationships[$type]))
            $collection = WPCRelationCollection::relations_by_id($type);

        return $collection;
    }

    function post_count() {
        $ret = array('errors' => array());

        if (! empty($_POST) || check_admin_referer('wpc_settings_nonce', 'nonce')) {
            $type = $_POST['type'];
            if ($collection = $this->collection_for_slug($type)) {
                $ret['new_nonce'] = wp_create_nonce('wpc_settings_nonce');
                $ret['post_count'] = $collection
                    ->filter('post_status', 'publish')
                    ->count();
            } else
                array_push($ret['errors'], "No such record or relation type: '$type'");
        } else
            array_push($ret['errors'], 'U can\'t touch this!');

        echo json_encode($ret);
        die();
    }

    function regen_fields() {
        $ret = array('errors' => array());
        $defaults = array('last_id' => -1, 'limit'=>100, 'type'=>'');

        if (! empty($_POST) || check_admin_referer('wpc_settings_nonce', 'nonce')) {
            $ret['new_nonce'] = wp_create_nonce('wpc_settings_nonce');
            $args = (object) wp_parse_args($_POST, $defaults);
            $type = $args->type;
            $limit = $args->limit;
            $last_id = $args->last_id;

            if ($collection = $this->collection_for_slug($type)) {
                $record = $collection
                    ->filter('id', $last_id, '>')
                    ->limit($limit)
                    ->each(function ($rec) {
                        $rec->commit(null, true);
                    })->last_record();
                $ret['last_id'] = intval($record? $record->id : -1);
            } else
                array_push($ret['errors'], "No such record or relation type: '$type'");
        } else
            array_push($ret['errors'], 'U can\'t touch this!');

        echo json_encode($ret);
        die();
    }
}
?>
