<?php
declare(strict_types=1);

$users = $users ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$pages = $pages ?? 1;
$filters = $filters ?? [];

$adminCount = 0;
$customerCount = 0;
foreach ($users as $u) {
    if (($u['role'] ?? '') === 'admin') $adminCount++;
    else $customerCount++;
}
?>

<div class="admin-page-container">
  <!-- Breadcrumb -->
  <div class="admin-breadcrumb">
    <a href="<?= url('/admin') ?>">Admin</a>
    <span class="sep">/</span>
    <span class="current">Users &amp; Customers</span>
  </div>

  <!-- Page Header -->
  <div class="admin-header-row">
    <div>
      <h1 class="admin-title">Users &amp; Access Control</h1>
      <p class="admin-subtitle">Manage registered customers, authentication profiles, and role-based access permissions.</p>
    </div>
    <div class="admin-header-actions">
      <a href="<?= url('/admin/users/new') ?>" class="btn-admin-primary">
        <?= icon('user-plus') ?>
        <span>Add New User</span>
      </a>
    </div>
  </div>

  <!-- Metrics Strip -->
  <div class="admin-stats-strip">
    <div class="stat-chip">
      <span class="label">Total Registered</span>
      <span class="val"><?= (int)$total ?></span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-chip">
      <span class="label">Customers (Page)</span>
      <span class="val text-indigo"><?= $customerCount ?></span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-chip">
      <span class="label">Admins (Page)</span>
      <span class="val text-emerald"><?= $adminCount ?></span>
    </div>
  </div>

  <!-- Search & Filter Card -->
  <div class="admin-card filter-card">
    <form action="<?= url('/admin/users') ?>" method="GET" class="filter-form">
      <div class="filter-input-wrap">
        <span class="search-icon"><?= icon('search') ?></span>
        <input
          type="search"
          name="q"
          value="<?= ea($filters['q'] ?? '') ?>"
          placeholder="Search full name, email, or @username..."
          class="admin-input admin-input--search"
        >
      </div>

      <div class="filter-select-wrap">
        <select name="role" class="admin-select" onchange="this.form.submit()">
          <option value="">All Roles</option>
          <option value="customer" <?= ($filters['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customers Only</option>
          <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrators Only</option>
        </select>
      </div>

      <button type="submit" class="btn-admin-secondary">
        <?= icon('filter') ?>
        <span>Filter</span>
      </button>

      <?php if (!empty($filters['q']) || !empty($filters['role'])): ?>
        <a href="<?= url('/admin/users') ?>" class="btn-admin-ghost">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Users Table Card -->
  <div class="admin-card table-card">
    <div class="admin-table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>User / Handle</th>
            <th>Email Address</th>
            <th>Phone Contact</th>
            <th>System Role</th>
            <th>Registered Date</th>
            <th style="text-align: right; width: 140px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="6" class="empty-table-cell">
                <div class="empty-state-wrap">
                  <div class="empty-state-icon"><?= icon('user') ?></div>
                  <h3>No accounts found</h3>
                  <p>Try adjusting your search criteria or register a new user.</p>
                  <a href="<?= url('/admin/users/new') ?>" class="btn-admin-primary btn-admin-sm">
                    <?= icon('user-plus') ?>
                    <span>Create User</span>
                  </a>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $u): ?>
              <?php
                $initial = strtoupper(substr($u['full_name'] ?: ($u['username'] ?: 'U'), 0, 1));
                $isAdmin = (($u['role'] ?? '') === 'admin');
              ?>
              <tr class="admin-table-row">
                <td>
                  <div class="user-cell-wrap">
                    <div class="user-avatar-badge <?= $isAdmin ? 'user-avatar-badge--admin' : '' ?>">
                      <?= $initial ?>
                    </div>
                    <div>
                      <div class="font-semibold text-slate-900"><?= e($u['full_name'] ?: 'Unnamed User') ?></div>
                      <div class="text-xs text-slate-500">@<?= e($u['username'] ?: 'no_handle') ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="user-email-text">
                    <?= e($u['email'] ?? '—') ?>
                  </span>
                </td>
                <td>
                  <?php if (!empty($u['phone'])): ?>
                    <span class="text-sm text-slate-700">
                      <?= e($u['country_code'] ?? '+1') ?> <?= e($u['phone']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-xs text-slate-400 italic">Not provided</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($isAdmin): ?>
                    <span class="admin-role-badge admin-role-badge--admin">
                      <?= icon('shield') ?>
                      <span>Administrator</span>
                    </span>
                  <?php else: ?>
                    <span class="admin-role-badge admin-role-badge--customer">
                      <span>Customer</span>
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="text-sm text-slate-600">
                    <?= !empty($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : '—' ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <div class="admin-row-actions">
                    <a href="<?= url('/admin/users/' . ea($u['id']) . '/edit') ?>" class="action-icon-btn" title="Edit user" aria-label="Edit user">
                      <?= icon('pencil') ?>
                    </a>
                    <form action="<?= url('/admin/users/' . ea($u['id']) . '/role') ?>" method="POST" style="display:inline;">
                      <?= csrf_field() ?>
                      <button
                        type="submit"
                        class="action-icon-btn"
                        title="<?= $isAdmin ? 'Demote to Customer' : 'Promote to Admin' ?>"
                        aria-label="<?= $isAdmin ? 'Demote to Customer' : 'Promote to Admin' ?>"
                      >
                        <?= icon('shield') ?>
                      </button>
                    </form>
                    <form action="<?= url('/admin/users/' . ea($u['id']) . '/delete') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete <?= ea($u['full_name'] ?: $u['email']) ?>? This action cannot be undone.');">
                      <?= csrf_field() ?>
                      <button type="submit" class="action-icon-btn action-icon-btn--danger" title="Delete account" aria-label="Delete account">
                        <?= icon('trash') ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
      <div class="admin-table-footer">
        <?php require dirname(__DIR__) . '/partials/pagination.php'; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
