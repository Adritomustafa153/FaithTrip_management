<?php
session_start();
$_SESSION['test'] = 'Working';
echo session_save_path() . '<br>';
echo '<pre>'; print_r($_SESSION); echo '</pre>';