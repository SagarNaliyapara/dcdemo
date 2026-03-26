<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title_for_layout) ? h($title_for_layout) . ' — DC Orders' : 'DC Orders'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php echo $this->Html->css('app'); ?>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">DC Orders</h1>
            <p class="text-slate-400 text-sm mt-1">Pharmacy Order Management</p>
        </div>
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <?php echo $content_for_layout; ?>
        </div>
        <p class="text-center text-slate-500 text-xs mt-6">CakePHP 2.x Edition</p>
    </div>
</body>
</html>
