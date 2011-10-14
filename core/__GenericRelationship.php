<?php


global $wpc_relationships;
$wpc_relationships = array();

class __GenericRelationship {
	public $id					= "";
	public $fields				= NULL;

	public $label				= "";

	public $post_type_from_id	= "";
	public $post_type_from		= NULL;
	public $post_type_to_id		= "";
	public $post_type_to		= NULL;

	function __construct () {
		global $wpc_relationships;
		global $wpc_content_types;

//	SET DEFAULTS
        if (empty($this->id))		$this->id 		= strtolower( get_class($this) );

		if (empty($this->label))	$this->label 	= $this->id;

		if ( !in_array( $this->post_type_from_id, get_post_types() ) ) {
			die ("in wpc relation \"$this->id\" is post_type_from not a valid wpc content_type\npost_type_from_id == \"$this->post_type_from_id\"");

			return ;
		} else {
			$this->post_type_from		= get_post_type_object($this->post_type_from_id);
		}

		if ( !in_array($this->post_type_to_id, get_post_types()) ) {
			die ("in wpc relation \"$this->id\" is post_type_to not a valid wpc content_type\npost_type_from_id == \"$this->post_type_to_id\"");

			return ;
		} else {
			$this->post_type_to			= get_post_type_object($this->post_type_to_id);
		}


		if (isset($wpc_ids[$this->id]) ) {
			die ("wpc relation \"$this->id\" is not unique");

			return ;
		} else {
			$wpc_relationships[$this->id] = $this;
		}

	}

	function echo_metabox ($post, $id_source, $id_dest, $direction) {
		$post_type_source	= get_post_type_object($id_source);
		$post_type_dest		= get_post_type_object($id_dest);

		$available_items 	= __GenericRelationship::get_post_type_items_for_relation((object)array("post_type" => $id_source));

?><h4 class="relation_heading"><?php echo "Available ".$post_type_source->labels->name ?></h4>
<ul id="<?php echo "source_$this->id" ?>" class="relation_source_list">
	<?php foreach ($available_items->results as $available_item_post): ?>
		<li data-post_id="<?php echo $available_item_post->ID ?>"><a href="#" class="<?php echo "$this->id source_item" ?>"><?php echo $available_item_post->post_title ?></a></li>
	<?php endforeach ?>
</ul>
<script type="text/javascript">
    jQuery('ul#<?php echo "source_$this->id" ?>').delegate('a.<?php echo "$this->id.source_item" ?>', 'click',
    function(event) {
    	event.preventDefault();

		var source_link 		= jQuery(this);
		var source_list_item	= source_link.parent();
		var source_list			= source_list_item.parent();

		jQuery('#<?php echo "add_box_$this->id" ?>').remove();

		source_list.after(
			'<div id="<?php echo "add_box_$this->id" ?>" class="relation_item_add_box">' +
				'<label for="<?php echo "add_$this->id" ?>">add <b>' + this.innerText + '</b></label>' +
				'<a id="<?php echo "add_link_$this->id" ?>" data-post_id="' + source_list_item.data('post_id') + '" class="button button_right" href="#">add</a>' +
			'</div>'
		);
    });

    jQuery('body').delegate('a#<?php echo "add_link_$this->id" ?>', 'click',
    function(event) {
    	event.preventDefault();

		var source_link 		= jQuery(this);

		jQuery.ajax({
			url: ajaxurl,
			context: source_link,
			data : {
				action  : "add_relation",
				nonce   : "<?php echo wp_create_nonce($this->id); ?>",
				rel_id  : "<?php echo $this->id; ?>",
				from_id : <?php echo $direction == "source_is_from" ? 'source_link.data("post_id")' : "'$post->ID'" ?>,
				to_id   : <?php echo $direction == "source_is_to"   ? 'source_link.data("post_id")' : "'$post->ID'" ?>,
			},
			success: function (data, textStatus, jqXHR) {
				console.log(data);
				console.log(textStatus);

				jQuery('#<?php echo "add_box_$this->id" ?>').remove();
			}
		});
    });
</script>
<?php
	}

	function echo_metabox_from ($post, $args) {
		$this->echo_metabox($post, $this->post_type_to_id, $this->post_type_from_id, "source_is_to");
	}

	function echo_metabox_to ($post, $relationship) {
		$this->echo_metabox($post, $this->post_type_from_id, $this->post_type_to_id, "source_is_from");
	}



	static function hookup_ajax_functions () {
		add_action('wp_ajax_get_post_type_items_for_relation',	array('__GenericRelationship', 'get_post_type_items_for_relation_ajax'));
		add_action('wp_ajax_add_relation', 						array('__GenericRelationship', 'add_relation_ajax'));
	}

