<?php $flash=bao_pull_flash(); ?>
<link rel="stylesheet" href="./assets/css/bao.css"><section class="bao-page"><div class="bao-page-heading"><div><p class="bao-eyebrow">REUSE / ADMIN</p><h1>Quản lý điểm demo</h1><p>Admin điều chỉnh điểm và hệ thống lưu lại lịch sử kiểm toán.</p></div></div>
<?php if($flash):?><p class="bao-alert"><?=e($flash['message'])?></p><?php endif;?><?php if($walletError):?><p class="bao-alert bao-error"><?=e($walletError)?></p><?php endif;?>
<div class="bao-panel"><h2>Điều chỉnh điểm người dùng</h2><form class="bao-form" method="post" action="layout.php?page=admin-wallet"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<label for="user_id">Người dùng</label><select name="user_id" id="user_id" required><?php foreach($walletUsers as $u):?><option value="<?=(int)$u['id']?>">#<?=(int)$u['id']?> <?=e($u['fullname'])?> — <?=e($u['email'])?> (<?=(int)$u['barter_wallet']?> điểm)</option><?php endforeach;?></select>
<label for="change_amount">Số điểm cộng / trừ (âm để trừ)</label><input type="number" id="change_amount" name="change_amount" min="-100000" max="100000" step="1" required>
<label for="note">Lý do điều chỉnh</label><input name="note" id="note" minlength="3" maxlength="255" required placeholder="Điểm thưởng demo / Điều chỉnh số dư">
<button type="submit" class="bao-btn">Xác nhận điều chỉnh</button></form></div></section>
