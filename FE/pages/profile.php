<link rel="stylesheet" href="./assets/css/marketplace.css">
<section class="mp-page">
    <div class="mp-heading"><div><p class="mp-eyebrow">TÀI KHOẢN REUSE</p><h1>Hồ sơ cá nhân</h1><p>Cập nhật thông tin liên hệ và bảo vệ tài khoản của bạn.</p></div><a class="mp-link" href="layout.php?page=my-products">Quản lý tin đăng →</a></div>
    <?php if ($profileNotice !== ''): ?><p class="mp-alert mp-success" role="status"><?php echo e($profileNotice); ?></p><?php endif; ?>
    <?php if ($profileError !== ''): ?><p class="mp-alert mp-error" role="alert"><?php echo e($profileError); ?></p><?php endif; ?>
    <?php if ($profileUser): ?>
    <div class="mp-two-cols">
      <section class="mp-panel">
        <h2>Thông tin của bạn</h2>
        <div class="mp-stats"><span>Điểm uy tín: <strong><?php echo (int)$profileUser['reputation_score']; ?></strong></span><span>Điểm trao đổi: <strong><?php echo (int)$profileUser['barter_wallet']; ?></strong></span></div>
        <form action="layout.php?page=profile" method="post" class="mp-form">
          <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>"><input type="hidden" name="profile_action" value="update_info">
          <label for="profile_fullname">Họ và tên</label><input id="profile_fullname" name="fullname" maxlength="100" required value="<?php echo e($profileUser['fullname']); ?>">
          <label for="profile_email">Email đăng nhập</label><input id="profile_email" type="email" value="<?php echo e($profileUser['email']); ?>" disabled><p class="mp-hint">Email được giữ nguyên để tránh ảnh hưởng đến đăng nhập.</p>
          <label for="profile_phone">Số điện thoại</label><input id="profile_phone" name="phone" type="tel" maxlength="20" value="<?php echo e($profileUser['phone'] ?? ''); ?>" placeholder="Ví dụ: 0901234567">
          <label for="profile_address">Địa chỉ liên hệ</label><textarea id="profile_address" name="address" rows="3" maxlength="500" placeholder="Nhập địa chỉ của bạn"><?php echo e($profileUser['address'] ?? ''); ?></textarea>
          <button type="submit" class="mp-primary">Lưu thông tin</button>
        </form>
      </section>
      <section class="mp-panel">
        <h2>Đổi mật khẩu</h2><p class="mp-hint">Đặt mật khẩu mới từ 8 ký tự và không chia sẻ cho người khác.</p>
        <form action="layout.php?page=profile" method="post" class="mp-form">
          <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>"><input type="hidden" name="profile_action" value="change_password">
          <label for="current_password">Mật khẩu hiện tại</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
          <label for="new_password">Mật khẩu mới</label><input id="new_password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
          <label for="confirm_password">Nhập lại mật khẩu mới</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
          <button type="submit" class="mp-primary">Đổi mật khẩu</button>
        </form>
      </section>
    </div>
    <?php endif; ?>
</section>
