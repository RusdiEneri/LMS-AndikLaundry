<?php

namespace App\Http\Controllers\Owner;

/**
 * Compatibility alias controller for Owner Executive Dashboard.
 *
 * Extends DashboardController to ensure any external or legacy references
 * to OwnerDashboardController resolve seamlessly without modifying or
 * duplicating core dashboard logic.
 */
class OwnerDashboardController extends DashboardController
{
    // Inherits index(), exportCsv(), and helper methods from DashboardController.
}
