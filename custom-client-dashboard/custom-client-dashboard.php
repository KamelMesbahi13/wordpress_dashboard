<?php
/**
 * Plugin Name: Custom Client Dashboard
 * Description: A modern, custom dashboard interface for the client.
 * Version: 2.0.1
 * Author: KML
 */


function remove_default_dashboard_widgets() {
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
}
add_action('wp_dashboard_setup', 'remove_default_dashboard_widgets');

function add_custom_dashboard_widgets() {
    wp_add_dashboard_widget('custom_emploi_widget', 'Statistiques Candidats', 'custom_emploi_widget_display');
    wp_add_dashboard_widget('custom_devis_widget', 'Statistiques Devis', 'custom_devis_widget_display');
}
add_action('wp_dashboard_setup', 'add_custom_dashboard_widgets');



/**
 * Display function for Emploi (Candidates) Widget
 */
function custom_emploi_widget_display() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cv_submissions';
    
    // Default values
    $counts = array('total' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0);
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        $counts['total'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $counts['pending'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $counts['accepted'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'accepted'");
        $counts['rejected'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'");
    }
    
    custom_render_status_chart($counts, 'Candidats');
}

/**
 * Display function for Devis Widget
 */
function custom_devis_widget_display() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'devis_submissions';
    
    // Default values
    $counts = array('total' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0);
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        $counts['total'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $counts['pending'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $counts['accepted'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'accepted'");
        $counts['rejected'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'");
    }
    
    custom_render_status_chart($counts, 'Devis');
}

/**
 * Helper to render the status chart
 */
function custom_render_status_chart($counts, $label) {
    $total = $counts['total'];
    $pending = $counts['pending'];
    $accepted = $counts['accepted'];
    $rejected = $counts['rejected'];
    
    $p_pending = $total > 0 ? ($pending / $total) : 0;
    $p_accepted = $total > 0 ? ($accepted / $total) : 0;
    $p_rejected = $total > 0 ? ($rejected / $total) : 0;
    
    $r = 40;
    $circ = 2 * 3.14159 * $r; // ~251.3
    
    $s_accepted = $p_accepted * $circ;
    $s_pending = $p_pending * $circ;
    $s_rejected = $p_rejected * $circ;
    
    // Colors
    $c_accepted = '#10b981'; // Green
    $c_pending = '#f59e0b';  // Amber
    $c_rejected = '#ef4444'; // Red
    ?>
    <div class="modern-status-widget" style="display: flex; align-items: center; justify-content: space-around; padding: 10px 0;">
        <div class="chart-container" style="position: relative; width: 120px; height: 120px;">
             <!-- Rotated -90deg -->
             <svg width="120" height="120" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                <!-- Background -->
                <circle cx="50" cy="50" r="40" stroke="#1e293b" stroke-width="12" fill="none"/>
                
                <?php if ($total > 0): ?>
                    <!-- Accepted Segment (Starts at 0) -->
                    <circle cx="50" cy="50" r="40" stroke="<?php echo $c_accepted; ?>" stroke-width="12" fill="none"
                            stroke-dasharray="<?php echo $s_accepted; ?> <?php echo $circ; ?>"
                            stroke-dashoffset="0" />
                            
                    <!-- Pending Segment (Starts after Accepted) -->
                    <circle cx="50" cy="50" r="40" stroke="<?php echo $c_pending; ?>" stroke-width="12" fill="none"
                            stroke-dasharray="<?php echo $s_pending; ?> <?php echo $circ; ?>"
                            stroke-dashoffset="<?php echo -$s_accepted; ?>" />
                            
                    <!-- Rejected Segment (Starts after Pending) -->
                    <circle cx="50" cy="50" r="40" stroke="<?php echo $c_rejected; ?>" stroke-width="12" fill="none"
                            stroke-dasharray="<?php echo $s_rejected; ?> <?php echo $circ; ?>"
                            stroke-dashoffset="<?php echo -($s_accepted + $s_pending); ?>" />
                <?php endif; ?>
            </svg>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                <span style="font-size: 20px; font-weight: bold; color: #f8fafc; display: block; line-height: 1;"><?php echo $total; ?></span>
                <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $label; ?></span>
            </div>
        </div>
        
        <div class="stats-legend">
            <?php 
            $items = [
                ['label' => 'Accepté', 'count' => $accepted, 'color' => $c_accepted],
                ['label' => 'En attente', 'count' => $pending, 'color' => $c_pending],
                ['label' => 'Rejeté', 'count' => $rejected, 'color' => $c_rejected],
            ];
            foreach ($items as $item): ?>
            <div style="margin-bottom: 8px;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 13px;">
                    <span style="width: 10px; height: 10px; background: <?php echo $item['color']; ?>; border-radius: 50%; display: inline-block;"></span>
                    <div style="display: flex; justify-content: space-between; width: 100px;">
                        <span style="color: #cbd5e1; font-size: 11px;"><?php echo $item['label']; ?></span>
                        <span style="font-weight: 600; color: #f8fafc; font-size: 12px;"><?php echo $item['count']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}



function add_custom_admin_user_section() {
    $current_user = wp_get_current_user();
    $avatar_url = get_avatar_url($current_user->ID);
    $user_role = !empty($current_user->roles) ? ucfirst($current_user->roles[0]) : 'User';
    ?>
    <div id="custom-sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-image">
                <img src="https://bet-ces-cet.com/wp-content/uploads/2025/10/2-removebg-preview-e1759501498378.png" alt="Logo" style="width: auto; height: 50px; max-width: 100%;">
            </div>
            <div class="logo-text">
                <span class="logo-name" style="font-size: 15px; line-height: 1.2;">Bet ces cet<br>zerrouki</span>
                <span class="logo-version">v2.0</span>
            </div>
        </div>
        <div class="sidebar-user">
            <a href="<?php echo esc_url(get_edit_profile_url($current_user->ID)); ?>" class="sidebar-user-link">
                <div class="user-avatar">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                    <span class="user-status"></span>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                    <span class="user-role"><?php echo esc_html($user_role); ?></span>
                </div>
            </a>
            <div class="user-menu-toggle">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"/>
                    <circle cx="12" cy="5" r="1"/>
                    <circle cx="12" cy="19" r="1"/>
                </svg>
            </div>
            <div class="user-dropdown">
                <a href="<?php echo wp_logout_url(); ?>" class="user-dropdown-item logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Déconnexion
                </a>
            </div>
        </div>
        <div class="sidebar-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" placeholder="Rechercher..." id="admin-search">
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            // Move custom header before admin menu
            if ($('#custom-sidebar-header').length && $('#adminmenu').length) {
                $('#adminmenu').before($('#custom-sidebar-header'));
            }
            
            $('#admin-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('#adminmenu > li').each(function() {
                    var menuText = $(this).text().toLowerCase();
                    if (menuText.indexOf(searchTerm) > -1 || searchTerm === '') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // User dropdown toggle
            $('.user-menu-toggle').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('.user-dropdown').toggleClass('show');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.user-menu-toggle').length && !$(e.target).closest('.user-dropdown').length) {
                    $('.user-dropdown').removeClass('show');
                }
            });
            

            
            function reorganizeDashboardWidgets() {
                var $wrap = $('#dashboard-widgets-wrap');
                if ($wrap.length === 0) return;
                
                if ($('#dashboard-grid-container').length > 0) return;
                

                var widgetOrder = [
                    'custom_emploi_widget',
                    'custom_devis_widget'
                ];
                
                var $allWidgets = $wrap.find('.postbox');
                var widgetsMap = {};
                
                $allWidgets.each(function() {
                    var id = $(this).attr('id');
                    widgetsMap[id] = $(this);
                });
                
                var $gridContainer = $('<div id="dashboard-grid-container"></div>');
                
                widgetOrder.forEach(function(widgetId) {
                    if (widgetsMap[widgetId]) {
                        var $widget = widgetsMap[widgetId].detach();
                        $widget.addClass('grid-widget');
                        ensureNavigationButtons($widget);
                        $gridContainer.append($widget);
                        delete widgetsMap[widgetId];
                    }
                });
     
                
                $wrap.find('#dashboard-widgets').hide();
                $wrap.append($gridContainer);
                
                initNavigationButtons();
                

            }
            
            function ensureNavigationButtons($widget) {
                var $handle = $widget.find('.hndle, h2.hndle').first();
                
                $widget.find('.widget-nav-buttons').remove();
                
                var $navButtons = $('<div class="widget-nav-buttons">' +
                    '<button type="button" class="widget-move-up" title="Déplacer vers le haut">' +
                    '<span class="dashicons dashicons-arrow-up-alt2"></span>' +
                    '</button>' +
                    '<button type="button" class="widget-move-down" title="Déplacer vers le bas">' +
                    '<span class="dashicons dashicons-arrow-down-alt2"></span>' +
                    '</button>' +
                    '</div>');
                
                $handle.append($navButtons);
            }
            
            function initNavigationButtons() {
                var $container = $('#dashboard-grid-container');
                
                $container.off('click', '.widget-move-up').on('click', '.widget-move-up', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var $widget = $(this).closest('.postbox');
                    var $prev = $widget.prev('.postbox');
                    
                    if ($prev.length) {
                        $widget.css('opacity', '0.5');
                        $prev.before($widget);
                        
                        setTimeout(function() {
                            $widget.css('opacity', '1');
                            highlightWidget($widget);

                        }, 100);
                        
                        saveWidgetOrder();
                    }
                });
                
                $container.off('click', '.widget-move-down').on('click', '.widget-move-down', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var $widget = $(this).closest('.postbox');
                    var $next = $widget.next('.postbox');
                    
                    if ($next.length) {
                        $widget.css('opacity', '0.5');
                        $next.after($widget);
                        
                        setTimeout(function() {
                            $widget.css('opacity', '1');
                            highlightWidget($widget);
                            if ($widget.attr('id') === 'custom_growth_widget') {
                                initGrowthChart();
                            }
                        }, 100);
                        
                        saveWidgetOrder();
                    }
                });
            }
            
            function highlightWidget($widget) {
                $widget.addClass('widget-moved');
                setTimeout(function() {
                    $widget.removeClass('widget-moved');
                }, 500);
            }
            
            function saveWidgetOrder() {
                var order = [];
                $('#dashboard-grid-container .postbox').each(function() {
                    order.push($(this).attr('id'));
                });
                localStorage.setItem('dashboard_widget_order', JSON.stringify(order));
            }
            
            function loadWidgetOrder() {
                var savedOrder = localStorage.getItem('dashboard_widget_order');
                if (savedOrder) {
                    try {
                        return JSON.parse(savedOrder);
                    } catch(e) {
                        return null;
                    }
                }
                return null;
            }
            
            setTimeout(function() {
                reorganizeDashboardWidgets();
                
                var savedOrder = loadWidgetOrder();
                if (savedOrder && savedOrder.length > 0) {
                    var $container = $('#dashboard-grid-container');
                    savedOrder.forEach(function(widgetId) {
                        var $widget = $container.find('#' + widgetId);
                        if ($widget.length) {
                            $container.append($widget);
                        }
                    });
                    

                }
            }, 150);
        });
    </script>
    <?php
}
add_action('admin_footer', 'add_custom_admin_user_section');

