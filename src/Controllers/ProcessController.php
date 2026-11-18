<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Response;
use PDO;
use Exception;

class ProcessController extends BaseController
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $query = "
            SELECT 
                pid,
                datname,
                usename,
                client_addr,
                client_port,
                backend_start,
                xact_start,
                query_start,
                state_change,
                wait_event_type,
                wait_event,
                state,
                query,
                ROUND(EXTRACT(EPOCH FROM (clock_timestamp() - query_start))::numeric, 2) AS duration_seconds
            FROM pg_stat_activity
            ORDER BY state = 'active' DESC, duration_seconds DESC NULLS LAST
        ";

        try {
            $processes = $this->pdo->query($query)->fetchAll() ?: [];
        } catch (Exception $e) {
            $processes = [];
        }

        $this->render('process.list', [
            'processes' => $processes,
            'pageTitle' => 'Server Activity & Processes',
        ]);
    }

    public function terminate(Request $request): void
    {
        $this->requireAuth();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/processes');
        }

        $pid = (int)$request->post('pid', 0);
        $action = (string)$request->post('action', 'terminate');

        if ($pid <= 0) {
            $this->session->flash('error', 'Invalid PID.');
            Response::redirect('/processes');
        }

        try {
            if ($action === 'cancel') {
                $stmt = $this->pdo->prepare('SELECT pg_cancel_backend(:pid)');
                $stmt->execute(['pid' => $pid]);
                $this->session->flash('success', "Query on PID {$pid} cancelled.");
            } else {
                $stmt = $this->pdo->prepare('SELECT pg_terminate_backend(:pid)');
                $stmt->execute(['pid' => $pid]);
                $this->session->flash('success', "Process PID {$pid} terminated.");
            }
        } catch (Exception $e) {
            $this->session->flash('error', "Failed: " . $e->getMessage());
        }

        Response::redirect('/processes');
    }
}
