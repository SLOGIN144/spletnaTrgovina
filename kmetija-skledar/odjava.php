<?php
require_once __DIR__ . '/includes/auth.php';

// Odjava samo prek obrazca (POST) z veljavnim CSRF žetonom
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('index.php');
}

$name = current_user()['first_name'] ?? '';

end_session();   // izprazni $_SESSION, izbriše piškotek in uniči sejo na strežniku

session_start(); // nova, prazna seja samo za sporočilo o odjavi
set_flash('success', $name ? "Nasvidenje, $name! Uspešno ste odjavljeni." : 'Odjavljeni ste.');
redirect('index.php');
