<?php
function icon($name, $extra = '') {
  $name = preg_replace('/[^a-z0-9\- ]/i', '', (string)$name);
  $extra = preg_replace('/[^a-z0-9\-\_ ]/i', '', (string)$extra);
  return '<i class="fa-solid fa-' . $name . ' ' . $extra . '"></i>';
}
