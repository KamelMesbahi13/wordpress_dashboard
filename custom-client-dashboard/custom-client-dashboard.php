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
    wp_add_dashboard_widget('custom_growth_widget', 'Graphique de Croissance', 'custom_growth_widget_display');
    wp_add_dashboard_widget('custom_stats_widget', 'Statistiques', 'custom_stats_widget_display');
    wp_add_dashboard_widget('custom_performance_widget', 'Performance', 'custom_performance_widget_display');
    wp_add_dashboard_widget('custom_storage_widget', 'Stockage Serveur', 'custom_storage_widget_display');
    wp_add_dashboard_widget('custom_income_widget', 'Aperçu des Revenus', 'custom_income_widget_display');
}
add_action('wp_dashboard_setup', 'add_custom_dashboard_widgets');

function custom_income_widget_display() {
    $total_posts = wp_count_posts()->publish;
    $total_pages = wp_count_posts('page')->publish;
    $revenue_estimate = ($total_posts * 15) + ($total_pages * 25);
    ?>
    <div class="modern-income-widget">
        <div class="income-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v12M9 9h6M9 15h6"/>
            </svg>
        </div>
        <div class="income-content">
            <div class="income-label">Valeur Estimée</div>
            <div class="income-amount"><?php echo number_format($revenue_estimate); ?>$</div>
        </div>
    </div>
    <?php
}

function custom_stats_widget_display() {
    $total_posts = wp_count_posts()->publish;
    $total_pages = wp_count_posts('page')->publish;
    $total_users = count_users()['total_users'];
    $total_comments = wp_count_comments()->approved;
    
    $stats = array(
        array('label' => 'Articles Publiés', 'value' => $total_posts, 'max' => 100, 'color' => '#6366f1'),
        array('label' => 'Pages Créées', 'value' => $total_pages, 'max' => 50, 'color' => '#06b6d4'),
        array('label' => 'Utilisateurs', 'value' => $total_users, 'max' => 100, 'color' => '#8b5cf6'),
        array('label' => 'Commentaires', 'value' => $total_comments, 'max' => 200, 'color' => '#10b981'),
    );
    ?>
    <div class="modern-stats-widget">
        <?php foreach($stats as $stat): ?>
            <div class="stat-item">
                <div class="stat-header">
                    <span class="stat-label"><?php echo $stat['label']; ?></span>
                    <span class="stat-value"><?php echo $stat['value']; ?></span>
                </div>
                <div class="stat-bar">
                    <div class="stat-progress" style="width: <?php echo min(($stat['value'] / $stat['max']) * 100, 100); ?>%; background: linear-gradient(90deg, <?php echo $stat['color']; ?>, <?php echo $stat['color']; ?>99);"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function custom_growth_widget_display() {
    global $wpdb;
    
    // Optimize: Get all data in one query used GROUP BY
    $results = $wpdb->get_results(
        "SELECT DATE_FORMAT(post_date, '%Y-%m') as month, COUNT(*) as count 
         FROM $wpdb->posts 
         WHERE post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
         GROUP BY month
         ORDER BY month ASC"
    );

    // Initialize array with last 7 months (0 count)
    $data_map = array();
    for($i = 6; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("-$i months"));
        $label = date('M', strtotime("-$i months"));
        $data_map[$key] = array('label' => $label, 'count' => 0);
    }

    // Fill with actual data
    foreach($results as $row) {
        if(isset($data_map[$row->month])) {
            $data_map[$row->month]['count'] = intval($row->count);
        }
    }

    // Extract for chart
    $months = array_column($data_map, 'label');
    $counts = array_column($data_map, 'count');
    
    $chart_data = json_encode(array(
        'labels' => $months,
        'values' => $counts
    ));
    ?>
    <div class="modern-growth-widget">
        <div class="growth-header">
            <div class="growth-title">Publications Mensuelles</div>
            <div class="growth-badge">7 derniers mois</div>
        </div>
        <div id="growth-chart-container">
            <canvas id="growthChart"></canvas>
        </div>
        <div id="chartTooltip"></div>
    </div>
    <script type="text/javascript">
        window.growthChartData = <?php echo $chart_data; ?>;
    </script>
    <?php
}

