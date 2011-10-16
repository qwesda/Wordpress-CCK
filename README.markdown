A Wordpress Plugin for Custom Post Types
========================================

About the Name
--------------

The name (and only the name) is derived from Drupal's Content Construction Kit (CCK).
The prefix `wpc` is used throughout the code to shorten *WordPress CCK*.


Using the plugin
----------------

Use the global `$wpc` object to do anything with the plugin.


Registering your Custom Types
-----------------------------

The following is a proposed API.

The following methods should only be used inside a `wpc_register` action during plugin (un)install (i.e. once) and in a `wpc_reregister` action in case WordPress CCK gets updated.

* `$wpc->register_type($filename)` registers a class to be found in the file. See below on how to write these.
It uses the `wpc_internal_register_type` action.

* `$wpc->register_types_in_dir($dir)` registers all files in a specific directory.

* `$wpc->register_relation($filename)` registers a relationships to be found in the file.  See below.

* `$wpc->register_relations_dir($dir)` registers all files in a specific directory.

* `$wpc->unregister_type($filename)` deletes the registered type. It does not fix any posts post-type.

* `wpc->unregister_types_in_dir($dir)` deletes all registered types in the specified directory.

* `$wpc->unregister_relation($filename)` deletes the registered relationships. It does not fix any posts post-type.

* `wpc->unregister_relations_in_dir($dir)` deletes all registered relationships in the specified directory.


Reference
---------

### List of hooks

* `wpc_register` action gets called in the first phase of WordPress CCK's init.
You are strongly advised, to only use it in your plugin's `register_activation_hook`.

* `wpc_reregister`, this gets called, when WordPress CCK decides it's time to reload all registered types and relations, i.e. when WordPress CCK is updated.