	static function add_relation_ajax () {
		header('Content-type: text/javascript');

		_ping();

		if( empty($_REQUEST['rel_id']) || !wp_verify_nonce($_REQUEST['nonce'], $_REQUEST['rel_id']) ) {
			_log("wp_verify_nonce('".$_REQUEST['nonce']."', '".$_REQUEST['rel_id']."') failed");
			_die();
		} else {
			_log("wp_verify_nonce('".$_REQUEST['nonce']."', '".$_REQUEST['rel_id']."') succeded");
		}

		$req = (object)$_REQUEST;

		$from_id = absint($req->from_id);
		$to_id   = absint($req->to_id);


		$ret = __GenericRelationship::add_relation($from_id, $to_id);

		echo json_encode($ret);

		die();
	}

	static function add_relation ($from_id, $to_id) {
		global $wpdb;
		global $wpc_relationships;
		global $wpc_content_types;

		$ret = (object)array(
			 "errors" => array (),
			 "status" => array (),
			"results" => array (),
		);

		if ($from_id == 0)	$ret->errors[] = "from_id has invalid value '$from_id'";
		if ($to_id == 0)	$ret->errors[] = "to_id has invalid value '$to_id'";



		return $ret;
	}

	static function get_post_type_items_for_relation ($req) {
		global $wpdb;
		global $wpc_relationships;
		global $wpc_content_types;

		$ret = (object)array(
			 "errors" => array (),
			 "status" => array (),
			"results" => array (),
		);

		if ( !empty( $req->post_type ) ) {
			if ( in_array($req->post_type, get_post_types () ) ) {
				$prepared_sql_limit		= "";
				$prepared_sql_order		= "";
				$prepared_sql_like		= "";

				$prepared_sql_filter	= $wpdb->prepare(
					"  FROM $wpdb->posts \n".
					" WHERE $wpdb->posts.post_type 	= %s \n".
					"   AND $wpdb->posts.post_status	= 'publish'".
					"", $req->post_type
				);

				if ( !empty($req->filter) ){
					$prepared_sql_like  = $wpdb->prepare("   AND $wpdb->posts.post_title LIKE '%%%s%%' ", $req->filter);
				}


				if ( !isset($req->limit) )
					$req->limit = 100;

				if ( !isset($req->offset) )
					$req->offset = 0;

				if ( absint($req->limit) > 0 ) {
					$prepared_sql_limit  = $wpdb->prepare(" LIMIT %d OFFSET %d", absint($req->limit), absint($req->offset));
				}


				if ( !isset($req->order_by) )
					$req->order_by = "NULL";

				if ( ( isset($req->order_by) && in_array ($req->order_by, array ("id",  "title", "NULL")) )
				  && ( empty($req->order)    || in_array ($req->order,    array ("asc", "desc")) ) ){
				  	$req->order_by 	= str_replace(array("id",  "title", "NULL"), array("$wpdb->posts.ID",  "$wpdb->posts.post_title", "NULL"), $req->order_by);
				  	$req->order   	= ( isset($req->order) && $req->order == "desc" ) ? "DESC" : "ASC";

					$prepared_sql_order  = $wpdb->prepare("ORDER BY $req->order_by $req->order ");
				}

				$available_count	= $wpdb->get_var 	( "SELECT COUNT(*) $prepared_sql_filter $prepared_sql_like" );
				$results			= $wpdb->get_results( "SELECT $wpdb->posts.ID,  $wpdb->posts.post_title
$prepared_sql_filter
$prepared_sql_like
$prepared_sql_order
$prepared_sql_limit" );

/*				_log("SELECT  $wpdb->posts.ID,  $wpdb->posts.post_title
$prepared_sql_filter
$prepared_sql_like
$prepared_sql_order
$prepared_sql_limit");
*/
				$ret->status['available_results']	= $available_count;
				$ret->status['returned_results']	= count($results);

				$ret->results		= $results;
			} else {
				$ret->errors[]		= "Specified post_type '$req->post_type' is invalid.\nRegistered post_types are: " . implode(", ", array_keys( $valid_posttypes ) );
			}
		} else {
			$ret->errors[] = "No post_type was specified";
		}

		return $ret;
	}
	static function get_post_type_items_for_relation_ajax () {
		header('Content-type: text/javascript');

		$req = (object)$_REQUEST;
		$ret = __GenericRelationship::get_post_type_items_for_relation($req);

		echo json_encode($ret);

		die();
	}
}

?>
