<?php
// templates/admin_header.php
$admin_page_title = isset($page_title) ? htmlspecialchars($page_title) . ' - Dari Admin' : 'Dari Admin Panel';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $admin_page_title; ?></title>
    <link rel="icon" href="<?php echo ASSETS_PATH; ?>/images/logo.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin.css">

    <?php if (isset($admin_page_specific_css)): ?>
        <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin/<?php echo htmlspecialchars($admin_page_specific_css); ?>">
    <?php endif; ?>
    
    <script>
        window.basePath = '<?php echo BASE_PATH; ?>';
    </script>
</head>
<body class="bg-light">

    <div class="container-fluid">
        <div class="row">
            <?php require_once TEMPLATES_PATH . '/admin_sidebar.php'; // Include the sidebar ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">