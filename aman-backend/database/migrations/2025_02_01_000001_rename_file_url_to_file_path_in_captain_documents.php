<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إعادة تسمية file_url إلى file_path: العمود يخزّن الآن مسار الملف داخل
 * القرص (S3 أو local)، وليس رابطًا عامًا مباشرًا — الروابط الفعلية تُولَّد
 * لحظيًا وبشكل مؤقت (Temporary Signed URL) عبر DocumentStorageService،
 * لأن وثائق الكباتن (بطاقة وطنية، رخصة) بيانات حسّاسة يجب ألا تُخزَّن
 * كروابط عامة دائمة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captain_documents', function (Blueprint $table) {
            $table->renameColumn('file_url', 'file_path');
        });
    }

    public function down(): void
    {
        Schema::table('captain_documents', function (Blueprint $table) {
            $table->renameColumn('file_path', 'file_url');
        });
    }
};
