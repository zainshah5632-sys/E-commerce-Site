<?php
declare(strict_types=1);

$users = $users ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$pages = $pages ?? 1;
$filters = $filters ?? [];
?>

<div class="admin-header">
  <div>
    <h1>Users & Customers</h1>
    <p style="font-size: 0.9rem; color: var(--text-3); margin-top: 4px;">
      Total registered accounts: <strong><?= (int)$total ?></strong>
    </p>
  </div>
  <a href="<?= url('/admin/users/new') ?>" class="btn btn--primary btn--sm">
    <?= icon('user-plus') ?>
    <span>Add New User</span>
  </a>
</div>

<!-- Search & Filter Controls -->
<div class="glass--strong" style="padding: var(--s-4); margin-bottom: var(--s-5);">
  <form action="<?= url('/admin/users') ?>" method="GET" style="display: flex; gap: var(--s-3); flex-wrap: wrap; align-items: center;">
    <div style="flex: 1; min-width: 220px; position: relative;">
      <input
        type="search"
        name="q"
        value="<?= ea($filters['q'] ?? '') ?>"
        placeholder="Search name, email, or username..."
        class="form-input"
        style="padding-left: 38px;"
      >
      <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-3); pointer-events: none;">
        <?= icon('search') ?>
      </span>
    </div>

    <div style="width: 160px;">
      <select name="role" class="form-select" onchange="this.form.submit()">
        <option value="">All Roles</option>
        <option value="customer" <?= ($filters['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customers Only</option>
        <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admins Only</option>
      </select>
    </div>

    <button type="submit" class="btn btn--secondary btn--sm">Filter</button>

    <?php if (!empty($filters['q']) || !empty($filters['role'])): ?>
      <a href="<?= url('/admin/users') ?>" class="btn btn--ghost btn--sm">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Users Table -->
<div class="table-container glass--strong">
  <table class="table">
    <thead>
      <tr>
        <th>User / Handle</th>
        <th>Email Address</th>
        <th>Phone Number</th>
        <th>System Role</th>
        <th>Registered</th>
        <th style="text-align: right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: var(--s-7); color: var(--text-3);">
            No user accounts found matching your query.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: var(--s-3);">
                <div style="width: 36px; height: 36px; border-radius: var(--r-pill); background: var(--grad-brand-soft); display: flex; align-items: center; justify-content: center; color: var(--brand-600); font-weight: 700; font-size: 0.9rem;">
                  <?= strtoupper(substr($u['full_name'] ?? ($u['username'] ?? 'U'), 0, 1)) ?>
                </div>
                <div>
                  <div style="font-weight: 600; color: var(--text-1);"><?= e($u['full_name'] ?: 'Unnamed User') ?></div>
                  <div style="font-size: 0.8rem; color: var(--text-3);">@<?= e($u['username'] ?: 'no_handle') ?></div>
                </div>
              </div>
            </td>
            <td>
              <span style="font-family: var(--font-mono, monospace); font-size: 0.85rem; color: var(--text-2);">
                <?= e($u['email'] ?? '—') ?>
              </span>
            </td>
            <td>
              <?php if (!empty($u['phone'])): ?>
                <span style="font-size: 0.85rem; color: var(--text-2);">
                  <?= e($u['country_code'] ?? '+1') ?> <?= e($u['phone']) ?>
                </span>
              <?php else: ?>
                <span style="color: var(--text-3); font-size: 0.85rem;">Not provided</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (($u['role'] ?? '') === 'admin'): ?>
                <span class="badge badge--brand" style="background: rgba(99, 102, 241, 0.2); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.4);">
                  <?= icon('shield') ?>
                  <span>Administrator</span>
                </span>
              <?php else: ?>
                <span class="badge badge--neutral">Customer</span>
              <?php endif; ?>
            </td>
            <td>
              <span style="font-size: 0.85rem; color: var(--text-3);">
                <?= !empty($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : '—' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div style="display: inline-flex; gap: var(--s-2); align-items: center;">
                <a href="<?= url('/admin/users/' . ea($u['id']) . '/edit') ?>" class="btn btn--ghost btn--sm" title="Edit User">
                  Edit
                </a>

                <form action="<?= url('/admin/users/' . ea($u['id']) . '/role') ?>" method="POST" style="display: inline;">
                  <?= csrf_field() ?>
                  <button
                    type="submit"
                    class="btn btn--ghost btn--sm"
                    title="<?= ($u['role'] ?? '') === 'admin' ? 'Demote to Customer' : 'Promote to Admin' ?>"
                  >
                    <?= ($u['role'] ?? '') === 'admin' ? 'Make Customer' : 'Make Admin' ?>
                  </button>
                </form>

                <form action="<?= url('/admin/users/' . ea($u['id']) . '/delete') ?>" method="POST" style="display: inline;">
                  <?= csrf_field() ?>
                  <button
                    type="submit"
                    class="btn btn--danger btn--icon"
                    title="Delete User Account"
                    data-action="confirm-delete"
                    data-confirm="Permanently delete this user account? This action cannot be undone."
                  >
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
  <div class="pagination" style="margin-top: var(--s-5); display: flex; justify-content: center; gap: var(--s-2);">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a
        href="<?= url('/admin/users?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"
        class="btn <?= $i === $page ? 'btn--primary' : 'btn--ghost' ?> btn--sm"
      >
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