function custom_admin_dashboard_styles() {
    $screen = get_current_screen();
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
	
    <?php if ($screen && $screen->id === 'dashboard'): ?>
	body.wp-admin,
    html.wp-admin,
    #wpcontent,
    #wpbody,
    #wpbody-content {
        background: #0f172a !important;
    }
        body.wp-admin {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
        }

        #wpcontent {
            background: #0f172a;
        }

        .wrap {
            background: #1e293b;
            border-radius: 20px;
            padding: 30px;
            margin: 20px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .wrap h1 {
            color: #f8fafc;
        }
    <?php endif; ?>

        #wpcontent {
            margin-left: 200px !important;
        }
        
        @media only screen and (min-width: 961px) {
            body.folded #wpcontent {
                margin-left: 36px !important;
            }
        }

        #adminmenuback,
        #adminmenuwrap {
            background: #0f172a !important;
            width: 200px !important;
        }

        #adminmenu {
            background: #0f172a;
            margin-top: 0 !important;
            width: 200px !important;
        }

        #custom-sidebar-header {
            background: #0f172a;
            padding: 20px 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            position: relative;
            z-index: 1;
        }

        /* Ensure sidebar header appears in mobile collapsed view */
        @media only screen and (max-width: 960px) {
            body:not(.folded) #custom-sidebar-header {
                display: block !important;
            }
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }



        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-name {
            color: #f8fafc;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .logo-version {
            color: #64748b;
            font-size: 11px;
            font-weight: 500;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(148, 163, 184, 0.05);
            border-radius: 12px;
            margin-bottom: 16px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .sidebar-user .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
        }

        .sidebar-user .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-status {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            background: #10b981;
            border: 2px solid #0f172a;
            border-radius: 50%;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .sidebar-user .user-name {
            color: #f8fafc;
            font-size: 13px;
            font-weight: 600;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            color: #64748b;
            font-size: 11px;
            font-weight: 500;
        }

        .user-menu-toggle {
            background: transparent;
            padding: 4px;
            cursor: pointer;
            color: #94a3b8;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .user-menu-toggle:hover {
            background: rgba(248, 250, 252, 0.1);
            color: #f8fafc;
        }

        .sidebar-user {
            position: relative;
        }

        .sidebar-user-link {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            text-decoration: none !important;
            color: inherit !important;
            min-width: 0;
        }

        .user-dropdown {
            position: absolute;
            bottom: 100%; /* Show above */
            right: 0;
            width: 100%;
            background: #1e293b;
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 100;
            margin-bottom: 8px;
        }

        .user-dropdown.show {
            display: block;
            animation: slideUp 0.2s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            color: #f8fafc;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.2s;
        }

        .user-dropdown-item:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }

        .user-dropdown-item.logout {
            color: #ef4444;
        }

        .user-dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .user-menu-toggle:hover {
            background: rgba(148, 163, 184, 0.1);
            color: #f8fafc;
        }

        .sidebar-search {
            position: relative;
            margin-bottom: 16px;
        }

        .sidebar-search svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .sidebar-search input {
            width: 100%;
            background: rgba(148, 163, 184, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 10px;
            padding: 10px 12px 10px 40px;
            color: #f8fafc;
            font-size: 13px;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .sidebar-search input::placeholder {
            color: #64748b;
        }

        .sidebar-search input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .sidebar-quick-stats {
            display: none;
        }

        #adminmenu li {
            margin: 4px 0 !important;
            padding: 0 !important;
        }

        #adminmenu a {
            color: #94a3b8 !important;
            padding: 10px 14px !important;
            margin: 0 !important;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 10px !important;
            border: none !important;
        }

        #adminmenu li:hover > a,
        #adminmenu li.opensub > a,
        #adminmenu li > a:focus {
            color: #f8fafc !important;
            background: rgba(148, 163, 184, 0.1) !important;
            border-radius: 10px !important;
        }

        #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
        #adminmenu li.current a.menu-top,
        #adminmenu li.wp-has-current-submenu > a.menu-top,
        #adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head {
            color: #f8fafc !important;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2)) !important;
            font-weight: 600;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
            border-radius: 10px !important;
            position: relative;
        }
        
        /* Active menu item indicator arrow */
        #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu::before,
        #adminmenu li.current a.menu-top::before,
        #adminmenu li.wp-has-current-submenu > a.menu-top::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 8px 0 8px 8px;
            border-color: transparent transparent transparent #6366f1;
        }
        
        /* Remove default WordPress arrow */
        #adminmenu .wp-has-current-submenu .wp-menu-arrow,
        #adminmenu .current .wp-menu-arrow {
            display: none;
        }
        
        /* Active submenu items */
        #adminmenu .wp-submenu li.current a,
        #adminmenu .wp-submenu li a.current,
        #adminmenu .wp-has-current-submenu .wp-submenu li a {
            color: #94a3b8 !important;
            background: transparent !important;
        }
        
        #adminmenu .wp-submenu li.current a,
        #adminmenu .wp-submenu li a.current {
            color: #6366f1 !important;
            font-weight: 600;
            background: rgba(99, 102, 241, 0.1) !important;
        }

        #adminmenu .wp-menu-image,
        #adminmenu .wp-menu-image:before {
            color: #64748b !important;
            transition: all 0.2s ease;
        }

        #adminmenu li:hover .wp-menu-image,
        #adminmenu li:hover .wp-menu-image:before,
        #adminmenu li.wp-has-current-submenu .wp-menu-image,
        #adminmenu li.wp-has-current-submenu .wp-menu-image:before,
        #adminmenu li.current .wp-menu-image,
        #adminmenu li.current .wp-menu-image:before {
            color: #6366f1 !important;
        }

