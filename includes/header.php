<?php $user = current_user(); ?>
<div class="topbar">
  <div class="brand">
    <img src="assets/images/logo.svg" alt="Logo">
    <div class="title">KALINGA DIALECTS<br>LEARNING DICTIONARY</div>
  </div>
  <div class="userbox">
    <a href="index.php" target="_blank" style="color:#fff; opacity:.9; font-weight:500; margin-right:18px; display:inline-flex; align-items:center; gap:6px;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/></svg>
      View Public Site
    </a>
    <?= htmlspecialchars($user['role']) ?>
  </div>
</div>
