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
        $validated = $request->validate([
            'before' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $before = isset($validated['before']) ? CarbonImmutable::parse($validated['before']) : null;
        // TrimStrings and ConvertEmptyStringsToNull have run: an empty search box arrives as null.
        $search = $validated['q'] ?? null;

        return view('modules.board.index', ['page' => $board->page($before, $search), 'before' => $before, 'search' => $search]);
    }
}
