<?php

namespace App\Http\Controllers\Panelist;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Modules\Documents\Actions\SaveManuscriptPageMapping;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DocumentPageMappingController extends Controller
{
    public function __invoke(Request $request, Document $document, DocumentReviewerAccess $access, SaveManuscriptPageMapping $save): JsonResponse
    {
        abort_unless($request->user()->can('review', $document) && $access->canCommentAsAssignedPanelist($request->user(), $document), 403);
        $data = $request->validate([
            'body_start' => ['required', 'integer', 'min:1', 'max:10000'],
            'preliminary_labels' => ['present', 'array', 'max:9999'],
            'preliminary_labels.*' => ['required', 'string', 'max:40', 'regex:/^[\pL\pN .()\-]+$/u'],
        ]);
        if (count($data['preliminary_labels']) !== (int) $data['body_start'] - 1) {
            throw ValidationException::withMessages(['preliminary_labels' => 'Enter one label for each preview page before manuscript Page 1.']);
        }
        $mapping = ['body_start' => (int) $data['body_start'], 'preliminary_labels' => array_values($data['preliminary_labels'])];
        $save->handle($request->user(), $document, $mapping);

        return response()->json(['mapping' => $mapping, 'message' => 'Page numbering saved for this version. Existing critiques keep their original references.']);
    }
}
