<?php if ($this->Session->read('Message.flash')): ?>
<div class="flash-error mb-4 rounded-xl p-3 text-sm">
    <?php echo $this->Session->flash('flash', array('element' => false)); ?>
</div>
<?php endif; ?>

<h2 class="text-xl font-bold text-slate-900 mb-1">Welcome back</h2>
<p class="text-slate-500 text-sm mb-6">Sign in to your account to continue</p>

<?php echo $this->Form->create('User', array(
    'url' => array('controller' => 'auth', 'action' => 'login'),
    'class' => 'space-y-4',
)); ?>

<div>
    <label class="app-label">Email address</label>
    <?php echo $this->Form->input('email', array(
        'label' => false,
        'type' => 'email',
        'class' => 'app-input',
        'placeholder' => 'admin@example.com',
        'div' => false,
    )); ?>
</div>

<div>
    <label class="app-label">Password</label>
    <?php echo $this->Form->input('password', array(
        'label' => false,
        'type' => 'password',
        'class' => 'app-input',
        'placeholder' => '••••••••',
        'div' => false,
    )); ?>
</div>

<div class="pt-2">
    <?php echo $this->Form->submit('Sign in', array(
        'class' => 'app-button app-button-primary w-full',
        'div' => false,
    )); ?>
</div>

<?php echo $this->Form->end(); ?>

<div class="mt-6 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">
    <strong>Test credentials:</strong><br>
    Email: admin@example.com<br>
    Password: password
</div>
