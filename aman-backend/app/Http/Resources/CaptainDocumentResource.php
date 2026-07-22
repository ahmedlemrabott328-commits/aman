<?php

namespace App\Http\Resources;

use App\Services\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaptainDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            // رابط مؤقت وموقَّع (صالح لدقائق معدودة فقط) يُولَّد لحظيًا؛ لا نخزّن أو
            // نُعيد أبدًا رابطًا عامًا دائمًا لوثيقة هوية حساسة.
            'file_url' => $this->file_path
                ? app(DocumentStorageService::class)->temporaryUrl($this->file_path)
                : null,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
