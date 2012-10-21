<?php

/**
 *
 */

class DateField extends GenericField {
    function __construct ($parent, $params) {
        parent::__construct ($parent, $params);
    }

    /**
     * the non-js-version is not localized at all. it would need a save-hook to convert the localized date back.
     * the js-version is partly localized. it needs a date_format in js.
     */
    function echo_field_core () {
        $record = the_record();
        $value = $this->parent->id == $record->post_type ? $record->get($this->id) : "";

        ?><input type="text" name="<?php echo "wpc_$this->id" ?>" class="wpc_input wpc_input_date hide-if-js"
            placeholder="<?php echo $this->hint;?>"
            id="<?php echo "wpc_field_$this->id" ?>" value="<?php
                  if ( $value == "NOW" ) echo date("Y-m-d");
                  else echo $value;
                ?>"; />
        <label class="wpc_hint hide-if-js" for="<?php echo "wpc_$this->id" ?>"><?php echo $this->hint ?></label>
        <span class='wpc_input_date_date hide-if-no-js'>
            <a class='wpc_input_date_edit_link' href='#'><span id="wpc_input_date_timestamp-<?php echo $this->id;?>">
                <?php if ($value !== '') echo date_i18n(__('Y-m-d'), mysql2date('U', $value, false)); //date_i18n(__('M, j Y'), mysql2date('U', $value, false));
                      else echo _e('Set Date'); ?> </span></a>
        </span>
        <div class='wpc_input_date_edit_container hidden'>
            <?php
            $m = $d = $y = '';
            if ($value !== '') {
                $m = mysql2date('m', $value, false);
                $d = mysql2date('j', $value, false);
                $y = mysql2date('Y', $value, false);
            }
              $month = "<select id='wpc_input_date_m-$this->id' name='wpc_date_m-$this->id'>\n";
              for ($i=1; $i<=12; $i+=1) {
                  $month.= "<option value='$i'";
                  if ($i == $m)
                    $month .= " selected='selected'";
                  $month.= ">".__(date('M', mktime(0, 0, 0, $i, 1, 2000)))."</option>\n";
              }
              $month.= '</select>';
            $day   = "<input id='wpc_input_date_d-$this->id' name='wpc_date_d-$this->id' type='text' size=2 maxlength=2 value='$d' placeholder='".__('dd')."'/>";
            $year  = "<input id='wpc_input_date_y-$this->id' name='wpc_date_y-$this->id' type='text' size=4 maxlength=4 value='$y' placeholder='".__('yyyy')."'/>";
            printf(__(' %2$s %1$s %3$s'), $month, $day, $year);
            ?> <a id='wpc_input_date_edit_ok-<?php echo $this->id;?>' href='#' class='wpc_input_date_edit_ok'><?php _e('OK')?></a>
            <a id='wpc_input_date_edit_cancel-<?php echo $this->id;?>' href='#' class='wpc_input_date_edit_cancel'><?php _e('cancel')?></a>
        </div>
    <?php }
}

?>
