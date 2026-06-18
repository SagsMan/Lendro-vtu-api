<?php
  // GET/POST /api/v1/client/services  — return all active services in frontend format
  require_once __DIR__ . '/../db.php';
  requireAuth();

  $svc = json_decode(getAllServices($db), true);
  toJSON(["status" => "success", "data" => $svc['data'] ?? []]);
  