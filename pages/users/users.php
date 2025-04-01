<?php echo 'إدارة المستخدمين والصلاحيات'; ?>

<!-- Get all roles -->
<?php
$stmt = $pdo->query("SELECT * FROM roles ORDER BY name");
$roles = $stmt->fetchAll();
?>

<table class="table">
    <thead>
        <tr>
            <th>الدور</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo getRoleName($user['role']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- In the edit modal -->
<div class="mb-3">
    <label class="form-label">الدور</label>
    <select class="form-select" name="role_id" required>
        <?php foreach ($roles as $role): ?>
        <option value="<?php echo $role['id']; ?>" <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($role['description']); ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- In the add modal -->
<div class="mb-3">
    <label class="form-label">الدور</label>
    <select class="form-select" name="role_id" required>
        <?php foreach ($roles as $role): ?>
        <option value="<?php echo $role['id']; ?>">
            <?php echo htmlspecialchars($role['description']); ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>