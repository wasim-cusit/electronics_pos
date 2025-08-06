<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothing POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: #f8f9fa; 
            margin: 0;
            padding: 0;
        }
        
        /* Fixed sidebar styling */
        .sidebar {
            position: fixed;
            top: 56px; /* Height of the navbar */
            left: 0;
            width: 250px;
            height: calc(100vh - 56px);
            background: #212529;
            color: #fff;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover {
            background: #343a40;
            color: #ffc107;
            border-left-color: #ffc107;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: #343a40;
            color: #ffc107;
            border-left-color: #ffc107;
            font-weight: 600;
        }
        
        /* Main content area */
        .main-content {
            margin-left: 250px;
            margin-top: 56px;
            min-height: calc(100vh - 56px);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        /* Navbar styling */
        .navbar-brand {
            color: #ffc107 !important;
        }
        
        .profile-dropdown {
            min-width: 180px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        /* Scrollbar styling for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #343a40;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #ffc107;
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #e0a800;
        }
    </style>
</head>
<body>
<?php
require_once __DIR__ . '/config.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
  <div class="container-fluid">
    <button class="navbar-toggler d-md-none" type="button" id="sidebarToggle">
      <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand fw-bold" href="<?= $base_url ?>dashboard.php">Clothing POS</a>
    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item me-3">
        <a class="nav-link position-relative" href="<?= $base_url ?>notifications.php">
          <i class="bi bi-bell fs-5"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-circle fs-5 me-1"></i>
          <?= htmlspecialchars(current_user() ?? 'User') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end profile-dropdown" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="<?= $base_url ?>profile.php"><i class="bi bi-person me-2"></i>Profile/Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= $base_url ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>