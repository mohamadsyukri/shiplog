<?php

declare(strict_types=1);

namespace App\Http\Controllers\Board;

use App\Http\Controllers\Controller;
use App\Services\Board\BoardService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class BoardController extends Controller
{
    public function __invoke(Request $request, BoardService $board): View
    {
        $validated = $request->validate(['before' => ['nullable', 'date_format:Y-m-d']]);
        $before = isset($validated['before']) ? CarbonImmutable::parse($validated['before']) : null;

        return view('modules.board.index', ['page' => $board->page($before), 'before' => $before]);
    }
}
