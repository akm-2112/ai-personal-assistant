<?php

namespace Modules\CvWriter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\CvWriter\Models\KnowledgeFile;
use Modules\CvWriter\Services\KnowledgeIngestionService;

class KnowledgeFileController extends Controller
{
    public function __construct(
        private KnowledgeIngestionService $ingestion
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $files = KnowledgeFile::latest()->get();

        return view('cvwriter::knowledge.index', compact('files'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('cvwriter::knowledge.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'raw_content' => 'required|string',
        ]);

        $file = KnowledgeFile::create($data);

        // Ingest immediately after save
        $this->ingestion->ingest($file);

        return redirect()->route('cvwriter.knowledge.index')
            ->with('success', "'{$file->title}' saved and ingested.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KnowledgeFile $knowledgeFile): View
    {
        return view('cvwriter::knowledge.form', ['file' => $knowledgeFile]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KnowledgeFile $knowledgeFile): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'raw_content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $knowledgeFile->update($data);

        // Re-ingest because content changed
        $this->ingestion->ingest($knowledgeFile);

        return redirect()->route('cvwriter.knowledge.index')
            ->with('success', "'{$knowledgeFile->title}' updated and re-ingested.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KnowledgeFile $knowledgeFile): RedirectResponse
    {
        $title = $knowledgeFile->title;
        $knowledgeFile->delete(); // chunks cascade delete

        return redirect()->route('cvwriter.knowledge.index')
            ->with('success', "'{$title}' deleted.");
    }

    /**
     * Manually trigger re-ingestion for a knowledge file.
     */
    public function reIngest(KnowledgeFile $knowledgeFile): RedirectResponse
    {
        $this->ingestion->ingest($knowledgeFile);

        return back()->with('success', "Re-ingested '{$knowledgeFile->title}'.");
    }
}
