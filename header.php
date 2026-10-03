<?php
require_once __DIR__ . '/../config/auth.php';

// دڵنیابوونەوە لە چوونی ژوورەوەی بەکارهێنەر بۆ سەرجەم لاپەڕەکانی سیستەم
requireLogin();
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستەمێ کلینیکا دکتورێ ددانان</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">