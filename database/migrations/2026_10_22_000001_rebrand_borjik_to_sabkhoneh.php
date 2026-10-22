<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings')) {
            foreach (DB::table('site_settings')->select(['id', 'value'])->get() as $setting) {
                $decoded = json_decode((string) $setting->value, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                $updated = $this->replaceBrand($decoded);

                if ($updated !== $decoded) {
                    DB::table('site_settings')->where('id', $setting->id)->update([
                        'value' => json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            }
        }

        if (Schema::hasTable('sms_templates') && Schema::hasColumn('sms_templates', 'body')) {
            DB::table('sms_templates')
                ->where('body', 'like', '%برجیک%')
                ->update(['body' => DB::raw("REPLACE(body, 'برجیک', 'صابخونه')")]);
        }

        if (Schema::hasTable('articles')) {
            $columns = array_values(array_filter(
                ['title', 'excerpt', 'body'],
                static fn (string $column): bool => Schema::hasColumn('articles', $column)
            ));

            if ($columns !== []) {
                foreach (DB::table('articles')->select(array_merge(['id'], $columns))->get() as $article) {
                    $changes = [];

                    foreach ($columns as $column) {
                        $value = $article->{$column};

                        if (is_string($value) && str_contains($value, 'برجیک')) {
                            $changes[$column] = str_replace('برجیک', 'صابخونه', $value);
                        }
                    }

                    if ($changes !== []) {
                        DB::table('articles')->where('id', $article->id)->update($changes);
                    }
                }
            }
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function replaceBrand(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace('برجیک', 'صابخونه', $value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->replaceBrand($item);
            }
        }

        return $value;
    }

    public function down(): void
    {
        // Brand changes are intentionally not reverted to avoid overwriting customized content.
    }
};
