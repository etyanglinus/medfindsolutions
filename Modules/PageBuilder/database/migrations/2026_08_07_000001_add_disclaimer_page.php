<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        if (DB::table('customizeable_pages')->where('slug', 'disclaimer')->exists()) {
            return;
        }

        $now = now();
        $pageId = DB::table('customizeable_pages')->insertGetId([
            'slug' => 'disclaimer', 'status' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $languages = DB::table('languages')->pluck('code');
        foreach ($languages as $code) {
            DB::table('customizable_page_translations')->insert([
                'customizeable_page_id' => $pageId, 'lang_code' => $code, 'title' => 'Disclaimer',
                'description' => '<p>Content for this disclaimer has not yet been added.</p>', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void {
        $pageId = DB::table('customizeable_pages')->where('slug', 'disclaimer')->value('id');
        if ($pageId) {
            DB::table('customizable_page_translations')->where('customizeable_page_id', $pageId)->delete();
            DB::table('customizeable_pages')->where('id', $pageId)->delete();
        }
    }
};
