<?php
$current = basename($_SERVER['SCRIPT_NAME']);
function nav_active($page, $current) { return $page === $current ? 'active' : ''; }
?>
<div class="sidebar">
  <div class="nav-heading">
    <span>Navigation</span>
    <span class="toggle-icon">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16V4M7 4L3 8M7 4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
    </span>
  </div>

  <div class="section-label">KPTO-KDLD</div>

  <div class="section-label">GENERAL</div>
  <a class="nav-item <?= nav_active('dashboard.php', $current) ?>" href="dashboard.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>
    Dashboard
  </a>
  <a class="nav-item <?= nav_active('dialects.php', $current) ?>" href="dialects.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"/><path d="M2 12h20"/><path d="M12 4c2.5 2.5 4 6 4 8s-1.5 5.5-4 8c-2.5-2.5-4-6-4-8s1.5-5.5 4-8z"/></svg>
    Dialects
  </a>
  <a class="nav-item <?= nav_active('words.php', $current) ?>" href="words.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    Dictionary Entries
  </a>
  <a class="nav-item <?= nav_active('categories.php', $current) ?>" href="categories.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Categories
  </a>
  <a class="nav-item <?= nav_active('parts_of_speech.php', $current) ?>" href="parts_of_speech.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
    Parts of Speech
  </a>
  <a class="nav-item <?= nav_active('contributors.php', $current) ?>" href="contributors.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Contributors
  </a>
  <a class="nav-item <?= nav_active('feedback.php', $current) ?>" href="feedback.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    User Feedback
  </a>
  <a class="nav-item <?= nav_active('users.php', $current) ?>" href="users.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6"/></svg>
    Admin Users
  </a>
  <a class="nav-item <?= nav_active('results.php', $current) ?>" href="results.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l4-6 4 3 5-8"/></svg>
    Results
  </a>
  <a class="nav-item" href="logout.php">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
    Sign Out
  </a>
</div>
