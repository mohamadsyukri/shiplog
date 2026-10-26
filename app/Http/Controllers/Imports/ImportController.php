<?php

declare(strict_types=1);

namespace App\Http\Controllers\Imports;

use App\Enums\ImportSource;
use App\Exceptions\Imports\ImportRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\StoreImportRequest;
use App\Models\Import;
use App\Services\Imports\ImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class ImportController extends Controller
{
    private const int HISTORY_PER_PAGE = 20;

    public function __construct(private readonly ImportService $importer) {}

    public function index(): View
    {
        return view('modules.imports.index', [
            'imports' => Import::query()->with('user:id,name')->latest('id')->paginate(self::HISTORY_PER_PAGE),
        ]);
    }

    public function create(): View
    {
        return view('modules.imports.create');
    }

    public function store(StoreImportRequest $request): RedirectResponse
    {
        try {
            $result = $this->importer->import($request->fileText(), ImportSource::Upload, $request->fileName(), $request->user());
        } catch (ImportRejected $rejected) {
            // The count goes under the file field; the form lists every problem from `problems`.
            return back()->withErrors(['file' => $rejected->getMessage(), 'problems' => $rejected->errors()]);
        }

        return to_route('imports.index')->with('success', __('Imported :file: :created cards created, :replaced replaced, :lanes lanes.', [
            'file' => $request->fileName(),
            'created' => $result->entriesCreated,
            'replaced' => $result->entriesReplaced,
            'lanes' => $result->lanesUpserted,
        ]));
    }
}
