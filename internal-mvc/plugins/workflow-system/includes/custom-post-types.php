<?php
/**
 * Custom Post Types
 * Registers Clients, Workflows, and Tasks
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_Custom_Post_Types {
    
    /**
     * Register all custom post types
     */
    public static function register() {
        self::register_clients();
        self::register_workflows();
        self::register_tasks();
    }
    
    /**
     * Register Clients post type
     */
    private static function register_clients() {
        $labels = array(
            'name'                  => 'Clients',
            'singular_name'         => 'Client',
            'menu_name'             => 'Clients',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Client',
            'edit_item'             => 'Edit Client',
            'new_item'              => 'New Client',
            'view_item'             => 'View Client',
            'search_items'          => 'Search Clients',
            'not_found'             => 'No clients found',
            'not_found_in_trash'    => 'No clients found in trash',
            'all_items'             => 'All Clients',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-groups',
            'capability_type'       => 'post',
            'hierarchical'          => false,
            'supports'              => array('title', 'editor'),
            'has_archive'           => false,
            'rewrite'               => false,
            'show_in_rest'          => true,
        );
        
        register_post_type('wfs_client', $args);
    }
    
    /**
     * Register Workflows post type
     */
    private static function register_workflows() {
        $labels = array(
            'name'                  => 'Workflows',
            'singular_name'         => 'Workflow',
            'menu_name'             => 'Workflows',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Workflow',
            'edit_item'             => 'Edit Workflow',
            'new_item'              => 'New Workflow',
            'view_item'             => 'View Workflow',
            'search_items'          => 'Search Workflows',
            'not_found'             => 'No workflows found',
            'not_found_in_trash'    => 'No workflows found in trash',
            'all_items'             => 'All Workflows',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 21,
            'menu_icon'             => 'dashicons-networking',
            'capability_type'       => 'post',
            'hierarchical'          => false,
            'supports'              => array('title', 'editor'),
            'has_archive'           => false,
            'rewrite'               => false,
            'show_in_rest'          => true,
        );
        
        register_post_type('wfs_workflow', $args);
    }
    
    /**
     * Register Tasks post type
     */
    private static function register_tasks() {
        $labels = array(
            'name'                  => 'Tasks',
            'singular_name'         => 'Task',
            'menu_name'             => 'Tasks',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Task',
            'edit_item'             => 'Edit Task',
            'new_item'              => 'New Task',
            'view_item'             => 'View Task',
            'search_items'          => 'Search Tasks',
            'not_found'             => 'No tasks found',
            'not_found_in_trash'    => 'No tasks found in trash',
            'all_items'             => 'All Tasks',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 22,
            'menu_icon'             => 'dashicons-list-view',
            'capability_type'       => 'post',
            'hierarchical'          => false,
            'supports'              => array('title', 'editor'),
            'has_archive'           => false,
            'rewrite'               => false,
            'show_in_rest'          => true,
        );
        
        register_post_type('wfs_task', $args);
    }
}
