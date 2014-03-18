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

        $types_and_relations    = array();
        if ( is_array($wpc_content_types) ) $types_and_relations    = array_merge($types_and_relations, $wpc_content_types);
        if ( is_array($wpc_relationships) ) $types_and_relations    = array_merge($types_and_relations, $wpc_relationships);

        $all_types_options = join("\n", array_map(function ($type) {
            return "<option value='$type->id'>$type->label</option>";
        }, array_filter($types_and_relations, $filter)));

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
