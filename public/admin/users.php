<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireRole('admin');
$currentUser = currentUser();
$error = '';
$success = '';

$pdo = getDbConnection();

// Handle user actions (toggle status, change role, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();

    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);

    if ($targetUserId <= 0) {
        $error = 'ID pengguna tidak sah.';
    } elseif ($targetUserId === $currentUser['user_id'] && in_array($action, ['toggle_status', 'delete', 'change_role'], true)) {
        // Prevent modifying current active admin account
        $error = 'Tindakan keselamatan: Anda tidak dibenarkan menggantung, menukar peranan atau memadamkan akaun anda sendiri.';
    } else {
        try {
            $tStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $tStmt->execute([$targetUserId]);
            $targetUser = $tStmt->fetch();

            if (!$targetUser) {
                $error = 'Pengguna tidak ditemui.';
            } else {
                if ($action === 'toggle_status') {
                    $newStatus = ((int)$targetUser['is_active'] === 1) ? 0 : 1;
                    $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?")->execute([$newStatus, $targetUserId]);
                    set_flash('success', 'Status akaun ' . $targetUser['name'] . ' berjaya ditukar kepada ' . ($newStatus ? 'Aktif' : 'Digantung') . '.');
                } elseif ($action === 'change_role') {
                    $newRole = $_POST['new_role'] ?? 'student';
                    if (in_array($newRole, ['student', 'landlord', 'admin'], true)) {
                        $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?")->execute([$newRole, $targetUserId]);
                        set_flash('success', 'Peranan akaun ' . $targetUser['name'] . ' berjaya ditukar kepada ' . ucfirst($newRole) . '.');
                    }
                } elseif ($action === 'delete') {
                    // Safe delete with cascade
                    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$targetUserId]);
                    set_flash('info', 'Akaun pengguna ' . $targetUser['name'] . ' telah dipadamkan secara kekal dari pangkalan data.');
                }
                header('Location: ' . BASE_URL . '/public/admin/users.php');
                exit;
            }
        } catch (Exception $e) {
            error_log('Admin user action error: ' . $e->getMessage());
            $error = 'Ralat pangkalan data semasa melaksanakan tindakan terhadap pengguna.';
        }
    }
}

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE :s1 OR email LIKE :s2 OR phone LIKE :s3)";
    $params['s1'] = "%$search%";
    $params['s2'] = "%$search%";
    $params['s3'] = "%$search%";
}

if ($roleFilter !== 'all' && !empty($roleFilter)) {
    $sql .= " AND role = :role";
    $params['role'] = $roleFilter;
}

$sql .= " ORDER BY user_id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$pageTitle = 'Pengurusan Pengguna (Admin)';
require_once __DIR__ . '/../../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 mb-2">
                <i class="fa-solid fa-users-gear"></i> Modul Pengurusan Pengguna (FR022)
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800">Senarai & Kawalan Pengguna Sistem</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Urus peranan RBAC, status keaktifan akaun, dan penyelenggaraan pangkalan data pengguna.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/public/admin/dashboard.php" class="px-4 py-2.5 rounded-2xl bg-white border border-slate-200 hover:border-purple-300 text-slate-700 font-extrabold text-xs shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-shield-check text-purple-600"></i>
                <span>Kembali ke Moderasi</span>
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs font-semibold text-rose-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation shrink-0"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Search & Filter Form -->
    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-3 text-xs">
        <form method="GET" action="<?= BASE_URL ?>/public/admin/users.php" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1 sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama, e-mel atau telefon..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:outline-none focus:border-purple-500">
            </div>

            <div>
                <select name="role" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold">
                    <option value="all" <?= ($roleFilter === 'all') ? 'selected' : '' ?>>Semua Peranan</option>
                    <option value="student" <?= ($roleFilter === 'student') ? 'selected' : '' ?>>Pelajar (Student)</option>
                    <option value="landlord" <?= ($roleFilter === 'landlord') ? 'selected' : '' ?>>Tuan Rumah (Landlord)</option>
                    <option value="admin" <?= ($roleFilter === 'admin') ? 'selected' : '' ?>>Pentadbir (Admin)</option>
                </select>
            </div>

            <?php if (!empty($search) || $roleFilter !== 'all'): ?>
                <a href="<?= BASE_URL ?>/public/admin/users.php" class="text-purple-600 font-bold hover:underline">
                    Set Semula
                </a>
            <?php endif; ?>
        </form>

        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
            <?= count($usersList) ?> Akaun Dijumpai
        </span>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 uppercase tracking-wider font-extrabold">
                        <th class="py-3.5 px-4">ID</th>
                        <th class="py-3.5 px-4">Nama Pengguna</th>
                        <th class="py-3.5 px-4">Alamat E-mel</th>
                        <th class="py-3.5 px-4">Telefon</th>
                        <th class="py-3.5 px-4">Peranan (Role)</th>
                        <th class="py-3.5 px-4">Pengesahan</th>
                        <th class="py-3.5 px-4">Status Akaun</th>
                        <th class="py-3.5 px-4 text-center">Tindakan Kawalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($usersList as $u): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-400">#<?= $u['user_id'] ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg pink-gradient text-white flex items-center justify-center font-bold text-[10px]">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <span><?= e($u['name']) ?></span>
                                    <?php if ($u['user_id'] === $currentUser['user_id']): ?>
                                        <span class="text-[9px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-extrabold">(Anda)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-semibold"><?= e($u['email']) ?></td>
                            <td class="py-3.5 px-4 text-slate-600"><?= e($u['phone']) ?></td>
                            <td class="py-3.5 px-4">
                                <form method="POST" action="<?= BASE_URL ?>/public/admin/users.php" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                    <select name="new_role" onchange="this.form.submit()" <?= ($u['user_id'] === $currentUser['user_id']) ? 'disabled' : '' ?> class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-bold capitalize <?= 
                                        $u['role'] === 'admin' ? 'text-purple-700' :
                                        ($u['role'] === 'landlord' ? 'text-amber-800' : 'text-pinkbrand-700')
                                    ?>">
                                        <option value="student" <?= ($u['role'] === 'student') ? 'selected' : '' ?>>Student</option>
                                        <option value="landlord" <?= ($u['role'] === 'landlord') ? 'selected' : '' ?>>Landlord</option>
                                        <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($u['verification_status'] === 'verified'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">Verified</span>
                                <?php elseif ($u['verification_status'] === 'pending'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800">Pending</span>
                                <?php elseif ($u['verification_status'] === 'rejected'): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800">Rejected</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ((int)$u['is_active'] === 1): ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                        <i class="fa-solid fa-circle text-[8px] text-emerald-500"></i> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700">
                                        <i class="fa-solid fa-circle text-[8px] text-rose-500"></i> Digantung
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($u['user_id'] !== $currentUser['user_id']): ?>
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Toggle Status (Activate / Suspend) -->
                                        <form method="POST" action="<?= BASE_URL ?>/public/admin/users.php" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg border <?= ((int)$u['is_active'] === 1) ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' ?> text-[11px] font-bold transition" title="<?= ((int)$u['is_active'] === 1) ? 'Gantung Akaun' : 'Aktifkan Semula' ?>">
                                                <i class="fa-solid <?= ((int)$u['is_active'] === 1) ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete User -->
                                        <form method="POST" action="<?= BASE_URL ?>/public/admin/users.php" class="inline" onsubmit="return confirm('AMARAN: Adakah anda pasti ingin memadam akaun <?= addslashes($u['name']) ?> secara kekal?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-[11px] font-bold transition" title="Padam Akaun">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400 text-[11px] italic">Akaun Semasa</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
