<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title_for_layout) ? h($title_for_layout) . ' — DC Orders' : 'DC Orders'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php echo $this->Html->css('app'); ?>
    <?php echo $this->Html->script('app'); ?>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body style="display:flex;min-height:100vh;margin:0;">
    <!-- Sidebar -->
    <aside class="dc-sidebar">
        <div class="dc-sidebar-logo">
            <a href="<?php echo Router::url(array('controller' => 'dashboard_reports', 'action' => 'index')); ?>" class="dc-logo-link">
                <div class="dc-logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="dc-logo-text">DC Orders</span>
            </a>
        </div>
        <nav class="dc-sidebar-nav">
            <p class="dc-nav-group-label">Platform</p>
            <?php
            $ctrl = $this->params['controller'];
            $act  = $this->params['action'];
            $nav = array(
                array('url' => array('controller' => 'dashboard_reports', 'action' => 'index'),
                      'label' => 'Dashboard', 'icon' => 'home',
                      'active' => $ctrl === 'dashboard_reports'),
                array('url' => array('controller' => 'orders_reports', 'action' => 'history'),
                      'label' => 'Order History', 'icon' => 'list',
                      'active' => $ctrl === 'orders_reports' && $act === 'history'),
                array('url' => array('controller' => 'orders_reports', 'action' => 'scheduledReports'),
                      'label' => 'Scheduled Reports', 'icon' => 'clock',
                      'active' => $ctrl === 'orders_reports' && $act === 'scheduledReports'),
                array('url' => array('controller' => 'notification_rules_reports', 'action' => 'index'),
                      'label' => 'Notification Rules', 'icon' => 'bell',
                      'active' => $ctrl === 'notification_rules_reports'),
            );
            $icons = array(
                'home'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
                'list'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
                'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bell'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
            );
            foreach ($nav as $item):
                $activeClass = $item['active'] ? ' dc-nav-item-active' : '';
            ?>
            <a href="<?php echo Router::url($item['url']); ?>" class="dc-nav-item<?php echo $activeClass; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <?php echo $icons[$item['icon']]; ?>
                </svg>
                <?php echo h($item['label']); ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="dc-sidebar-footer">
            <?php $user = $this->Session->read('Auth.User'); ?>
            <div class="dc-user-card">
                <div class="dc-avatar">
                    <?php
                    $name = $user['name'] ?? 'U';
                    $initials = strtoupper(substr($name, 0, 1));
                    $spacePos = strpos($name, ' ');
                    if ($spacePos !== false) {
                        $initials .= strtoupper(substr($name, $spacePos + 1, 1));
                    }
                    echo $initials;
                    ?>
                </div>
                <div class="dc-user-info">
                    <p class="dc-user-name"><?php echo h($user['name'] ?? 'User'); ?></p>
                    <p class="dc-user-email"><?php echo h($user['email'] ?? ''); ?></p>
                </div>
                <a href="<?php echo Router::url(array('controller' => 'auth', 'action' => 'logout')); ?>" class="dc-logout" title="Sign out">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="dc-main">
        <?php echo $content_for_layout; ?>
    </main>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
