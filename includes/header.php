<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electronics POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: #f8f9fa; 
            margin: 0;
            padding: 0;
            padding-top: 56px; /* Account for fixed navbar */
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
        
        .navbar {
            overflow: hidden;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
            width: 100% !important;
        }
        
        .navbar-nav {
            overflow: hidden;
        }
        
        /* Ensure navbar stays on top */
        .navbar.fixed-top {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
        }
        
        /* Fixed sidebar styling - adjust for fixed navbar */
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
        
        /* Main content area - adjust for fixed navbar */
        .main-content {
            margin-left: 250px;
            margin-top: 76px; /* Increased margin to account for fixed navbar */
            min-height: calc(100vh - 76px);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        /* Profile dropdown styling */
        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            left: auto;
            min-width: 260px;
            z-index: 1050;
            margin-top: 0.5rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.75rem;
            padding: 0.75rem 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }
        
        .profile-dropdown .dropdown-item {
            padding: 0.875rem 1.5rem;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            margin: 0.25rem 0.5rem 0.25rem 0.75rem;
            font-weight: 600;
            font-size: 0.95em;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
            color: #2c3e50;
            text-shadow: 0 0 1px rgba(255, 255, 255, 0.8);
            cursor: pointer;
            display: block;
            text-decoration: none;
        }
        
        /* Compact logout button */
        .profile-dropdown .dropdown-item.text-danger {
            padding: 0.5rem 1rem;
            margin: 0.125rem 0.375rem 0.125rem 0.5rem;
        }
        
        .profile-dropdown .dropdown-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 193, 7, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .profile-dropdown .dropdown-item:hover::before {
            left: 100%;
        }
        
        /* Profile items hover effect (non-logout) */
        .profile-dropdown .dropdown-item:not(.text-danger):hover {
            background-color: #f8f9fa;
            color: #212529;
            transform: translateX(2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Enhanced logout button styling */
        .profile-dropdown .dropdown-item.text-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: 1px solid #e74c3c;
            font-weight: 600;
            font-size: 0.85em;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            padding: 0.625rem 1rem;
        }
        
        .profile-dropdown .dropdown-item.text-danger:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            color: white;
            transform: translateX(3px) translateY(-1px);
            box-shadow: 0 6px 12px rgba(220, 53, 69, 0.4);
            border-color: #a71e2a;
        }
        
        .profile-dropdown .dropdown-item.text-danger:active {
            transform: translateX(1px) translateY(0);
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        
        .profile-dropdown .dropdown-divider {
            margin: 0.75rem 1rem;
            border-color: rgba(0, 0, 0, 0.1);
            opacity: 0.6;
        }
        
        .navbar-nav .dropdown-toggle::after {
            display: none;
        }
        
        .navbar-nav .nav-link {
            transition: all 0.2s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: #ffc107 !important;
        }
        
        .navbar-nav .dropdown-toggle:hover {
            color: #ffc107 !important;
        }
        
        .navbar-nav .dropdown-toggle:focus {
            color: #ffc107 !important;
            box-shadow: none;
        }
        
        /* Profile dropdown specific styling */
        #profileDropdown {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            position: relative;
            overflow: hidden;
            font-size: 0.9em;
            z-index: 1001;
        }
        
        #profileDropdown .d-flex {
            overflow: hidden;
        }
        
        #profileDropdown i {
            font-size: 0.9em !important;
        }
        
        #profileDropdown span {
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        
        #profileDropdown::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid rgba(255, 255, 255, 0.95);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        #profileDropdown:hover::after {
            opacity: 1;
        }
        
        #profileDropdown:hover {
            background-color: rgba(255, 193, 7, 0.15);
            border-radius: 0.5rem;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.2);
            color: #ffc107 !important;
        }
        
        #profileDropdown:active {
            transform: scale(0.98) translateY(0);
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }
        
        #profileDropdown:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
            outline: none;
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        /* Bootstrap dropdown visibility - ensure proper display */
        .dropdown-menu {
            z-index: 1050;
            display: none;
        }
        
        /* Ensure Bootstrap dropdown is always visible when shown */
        .dropdown-menu.show,
        .dropdown-menu[data-bs-popper="static"] {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
        }
        
        /* Force dropdown to show when Bootstrap class is applied */
        .dropdown-menu.show {
            display: block !important;
        }
        
        /* Ensure dropdown container is properly positioned */
        .dropdown {
            position: relative;
        }
        
        /* Ensure dropdown menu positioning works correctly */
        .dropdown-menu-end {
            right: 0;
            left: auto;
        }
        
        /* Profile dropdown specific positioning */
        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            left: auto;
            min-width: 260px;
            z-index: 1050;
            margin-top: 0.5rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.75rem;
            padding: 0.75rem 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }
        
        /* Ensure profile dropdown is always visible when shown */
        .profile-dropdown.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        /* Additional positioning fixes */
        .navbar-nav .dropdown {
            position: relative;
        }
        
        .navbar-nav .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            left: auto;
            margin-top: 0.5rem;
        }
        
        /* Ensure dropdown items are properly styled */
        .profile-dropdown .dropdown-item {
            padding: 0.875rem 1.5rem;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            margin: 0.25rem 0.5rem 0.25rem 0.75rem;
            font-weight: 600;
            font-size: 0.95em;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
            color: #2c3e50;
            text-shadow: 0 0 1px rgba(255, 255, 255, 0.8);
            cursor: pointer;
            display: block;
            text-decoration: none;
        }
        
        /* Ensure dropdown divider is visible */
        .profile-dropdown .dropdown-divider {
            margin: 0.75rem 1rem;
            border-color: rgba(0, 0, 0, 0.1);
            opacity: 0.6;
            height: 1px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Force dropdown visibility when show class is present */
        .dropdown-menu.show,
        .profile-dropdown.show {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            z-index: 1050 !important;
        }
        
        /* Ensure navbar has proper z-index */
        .navbar {
            z-index: 1030;
            position: relative;
        }
        
        /* Ensure dropdown container has proper positioning */
        .navbar-nav .nav-item.dropdown {
            position: relative;
        }
        
        /* Additional important rules for dropdown visibility */
        .dropdown-menu.show {
            display: block !important;
        }
        
        .profile-dropdown.show {
            display: block !important;
        }
        
        /* Ensure dropdown menu is not hidden by default */
        .dropdown-menu {
            display: none;
        }
        
        .dropdown-menu.show {
            display: block !important;
        }
        
        /* Force profile dropdown to be visible when shown */
        .profile-dropdown.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Ensure proper navbar positioning */
        .navbar.fixed-top {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
            width: 100% !important;
        }
        
        /* Ensure dropdown positioning is perfect */
        .navbar-nav .nav-item.dropdown {
            position: relative !important;
        }
        
        .navbar-nav .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            margin-top: 0.5rem !important;
            z-index: 1050 !important;
        }
        
        /* Profile dropdown specific positioning */
        .profile-dropdown {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            min-width: 260px;
            z-index: 1050 !important;
            margin-top: 0.5rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.75rem;
            padding: 0.75rem 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }
        
        /* Dropdown animation */
        @keyframes dropdownSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Profile dropdown toggle animation */
        @keyframes profileTogglePulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
            100% {
                transform: scale(1);
            }
        }
        
        #profileDropdown:active {
            animation: profileTogglePulse 0.15s ease-in-out;
        }
        
        /* Smooth dropdown transitions */
        .dropdown-menu {
            transition: all 0.2s ease-in-out;
        }
        
        .profile-dropdown {
            transition: all 0.2s ease-in-out;
        }
        
        /* Dropdown show animation */
        .dropdown-menu.show,
        .profile-dropdown.show {
            animation: dropdownSlideIn 0.2s ease-out;
        }
        
        /* Profile dropdown icon enhancements */
        .profile-dropdown .dropdown-item i {
            transition: all 0.3s ease;
            margin-right: 0.875rem;
            font-size: 1.2em;
            width: 22px;
            text-align: center;
            color: #6c757d;
        }
        
        /* Smaller icon for logout button */
        .profile-dropdown .dropdown-item.text-danger i {
            font-size: 1em;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .profile-dropdown .dropdown-item:hover i {
            transform: scale(1.1);
            color: #ffc107;
        }
        
        .profile-dropdown .dropdown-item.text-danger:hover i {
            color: white;
            transform: scale(1.1);
        }
        
        .profile-dropdown .dropdown-item span {
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #2c3e50;
        }
        
        /* Smaller text for logout button */
        .profile-dropdown .dropdown-item.text-danger span {
            font-size: 0.85em;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        
        /* Enhanced hover effects for profile items (non-logout) */
        .profile-dropdown .dropdown-item:not(.text-danger):hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #212529;
            transform: translateX(3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 193, 7, 0.3);
        }
        

        
        /* Ensure profile links are always clickable */
        .dropdown-item[data-profile-link="true"] {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .dropdown-item[data-profile-link="true"]:hover {
            background-color: #f8f9fa;
            color: #212529;
            transform: translateX(2px);
        }
        
        .dropdown-item[data-profile-link="true"]:active {
            background-color: #e9ecef;
            transform: translateX(1px);
        }
        
        /* Ensure logout link is always clickable */
        .dropdown-item[data-logout-link="true"] {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .dropdown-item[data-logout-link="true"]:hover {
            background-color: #dc3545;
            color: white;
            transform: translateX(2px);
        }
        
        .dropdown-item[data-logout-link="true"]:active {
            background-color: #c82333;
            transform: translateX(1px);
        }
        
        /* Remove duplicate hover effects for logout button */
        .profile-dropdown .dropdown-item.text-danger:hover {
            background: linear-gradient(135deg, #c0392b, #a93226) !important;
            color: white !important;
            transform: translateX(3px) translateY(-1px) !important;
            box-shadow: 0 6px 12px rgba(231, 76, 60, 0.4) !important;
            border-color: #a93226 !important;
        }
        
        /* Enhanced clickable elements */
        .nav-link, .btn, .dropdown-item, .navbar-brand {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link:hover, .btn:hover, .dropdown-item:hover {
            transform: translateY(-1px);
        }
        
        .nav-link:active, .btn:active, .dropdown-item:active {
            transform: translateY(0);
        }
        
        /* Notification badge enhancement */
        .badge {
            transition: all 0.2s ease;
        }
        
        .badge:hover {
            transform: scale(1.1);
        }
        
        /* Sidebar toggle button enhancement */
        .navbar-toggler {
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .navbar-toggler:hover {
            border-color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        
        /* Global clickable elements enhancement */
        a, button, input[type="submit"], input[type="button"], .btn, .nav-link, .dropdown-item {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        a:hover, button:hover, input[type="submit"]:hover, input[type="button"]:hover, .btn:hover, .nav-link:hover, .dropdown-item:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }
        
        a:active, button:active, input[type="submit"]:active, input[type="button"]:active, .btn:active, .nav-link:active, .dropdown-item:active {
            transform: translateY(0);
        }
        
        /* Table row hover effects */
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(255, 193, 7, 0.1);
            transform: scale(1.01);
        }
        
        /* Card hover effects */
        .card {
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        /* Form control focus states */
        .form-control:focus, .form-select:focus {
            border-color: #ffc107 !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
        }
        
        /* Button group enhancements */
        .btn-group .btn {
            transition: all 0.2s ease;
        }
        
        .btn-group .btn:hover {
            z-index: 2;
        }
        
        /* Pagination enhancements */
        .pagination .page-link {
            transition: all 0.2s ease;
        }
        
        .pagination .page-link:hover {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        
        /* Alert enhancements */
        .alert {
            transition: all 0.3s ease;
        }
        
        .alert:hover {
            transform: translateX(2px);
        }
        
        /* Modal enhancements */
        .modal-content {
            transition: all 0.3s ease;
        }
        
        .modal.show .modal-content {
            transform: scale(1.02);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                top: 56px; /* Keep consistent with navbar height */
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                margin-top: 76px; /* Keep consistent margin for mobile */
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 56px; /* Start below navbar */
                left: 0;
                width: 100%;
                height: calc(100vh - 56px);
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            /* Mobile profile dropdown adjustments */
            .profile-dropdown {
                min-width: 220px;
                margin-top: 0.25rem;
            }
            
            #profileDropdown {
                padding: 0.25rem 0.5rem;
                font-size: 0.8em;
                min-width: auto;
            }
            
            #profileDropdown span {
                max-width: 100px;
            }
            
            #profileDropdown .d-flex {
                min-width: auto;
            }
            
            .profile-dropdown .dropdown-item {
                padding: 0.75rem 1rem;
                margin: 0.125rem 0.5rem;
            }
            
            /* Mobile logout button adjustments */
            .profile-dropdown .dropdown-item.text-danger {
                padding: 0.375rem 0.75rem;
                margin: 0.125rem 0.375rem;
                font-size: 0.8em;
                font-weight: 600;
            }
            
            /* Ensure navbar stays fixed on mobile */
            .navbar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1030 !important;
                width: 100% !important;
            }
        }
        
        /* WhatsApp button styling */
        .btn-whatsapp {
            background-color: #25D366 !important;
            border-color: #25D366 !important;
            color: white !important;
        }
        
        .btn-whatsapp:hover {
            background-color: #128C7E !important;
            border-color: #128C7E !important;
            color: white !important;
        }
        
        .btn-whatsapp:focus {
            background-color: #25D366 !important;
            border-color: #25D366 !important;
            color: white !important;
            box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25);
        }
        
        .btn-whatsapp.disabled {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: #adb5bd !important;
            cursor: not-allowed;
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
        
        /* Chevron icon styling and transitions */
        .sidebar .bi-chevron-right {
            transition: transform 0.3s ease;
            font-size: 0.8em;
        }
        
        /* Smooth collapse animation */
        .collapse {
            transition: all 0.3s ease;
        }
        
        /* Submenu styling */
        .sidebar .collapse .nav-link {
            padding: 8px 20px 8px 40px;
            font-size: 0.9em;
            border-left: 2px solid transparent;
        }
        
        .sidebar .collapse .nav-link:hover {
            border-left-color: #ffc107;
            background: rgba(255, 193, 7, 0.1);
        }
        
        .sidebar .collapse .nav-link.active {
            border-left-color: #ffc107;
            background: rgba(255, 193, 7, 0.2);
        }
        
        /* Ensure submenu items are properly indented */
        .sidebar .collapse .nav {
            margin-left: 0;
        }
        
        /* Better spacing for submenu items */
        .sidebar .collapse .nav-item {
            margin-bottom: 2px;
        }
        
        /* Active state for parent menu when submenu is active */
        .sidebar .nav-link[aria-expanded="true"] {
            background: #343a40;
            color: #ffc107;
            border-left-color: #ffc107;
        }
    </style>
</head>
<body>
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
$unread_count = 0;
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetchColumn();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
  <div class="container-fluid">
    <button class="navbar-toggler d-md-none" type="button" id="sidebarToggle">
      <span class="navbar-toggler-icon"></span>
    </button>
                    <a class="navbar-brand fw-bold" href="<?= $base_url ?>dashboard.php">Electronics POS</a>
    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item me-3">
        <a class="nav-link position-relative" href="<?= $base_url ?>notifications.php">
          <i class="bi bi-bell fs-5"></i>
          <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7em;">
              <?= $unread_count ?>
            </span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true" title="Click to open profile menu">
          <div class="d-flex align-items-center">
            <i class="bi bi-person-circle fs-5 me-2"></i>
            <span class="fw-bold"><?= htmlspecialchars(current_user() ?? 'User') ?></span>
            <!-- <i class="bi bi-chevron-down ms-1 fs-6"></i> -->
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end profile-dropdown" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="<?= $base_url ?>profile.php" tabindex="0" data-profile-link="true">
            <i class="bi bi-person-circle"></i>
            <span>Profile & Settings</span>
          </a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= $base_url ?>logout.php" tabindex="0" data-logout-link="true">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
          </a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>