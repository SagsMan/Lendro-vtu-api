<?php
/**
 * POST /api/v1/auth/logout
 *
 * Destroy the current session.
 */
require_once __DIR__ . '/../db.php';

session_unset();
session_destroy();

sendJson(['status' => 'success', 'message' => 'You have been logged out.']);
