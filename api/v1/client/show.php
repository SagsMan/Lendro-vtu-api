<?php
  /**
   * POST /api/v1/client/show
   * Get services filtered by type / network / category.
   */
  require_once __DIR__ . '/../db.php';

  $userid  = requireAuth();
  $type     = $_POST['type']     ?? $_GET['type']     ?? null;
  $network  = $_POST['network']  ?? $_GET['network']  ?? null;
  $category = $_POST['category'] ?? $_GET['category'] ?? null;

  echo getServicesBy($type, $network, $category);
  