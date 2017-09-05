<?php
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
if (file_exists(__DIR__ . '/settings.memcache.php')) {
  include __DIR__ . '/settings.memcache.php';
}
if (file_exists(__DIR__ . '/settings.db.php')) {
  include __DIR__ . '/settings.db.php';
}
if (file_exists(__DIR__ . '/settings.dev.php')) {
  include __DIR__ . '/settings.dev.php';
}
if (file_exists(__DIR__ . '/settings.stage.php')) {
  include __DIR__ . '/settings.stage.php';
}
if (file_exists(__DIR__ . '/settings.prod.php')) {
  include __DIR__ . '/settings.prod.php';
}
if (file_exists(__DIR__ . '/settings.mine.php')) {
  include __DIR__ . '/settings.mine.php';
}
