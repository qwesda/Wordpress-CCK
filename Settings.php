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

        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_wpc_regen_post_count', array($this, 'post_count'));
        add_action('wp_ajax_wpc_regen_fields', array($this, 'regen_fields'));

        parent::__construct();
    }

    function admin_init() {
/*        wp_enqueue_style('jquery.ui.css-theme',
            plugins_url('vendor/jquery-ui/smoothness.css', __FILE__),
            array(),  '1.8.16');
        wp_enqueue_script('jquery.ui.minimal',
            plugins_url('/vendor/jquery-ui/jquery.ui.minimal.js', __FILE__),
            array('jquery'), '1.8.16');*/
 /*       wp_enqueue_script('jquery-ui-progressbar');
/*            plugins_url('vendor/jquery-ui/jquery.ui.progressbar.js', __FILE__),
            array('jquery','jquery.ui.minimal'), '1.8.16');
            */
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
    ?>
        <div class='wrap'>
            <div id='icon-$icon' class='icon32'><br /></div>
            <h2>CCK Settings</h2>

            <h3>Regenerate generated fields</h3>
            <hgroup>
                <select id='wpc_regen_content_type'>
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
        </div>
        <script type="text/javascript">
            var wpc_regen_nonce = "<?php echo wp_create_nonce('wpc_regen') ?>";
        </script>
            <?php
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

        if (! empty($_POST) || check_admin_referer('wpc_nonce', 'nonce')) {
            $ret['new_nonce'] = wp_create_nonce('wpc_nonce');
            $args = (object) wp_parse_args($_POST, $defaults);
            $type = $args->type;
            $limit = $args->limit;
            $last_id = $args->last_id;

            if ($collection = $this->collection_for_slug($type)) {
                $record = $collection
                    ->filter('post_status', 'publish')
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
