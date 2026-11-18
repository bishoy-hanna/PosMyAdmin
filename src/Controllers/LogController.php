<?php

declare(strict_types=1);

namespace PosMyAdmin\Controllers;

use PosMyAdmin\Auth\CSRF;
use PosMyAdmin\Http\Request;
use PosMyAdmin\Http\Response;
use PosMyAdmin\Utils\Logger;

class LogController extends BaseController
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $type = (string)$request->get('type', 'error');
        $isAuth = ($type === 'auth');

        $content = $isAuth ? Logger::getAuthContent() : Logger::getContent();
        $fileSize = $isAuth ? Logger::getAuthSize() : Logger::getSize();
        $logPath = $isAuth ? Logger::getAuthLogPath() : Logger::getLogPath();

        $this->render('logs.index', [
            'type' => $type,
            'content' => $content,
            'fileSize' => $fileSize,
            'logPath' => $logPath,
            'errorSize' => Logger::getSize(),
            'authSize' => Logger::getAuthSize(),
            'pageTitle' => $isAuth ? 'Authentication & Rate Limit Logs' : 'Application Error Logs',
        ]);
    }

    public function download(Request $request): void
    {
        $this->requireAuth();

        $type = (string)$request->get('type', 'error');
        $isAuth = ($type === 'auth');

        $content = $isAuth ? Logger::getAuthContent() : Logger::getContent();
        $prefix = $isAuth ? 'posmyadmin_auth_' : 'posmyadmin_error_';

        Response::download($prefix . date('Y-m-d_His') . '.log', $content, 'text/plain');
    }

    public function clear(Request $request): void
    {
        $this->requireAuth();

        if (!CSRF::validate($request->csrfToken())) {
            $this->session->flash('error', 'Invalid CSRF token.');
            Response::redirect('/logs');
        }

        $type = (string)$request->post('type', 'error');
        if ($type === 'auth') {
            Logger::clearAuthLog();
            $this->session->flash('success', 'Authentication & rate limit log cleared.');
            Response::redirect('/logs?type=auth');
        } else {
            Logger::clear();
            $this->session->flash('success', 'Application error log cleared.');
            Response::redirect('/logs?type=error');
        }
    }
}
