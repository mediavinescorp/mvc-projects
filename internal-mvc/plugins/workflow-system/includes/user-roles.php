<?php
/**
 * User Roles
 * Creates custom user roles for the workflow system
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WFS_User_Roles {
    
    /**
     * Create custom user roles
     */
    public static function create_roles() {
        self::create_team_member_role();
        self::create_supervisor_role();
        self::create_workflow_admin_role();
    }
    
    /**
     * Remove custom user roles
     */
    public static function remove_roles() {
        remove_role('wfs_team_member');
        remove_role('wfs_supervisor');
        remove_role('wfs_admin');
    }
    
    /**
     * Create Team Member role
     */
    private static function create_team_member_role() {
        $capabilities = array(
            'read'                      => true,
            
            // Task capabilities - can only edit their own
            'read_wfs_task'             => true,
            'edit_wfs_task'             => true,
            'edit_wfs_tasks'            => true,
            'edit_published_wfs_tasks'  => true,
            
            // Workflow capabilities - read only
            'read_wfs_workflow'         => true,
            
            // Client capabilities - read only
            'read_wfs_client'           => true,
            
            // Dashboard access
            'access_wfs_dashboard'      => true,
        );
        
        add_role('wfs_team_member', 'Team Member', $capabilities);
    }
    
    /**
     * Create Supervisor role
     */
    private static function create_supervisor_role() {
        $capabilities = array(
            'read'                          => true,
            
            // Task capabilities - full access
            'read_wfs_task'                 => true,
            'edit_wfs_task'                 => true,
            'edit_wfs_tasks'                => true,
            'edit_others_wfs_tasks'         => true,
            'edit_published_wfs_tasks'      => true,
            'publish_wfs_tasks'             => true,
            'delete_wfs_task'               => true,
            'delete_wfs_tasks'              => true,
            'delete_published_wfs_tasks'    => true,
            
            // Workflow capabilities - full access
            'read_wfs_workflow'             => true,
            'edit_wfs_workflow'             => true,
            'edit_wfs_workflows'            => true,
            'edit_others_wfs_workflows'     => true,
            'edit_published_wfs_workflows'  => true,
            'publish_wfs_workflows'         => true,
            'delete_wfs_workflow'           => true,
            'delete_wfs_workflows'          => true,
            'delete_published_wfs_workflows' => true,
            
            // Client capabilities - full access
            'read_wfs_client'               => true,
            'edit_wfs_client'               => true,
            'edit_wfs_clients'              => true,
            'edit_others_wfs_clients'       => true,
            'edit_published_wfs_clients'    => true,
            'publish_wfs_clients'           => true,
            'delete_wfs_client'             => true,
            'delete_wfs_clients'            => true,
            'delete_published_wfs_clients'  => true,
            
            // Dashboard access
            'access_wfs_dashboard'          => true,
            'access_wfs_supervisor_dashboard' => true,
            
            // Special permissions
            'approve_wfs_tasks'             => true,
            'close_wfs_workflows'           => true,
            'assign_wfs_tasks'              => true,
        );
        
        add_role('wfs_supervisor', 'Supervisor', $capabilities);
    }
    
    /**
     * Create Workflow Admin role
     */
    private static function create_workflow_admin_role() {
        // Get all supervisor capabilities
        $supervisor_role = get_role('wfs_supervisor');
        $capabilities = $supervisor_role ? $supervisor_role->capabilities : array();
        
        // Add admin-specific capabilities
        $admin_capabilities = array(
            'manage_wfs_settings'           => true,
            'manage_wfs_templates'          => true,
            'manage_wfs_users'              => true,
            'view_wfs_reports'              => true,
            'export_wfs_data'               => true,
        );
        
        $capabilities = array_merge($capabilities, $admin_capabilities);
        
        add_role('wfs_admin', 'Workflow Admin', $capabilities);
    }
    
    /**
     * Add workflow capabilities to WordPress Administrator
     */
    public static function add_caps_to_admin() {
        $admin = get_role('administrator');
        
        if ($admin) {
            // Get all workflow admin capabilities
            $wfs_admin = get_role('wfs_admin');
            
            if ($wfs_admin) {
                foreach ($wfs_admin->capabilities as $cap => $granted) {
                    $admin->add_cap($cap);
                }
            }
        }
    }
}
