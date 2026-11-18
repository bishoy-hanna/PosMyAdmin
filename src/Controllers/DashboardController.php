<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Database\ServerInfo;
use PosMyAdmin\Http\Request;
use Exception;

class DashboardController extends BaseController
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $serverInfoService = new ServerInfo($this->pdo);
        $overview = $serverInfoService->getOverview();

        $this->render('dashboard.index', [
            'overview' => $overview,
            'pageTitle' => 'PostgreSQL Server Dashboard',
        ]);
    }
}