function custom_storage_widget_display() {
    // Optimization: Cache this expensive calculation for 1 hour
    $total_size = get_transient('custom_dashboard_storage_size');
    
    if (false === $total_size) {
        $upload_dir = wp_upload_dir();
        $total_size = 0;
        
        if(is_dir($upload_dir['basedir'])) {
            $total_size = get_dir_size($upload_dir['basedir']) / 1024 / 1024 / 1024;
        }
        
        // Cache result
        set_transient('custom_dashboard_storage_size', $total_size, HOUR_IN_SECONDS);
    }
    
    $max_storage = 50;
    $percentage = min(($total_size / $max_storage) * 100, 100);
    $color = $percentage > 80 ? '#ef4444' : ($percentage > 60 ? '#f59e0b' : '#10b981');
    ?>
    <div class="modern-storage-widget">
        <div class="storage-visual">
            <svg width="100" height="100" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="40" stroke="#1e293b" stroke-width="8" fill="none"/>
                <circle cx="50" cy="50" r="40" stroke="<?php echo $color; ?>" stroke-width="8" fill="none"
                        stroke-dasharray="<?php echo (2 * 3.14159 * 40); ?>"
                        stroke-dashoffset="<?php echo (2 * 3.14159 * 40) * (1 - $percentage/100); ?>"
                        stroke-linecap="round"
                        transform="rotate(-90 50 50)"/>
            </svg>
            <div class="storage-percent"><?php echo round($percentage); ?>%</div>
        </div>
        <div class="storage-details">
            <div class="storage-title">Espace Utilisé</div>
            <div class="storage-values">
                <span class="storage-used"><?php echo round($total_size, 2); ?> GB</span>
                <span class="storage-separator">/</span>
                <span class="storage-total"><?php echo $max_storage; ?> GB</span>
            </div>
            <div class="storage-bar-container">
                <div class="storage-bar-bg">
                    <div class="storage-bar-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;"></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function custom_performance_widget_display() {
    global $wpdb;
    $total_posts = wp_count_posts()->publish;
    $total_comments = wp_count_comments()->approved;
    $total_media = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment'");
    
    $performance_score = min(100, ($total_posts * 2) + ($total_comments * 0.5) + ($total_media * 0.3));
    ?>
    <div class="modern-performance-widget">
        <div class="performance-main">
            <div class="performance-circle-container">
                <svg width="140" height="140" viewBox="0 0 140 140">
                    <defs>
                        <linearGradient id="perfGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#06b6d4" />
                            <stop offset="100%" style="stop-color:#8b5cf6" />
                        </linearGradient>
                    </defs>
                    <circle cx="70" cy="70" r="58" stroke="#1e293b" stroke-width="12" fill="none"/>
                    <circle cx="70" cy="70" r="58" stroke="url(#perfGradient)" stroke-width="12" fill="none"
                            stroke-dasharray="<?php echo (2 * 3.14159 * 58); ?>"
                            stroke-dashoffset="<?php echo (2 * 3.14159 * 58) * (1 - $performance_score/100); ?>"
                            stroke-linecap="round"
                            transform="rotate(-90 70 70)"/>
                </svg>
                <div class="performance-score">
                    <span class="score-value"><?php echo round($performance_score); ?></span>
                    <span class="score-unit">%</span>
                </div>
            </div>
            <div class="performance-label">Score Global</div>
        </div>
        <div class="performance-stats">
            <div class="perf-stat-item">
                <div class="perf-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="perf-stat-info">
                    <span class="perf-stat-value"><?php echo $total_posts; ?></span>
                    <span class="perf-stat-label">Articles</span>
                </div>
            </div>
            <div class="perf-stat-item">
                <div class="perf-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                </div>
                <div class="perf-stat-info">
                    <span class="perf-stat-value"><?php echo $total_media; ?></span>
                    <span class="perf-stat-label">Médias</span>
                </div>
            </div>
            <div class="perf-stat-item">
                <div class="perf-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div class="perf-stat-info">
                    <span class="perf-stat-value"><?php echo $total_comments; ?></span>
                    <span class="perf-stat-label">Commentaires</span>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function get_dir_size($directory) {
    $size = 0;
    if(!is_dir($directory)) return 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }
    return $size;
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
        <a href="<?php echo esc_url(get_edit_profile_url($current_user->ID)); ?>" class="sidebar-user" style="text-decoration: none; display: flex; color: inherit; cursor: pointer;">
            <div class="user-avatar">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                <span class="user-status"></span>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                <span class="user-role"><?php echo esc_html($user_role); ?></span>
            </div>
            <div class="user-menu-toggle">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"/>
                    <circle cx="12" cy="5" r="1"/>
                    <circle cx="12" cy="19" r="1"/>
                </svg>
            </div>
        </a>
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
            
            function initGrowthChart() {
                var container = document.getElementById('growth-chart-container');
                if (!container) return;
                
                var oldCanvas = document.getElementById('growthChart');
                if (oldCanvas) {
                    oldCanvas.remove();
                }
                
                var canvas = document.createElement('canvas');
                canvas.id = 'growthChart';
                container.appendChild(canvas);
                
                var tooltip = document.getElementById('chartTooltip');
                
                if (!canvas.getContext) return;
                if (!window.growthChartData) return;
                
                var ctx = canvas.getContext('2d');
                var data = window.growthChartData.values;
                var labels = window.growthChartData.labels;
                
                var allZero = data.every(function(val) { return val === 0; });
                // Removed fake data generation to show accurate empty state

                
                function resizeCanvas() {
                    var rect = container.getBoundingClientRect();
                    canvas.width = rect.width;
                    canvas.height = 220;
                    drawChart(-1);
                }
                
                var max = Math.max.apply(null, data) || 10;
                var bars = [];
                
                function drawChart(hoverIndex) {
                    var width = canvas.width;
                    var height = canvas.height - 50;
                    var padding = 40;
                    var barWidth = (width - padding * 2) / data.length;
                    
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    
                    ctx.strokeStyle = 'rgba(148, 163, 184, 0.1)';
                    ctx.lineWidth = 1;
                    for (var i = 0; i <= 4; i++) {
                        var y = padding / 2 + (height / 4) * i;
                        ctx.beginPath();
                        ctx.moveTo(padding, y);
                        ctx.lineTo(width - padding / 2, y);
                        ctx.stroke();
                    }
                    
                    bars = [];
                    data.forEach(function(value, index) {
                        var barHeight = (value / max) * (height - 20);
                        if (barHeight < 8) barHeight = 8;
                        
                        var x = padding + index * barWidth + barWidth * 0.15;
                        var y = height - barHeight + padding / 2;
                        var actualBarWidth = barWidth * 0.7;
                        
                        bars.push({
                            x: x,
                            y: y,
                            width: actualBarWidth,
                            height: barHeight,
                            value: value,
                            label: labels[index]
                        });
                        
                        var gradient = ctx.createLinearGradient(x, y + barHeight, x, y);
                        if (index === hoverIndex) {
                            gradient.addColorStop(0, '#6366f1');
                            gradient.addColorStop(1, '#8b5cf6');
                        } else {
                            gradient.addColorStop(0, '#3b82f6');
                            gradient.addColorStop(1, '#60a5fa');
                        }
                        
                        ctx.fillStyle = gradient;
                        var radius = 6;
                        ctx.beginPath();
                        ctx.moveTo(x + radius, y);
                        ctx.lineTo(x + actualBarWidth - radius, y);
                        ctx.quadraticCurveTo(x + actualBarWidth, y, x + actualBarWidth, y + radius);
                        ctx.lineTo(x + actualBarWidth, y + barHeight);
                        ctx.lineTo(x, y + barHeight);
                        ctx.lineTo(x, y + radius);
                        ctx.quadraticCurveTo(x, y, x + radius, y);
                        ctx.fill();
                        
                        if (index === hoverIndex) {
                            ctx.shadowColor = 'rgba(99, 102, 241, 0.5)';
                            ctx.shadowBlur = 15;
                            ctx.fill();
                            ctx.shadowBlur = 0;
                        }
                        
                        ctx.fillStyle = index === hoverIndex ? '#f8fafc' : '#64748b';
                        ctx.font = '500 12px Inter, -apple-system, sans-serif';
                        ctx.textAlign = 'center';
                        ctx.fillText(labels[index], x + actualBarWidth / 2, canvas.height - 15);
                        
                        if (index === hoverIndex) {
                            ctx.fillStyle = '#f8fafc';
                            ctx.font = '600 13px Inter, -apple-system, sans-serif';
                            ctx.fillText(value, x + actualBarWidth / 2, y - 10);
                        }
                    });
                }
                
                resizeCanvas();
                window.addEventListener('resize', resizeCanvas);
                
                canvas.addEventListener('mousemove', function(e) {
                    var rect = canvas.getBoundingClientRect();
                    var mouseX = e.clientX - rect.left;
                    var mouseY = e.clientY - rect.top;
                    var hoverIndex = -1;
                    
                    bars.forEach(function(bar, index) {
                        if (mouseX >= bar.x && mouseX <= bar.x + bar.width &&
                            mouseY >= bar.y && mouseY <= bar.y + bar.height) {
                            hoverIndex = index;
                            
                            if (tooltip) {
                                tooltip.style.display = 'block';
                                tooltip.style.left = (bar.x + bar.width / 2) + 'px';
                                tooltip.style.top = (bar.y - 50) + 'px';
                                tooltip.innerHTML = '<strong>' + bar.label + '</strong><br><span style="color: #60a5fa;">' + bar.value + ' articles</span>';
                            }
                        }
                    });
                    
                    if (hoverIndex === -1 && tooltip) {
                        tooltip.style.display = 'none';
                    }
                    
                    canvas.style.cursor = hoverIndex >= 0 ? 'pointer' : 'default';
                    drawChart(hoverIndex);
                });
                
                canvas.addEventListener('mouseleave', function() {
                    if (tooltip) {
                        tooltip.style.display = 'none';
                    }
                    drawChart(-1);
                });
            }
            
            function reorganizeDashboardWidgets() {
                var $wrap = $('#dashboard-widgets-wrap');
                if ($wrap.length === 0) return;
                
                if ($('#dashboard-grid-container').length > 0) return;
                
                var widgetOrder = [
                    'custom_growth_widget',
                    'custom_stats_widget', 
                    'custom_performance_widget',
                    'custom_storage_widget',
                    'custom_income_widget'
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
                
                setTimeout(function() {
                    initGrowthChart();
                }, 100);
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
                            if ($widget.attr('id') === 'custom_growth_widget') {
                                initGrowthChart();
                            }
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
                    
                    setTimeout(function() {
                        initGrowthChart();
                    }, 100);
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

        .modern-income-widget {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 32px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 24px;
            color: white;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }

        .income-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .income-icon svg {
            opacity: 0.9;
        }

        .income-content {
            flex: 1;
        }

        .income-label {
            font-size: 14px;
            font-weight: 500;
            opacity: 0.85;
            margin-bottom: 6px;
        }

        .income-amount {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .modern-stats-widget {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            background: rgba(148, 163, 184, 0.03);
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.08);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-label {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        .stat-value {
            font-size: 14px;
            color: #f8fafc;
            font-weight: 600;
        }

        .stat-bar {
            height: 8px;
            background: rgba(148, 163, 184, 0.1);
            border-radius: 100px;
            overflow: hidden;
        }

        .stat-progress {
            height: 100%;
            border-radius: 100px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-growth-widget {
            position: relative;
        }

        .growth-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .growth-title {
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
        }

        .growth-badge {
            font-size: 11px;
            font-weight: 500;
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.15);
            padding: 4px 10px;
            border-radius: 100px;
            border: 1px solid rgba(96, 165, 250, 0.2);
        }
        
        #growth-chart-container {
            width: 100%;
            height: 220px;
            position: relative;
        }

        #growthChart {
            width: 100% !important;
            height: 100% !important;
        }

        #chartTooltip {
            display: none;
            position: absolute;
            background: #0f172a;
            color: white;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            pointer-events: none;
            z-index: 1000;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
            transform: translateX(-50%);
            white-space: nowrap;
            line-height: 1.5;
        }
        
        #chartTooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 8px solid transparent;
            border-top-color: #0f172a;
        }

        .modern-storage-widget {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .storage-visual {
            position: relative;
            flex-shrink: 0;
        }

        .storage-visual svg {
            display: block;
        }

        .storage-percent {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: 700;
            color: #f8fafc;
        }

        .storage-details {
            flex: 1;
        }

        .storage-title {
            font-size: 14px;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 8px;
        }

        .storage-values {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 12px;
        }

        .storage-used {
            font-size: 24px;
            font-weight: 700;
            color: #f8fafc;
        }

        .storage-separator {
            color: #64748b;
            font-size: 16px;
        }

        .storage-total {
            font-size: 14px;
            color: #94a3b8;
            font-weight: 500;
        }

        .storage-bar-container {
            width: 100%;
        }

        .storage-bar-bg {
            height: 8px;
            background: rgba(148, 163, 184, 0.1);
            border-radius: 100px;
            overflow: hidden;
        }

        .storage-bar-fill {
            height: 100%;
            border-radius: 100px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-performance-widget {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .performance-main {
            text-align: center;
            flex-shrink: 0;
        }

        .performance-circle-container {
            position: relative;
            display: inline-block;
            margin-bottom: 12px;
        }

        .performance-circle-container svg {
            display: block;
        }

        .performance-score {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: baseline;
            gap: 2px;
        }

        .score-value {
            font-size: 32px;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.02em;
        }

        .score-unit {
            font-size: 16px;
            font-weight: 600;
            color: #64748b;
        }

        .performance-label {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        .performance-stats {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .perf-stat-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .perf-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .perf-stat-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .perf-stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #f8fafc;
        }

        .perf-stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
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
            
            .modern-performance-widget {
                flex-direction: column;
                text-align: center;
            }
            
            .performance-stats {
                width: 100%;
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
            
            .modern-income-widget {
                padding: 24px;
                flex-direction: column;
                text-align: center;
            }
            
            .income-amount {
                font-size: 32px;
            }
            
            .modern-storage-widget {
                flex-direction: column;
                text-align: center;
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