#adminmenu .wp-submenu {
 background: rgba(15, 23, 42, 0.95) !important;
    border: 1px solid rgba(148, 163, 184, 0.1) !important;
    border-radius: 12px !important;
    padding: 8px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
    display: none !important;
    position: absolute !important;
    left: 200px !important; 
    top: 0 !important;
    z-index: 99999 !important;
}
										
#adminmenu li:hover > .wp-submenu,
#adminmenu li.opensub > .wp-submenu {
    display: block !important;
}

/* For folded menu */
body.folded #adminmenu .wp-submenu {
    margin-left: 36px !important;
}

        #adminmenu .wp-submenu a {
            color: #94a3b8 !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            font-size: 12px !important;
        }

        #adminmenu .wp-submenu a:hover,
        #adminmenu .wp-submenu a:focus {
            color: #f8fafc !important;
            background: rgba(99, 102, 241, 0.15) !important;
        }

        /* Third-party plugin sections styling */
        #adminmenu .wp-menu-separator,
        #adminmenu li.wp-menu-separator {
            height: 1px;
            margin: 8px 0 !important;
            padding: 0 !important;
            background: rgba(148, 163, 184, 0.1) !important;
            display: block !important;
        }
        
        /* Plugin specific menu items (like XTRA) */
        #adminmenu > li > a.menu-top {
            background: transparent !important;
            position: relative;
        }
        
        #adminmenu > li.menu-top:hover > a,
        #adminmenu > li.opensub > a {
            background: rgba(148, 163, 184, 0.1) !important;
            border-radius: 10px !important;
        }
        
        /* Submenu hover effect with proper border radius */
        #adminmenu .wp-submenu li a:hover,
        #adminmenu .wp-submenu li a:focus {
            border-radius: 8px !important;
        }

        #adminmenu .wp-submenu li.current a,
        #adminmenu .wp-submenu li a.current {
            color: #6366f1 !important;
            font-weight: 600;
        }

        #adminmenu li.wp-menu-separator {
            display: none !important;
        }
        
        /* Override the separator display for better organization */
        #adminmenu .wp-menu-separator {
            height: 1px;
            margin: 8px 0;
            background: rgba(148, 163, 184, 0.1);
        }

        #adminmenu div.wp-menu-image {
            padding: 0 !important;
        }

        #collapse-button {
            color: #64748b !important;
            border-radius: 10px;
            margin: 8px 12px;
        }

        #collapse-button:hover {
            color: #f8fafc !important;
            background: rgba(148, 163, 184, 0.1);
        }

        #adminmenu::-webkit-scrollbar {
            width: 4px;
        }

        #adminmenu::-webkit-scrollbar-track {
            background: transparent;
        }

        #adminmenu::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 2px;
        }

        #adminmenu::-webkit-scrollbar-thumb:hover {
            background: #6366f1;
        }

        #dashboard-widgets-wrap #dashboard-widgets {
            display: none !important;
        }
        
        #dashboard-grid-container {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 24px !important;
            width: 100% !important;
            margin-top: 20px !important;
        }
        
        #dashboard-grid-container .postbox,
        #dashboard-grid-container .postbox.grid-widget {
            background: #1e293b !important;
            border: 1px solid rgba(148, 163, 184, 0.1) !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            margin: 0 !important;
            width: 100% !important;
            float: none !important;
            display: block !important;
            overflow: hidden;
        }

        #dashboard-grid-container .postbox:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3) !important;
            border-color: rgba(99, 102, 241, 0.3) !important;
        }
        
        #dashboard-grid-container .postbox.widget-moved {
            animation: widgetPulse 0.5s ease;
        }
        
        @keyframes widgetPulse {
            0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.6); }
            50% { box-shadow: 0 0 0 12px rgba(99, 102, 241, 0); }
            100% { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); }
        }

        #dashboard-grid-container .postbox .postbox-header {
            border-bottom: none !important;
            margin-bottom: 24px !important;
            background: transparent !important;
        }

        #dashboard-grid-container .postbox .hndle,
        #dashboard-grid-container .postbox h2.hndle {
            background: transparent !important;
            border: none !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #f8fafc !important;
            padding: 20px 24px !important;
            margin-bottom: 0 !important;
            cursor: default !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            letter-spacing: -0.01em;
        }

        #dashboard-grid-container .postbox .inside {
            padding: 0 24px 24px !important;
            margin: 0 !important;
        }
        
        #dashboard-grid-container .postbox .handlediv,
        #dashboard-grid-container .postbox .handle-order-higher,
        #dashboard-grid-container .postbox .handle-order-lower {
            display: none !important;
        }

        .widget-nav-buttons {
            display: flex;
            gap: 6px;
            margin-left: auto;
        }
        
        .widget-nav-buttons button {
            background: rgba(148, 163, 184, 0.1);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 8px;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: #64748b;
        }
        
        .widget-nav-buttons button:hover {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
            transform: scale(1.05);
        }
        
        .widget-nav-buttons button:active {
            transform: scale(0.95);
        }
        
        .widget-nav-buttons .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        #dashboard-grid-container .postbox:first-child .widget-move-up {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        #dashboard-grid-container .postbox:last-child .widget-move-down {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }











        #wpadminbar {
            background: #020617 !important;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        #wpadminbar .ab-item,
        #wpadminbar a.ab-item,
        #wpadminbar > #wp-toolbar span.ab-label,
        #wpadminbar > #wp-toolbar span.noticon {
            color: #94a3b8 !important;
        }

        #wpadminbar .ab-item:hover,
        #wpadminbar a.ab-item:hover {
            background: rgba(99, 102, 241, 0.1) !important;
            color: #f8fafc !important;
        }

        .welcome-panel {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none !important;
            border-radius: 16px;
            color: white;
            padding: 40px;
            box-shadow: 0 4px 30px rgba(99, 102, 241, 0.3);
        }

        .welcome-panel h2 {
            color: white !important;
        }

        .welcome-panel p {
            color: rgba(255, 255, 255, 0.9);
        }

        .welcome-panel .welcome-panel-close {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .button-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 10px 20px !important;
            text-shadow: none !important;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4) !important;
            font-weight: 500 !important;
        }

        .button-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed) !important;
        }

        #adminmenu .awaiting-mod,
        #adminmenu .update-plugins {
            background: #ef4444 !important;
            color: white !important;
            border-radius: 100px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }

        #screen-meta,
        #screen-meta-links {
            background: #1e293b;
        }

        #screen-meta-links .screen-meta-toggle {
            border-color: rgba(148, 163, 184, 0.1);
        }

        #screen-meta-links a {
            color: #94a3b8;
        }

        #screen-meta-links a:hover {
            color: #f8fafc;
        }

        .postbox .hndle {
            color: #f8fafc;
        }

        @media (max-width: 1200px) {
            #dashboard-grid-container {
                grid-template-columns: 1fr !important;
            }
            

        }
        
        @media (max-width: 782px) {
            #dashboard-grid-container {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
            
            #custom-sidebar-header {
                padding: 16px 12px;
            }
            
            .sidebar-logo {
                margin-bottom: 16px;
            }
            
            .logo-icon {
                width: 36px;
                height: 36px;
            }
            
            .logo-name {
                font-size: 16px;
            }
            
            .sidebar-quick-stats {
                display: none;
            }
            

            
            .widget-nav-buttons button {
                width: 28px;
                height: 28px;
            }
            
            .wrap {
                margin: 10px;
                padding: 20px;
            }
        }

        @media only screen and (max-width: 960px) {
            #adminmenuback,
            #adminmenuwrap,
            #adminmenu {
                background: #0f172a !important;
            }
            
            body.auto-fold #adminmenu,
            body.auto-fold #adminmenuback,
            body.auto-fold #adminmenuwrap {
                background: #0f172a !important;
            }
            
            /* Keep custom header visible when menu is open on mobile */
            body:not(.folded) #custom-sidebar-header {
                display: block !important;
            }
            
            /* Hide custom header when menu is collapsed on mobile */
            body.folded #custom-sidebar-header {
                display: none !important;
            }
        }
        
        @media only screen and (min-width: 961px) {
            #custom-sidebar-header {
                display: block !important;
            }
        }
    </style>
    <?php
}
add_action('admin_head', 'custom_admin_dashboard_styles');

function custom_welcome_panel() {
    $current_user = wp_get_current_user();
    ?>
    <div class="welcome-panel">
        <div class="welcome-panel-content">
            <h2>Bienvenue, <?php echo esc_html($current_user->display_name); ?>! 👋</h2>
            <p class="about-description">Votre tableau de bord est superbe. Voici un aperçu des performances de votre site.</p>
        </div>
    </div>
    <?php
}
add_action('welcome_panel', 'custom_welcome_panel');
