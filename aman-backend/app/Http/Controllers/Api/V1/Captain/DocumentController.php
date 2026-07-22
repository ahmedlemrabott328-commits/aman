<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\UploadDocumentRequest;
use App\Http\Resources\CaptainDocumentResource;
use App\Services\DocumentStorageService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(private DocumentStorageService $storage)
    {
    }

    /** قائمة وثائق الكابتن الحالي مع حالتها */
    public function index(Request $request)
    {
        $documents = $request->user()->documents()->latest()->get();

        return $this->success(CaptainDocumentResource::collection($documents));
    }

    /**
     * رفع وثيقة جديدة (رخصة، بطاقة وطنية، استمارة، تأمين).
     * كل رفع جديد لنفس document_type يُنشئ سجلاً جديدًا بحالة "pending" (وليس تعديل
     * القديم مباشرة)، حفاظًا على أثر تاريخي لكل محاولة رفع — الإدارة تراجع الأحدث دائمًا.
     */
    public function store(UploadDocumentRequest $request)
    {
        $captain = $request->user();

        $path = $this->storage->store($request->file('file'), $captain->id, $request->document_type);

        $document = $captain->documents()->create([
            'document_type' => $request->document_type,
            'file_path' => $path,
            'status' => 'pending',
            'expires_at' => $request->expires_at,
        ]);

        return $this->success(new CaptainDocumentResource($document), 'تم رفع الوثيقة، بانتظار مراجعة الإدارة', 201);
    }
}
