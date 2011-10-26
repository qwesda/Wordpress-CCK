<?php

function _debug_var (&$var){
    if (WP_DEBUG === true){
        if (is_array($var) || is_object($var)){
            error_log(print_r($var, true));
        } else {
            error_log($var);
        }
    }
}

function _die () {
    die (
        array_rand ( array(
            'Eris is pleased to see your efford to cause discord!',
            'Only a Pope is allowed to do this!',
            'Don\'t tell anybody about the frogs!',
            'Out of order!',
            'No Exit!',
            'Hail Eris!\nAll Hail Discordia!',
            'Καλλιστι!',
            'ALL RITES REVERSED Ⓚ REPRINT WHAT YOU LIKE!'
        ) )
    );
}

function _log ($var){
    if (WP_DEBUG === true)
    	_debug_var($var);
}

function _ping ($amount = 1){
	if (WP_DEBUG === false)
		return;

    $backtrace      = debug_backtrace();
    $backtrace_size = sizeof($backtrace);
    $i_end          = $amount == 0 ? $backtrace_size : min($backtrace_size, $amount + 1);

    _log("");

    for ($i = 1; $i < $i_end; $i++) { 
        $parent_scope   = (object)$backtrace[$i];

        $parent_scope->class    = isset($parent_scope->class)       ? $parent_scope->class      : '';
        $parent_scope->function = isset($parent_scope->function)    ? $parent_scope->function   : '';
        $parent_scope->line     = isset($parent_scope->line)        ? $parent_scope->line       : '';
        $parent_scope->file     = isset($parent_scope->file)        ? $parent_scope->file       : '';

        _log("$parent_scope->class::$parent_scope->function ($parent_scope->line -> $parent_scope->file)");
    }
}

function _var_dump (&$var) {
    echo "<pre>";
    var_dump($var);
    echo "</pre><br/>";
}

function _compile($file) {
    ob_start();

    require $file;

    return ob_get_clean();
}

function normalizeString ($string) {
    $string = trim($string);
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    $string = preg_replace('/[^A-Za-z0-9 -]+/', "", $string);
    $string = preg_replace('/[^\w-]+/', "-",    $string);
    $string = strToLower($string);

    return $string;
}

function startsWith ($haystack, $needle) {
    $length = strlen($needle);

    return (substr($haystack, 0, $length) === $needle);
}

function contains ($haystack, $needle) {
    return strstr(haystack, $needle);
}

function endsWith ($haystack, $needle) {
    $length = strlen($needle);
    $start  = $length * -1;

    return (substr($haystack, $start) === $needle);
}

function loadScriptsInPathWithIDPrefix ($path, $id_prefix) {    
    foreach (glob(WP_PLUGIN_DIR . "/Wordpress-CCK/$path/*.js") as $filename) {
        $js_name = preg_replace("/\/?[^\/]+\/|\.js/", "", $filename);

        $js_name_dependecies    = explode(".", $js_name);
        array_pop($js_name_dependecies);
        
        wp_enqueue_script("$id_prefix-$js_name", plugins_url("Wordpress-CCK/$path/$js_name.js" ) );
    }
}

function loadStylesInPathWithIDPrefix ($path, $id_prefix) {
    foreach (glob(WP_PLUGIN_DIR . "/Wordpress-CCK/$path/*.css") as $filename) {
        $css_name = preg_replace("/\/?[^\/]+\/|\.css/", "", $filename);
    
        wp_enqueue_style("$id_prefix-$css_name", plugins_url("Wordpress-CCK/$path/$css_name.css" ) );
    }
}

?>
