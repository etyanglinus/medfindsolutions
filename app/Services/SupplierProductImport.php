<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;

/**
 * Bulk add/update supplier products from a CSV file (one product per row).
 * A row whose name matches an existing product of this supplier updates it; other rows create
 * new products while the package's product limit allows. Every imported product goes back to
 * "pending" so an admin approves it before it is shown, exactly like the single-product form.
 */
class SupplierProductImport
{
    public const COLUMNS = ['name', 'brand', 'model', 'category', 'availability', 'description', 'specifications'];
    public const MAX_ROWS = 2000;

    /** The downloadable template: header row plus one example. */
    public static function template(): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, self::COLUMNS);
        fputcsv($out, ['Digital blood pressure monitor', 'Omron', 'M3', 'Diagnostic equipment', 'In stock', 'Upper-arm monitor with memory for 60 readings.', 'Cuff 22-42 cm; 4 x AA batteries']);
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    /**
     * @param  ?int  $limit  products_limit from the package (0 = unlimited)
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function import(MedicalSupplier $supplier, UploadedFile $file, ?int $limit, array $entitlements): array
    {
        $result = ['created' => 0, 'updated' => 0, 'errors' => []];
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) return $result + ['errors' => [__('The file could not be read.')]];

        if (fread($handle, 3) !== "\xEF\xBB\xBF") rewind($handle); // Excel's "CSV UTF-8" starts with a byte-order mark
        $header = fgetcsv($handle);
        if ($header === false) { fclose($handle); $result['errors'][] = __('The file is empty.'); return $result; }
        $header = array_map(fn ($h) => Str::snake(trim((string) $h)), $header);
        if (! in_array('name', $header, true)) { fclose($handle); $result['errors'][] = __('The first row must contain the column headings from the template (at least "name").'); return $result; }

        $categories = ! empty($entitlements['categories'])
            ? $supplier->categories()->where('is_active', true)->get()->mapWithKeys(fn ($c) => [mb_strtolower(trim($c->name)) => $c->id])
            : collect();
        $existing = $supplier->products()->get(['id', 'name', 'slug'])->keyBy(fn ($p) => mb_strtolower(trim($p->name)));
        $slugs = $existing->pluck('slug')->flip();
        $count = $existing->count();
        $line = 1;
        $seen = [];

        DB::transaction(function () use ($handle, $header, $supplier, $limit, $entitlements, $categories, $existing, &$slugs, &$count, &$line, &$seen, &$result) {
            while (($cells = fgetcsv($handle)) !== false) {
                $line++;
                if ($cells === [null] || count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) continue; // blank line
                if ($line - 1 > self::MAX_ROWS) { $result['errors'][] = __('Only the first :max rows were imported. Split larger files.', ['max' => self::MAX_ROWS]); break; }

                $row = [];
                foreach ($header as $i => $key) $row[$key] = trim((string) ($cells[$i] ?? ''));
                $name = $row['name'] ?? '';
                $problem = match (true) {
                    $name === '' => __('name is missing'),
                    mb_strlen($name) > 255 => __('name is longer than 255 characters'),
                    mb_strlen($row['brand'] ?? '') > 255, mb_strlen($row['model'] ?? '') > 255, mb_strlen($row['availability'] ?? '') > 255 => __('brand, model and availability must be 255 characters or less'),
                    mb_strlen($row['description'] ?? '') > 5000, mb_strlen($row['specifications'] ?? '') > 5000 => __('description and specifications must be 5000 characters or less'),
                    isset($seen[mb_strtolower($name)]) => __('the same product name appears earlier in the file (row :row)', ['row' => $seen[mb_strtolower($name)]]),
                    default => null,
                };
                if ($problem) { $result['errors'][] = __('Row :row: :problem.', ['row' => $line, 'problem' => $problem]); continue; }
                $seen[mb_strtolower($name)] = $line;

                $data = [
                    'name' => $name,
                    'model' => $row['model'] ?? null ?: null,
                    'availability' => $row['availability'] ?? null ?: null,
                    'description' => $row['description'] ?? null ?: null,
                    'specifications' => $row['specifications'] ?? null ?: null,
                    'approval_status' => 'pending', 'admin_note' => null, 'is_published' => false,
                ];
                if (! empty($entitlements['brands'])) $data['brand'] = $row['brand'] ?? null ?: null;
                if (($row['category'] ?? '') !== '' && ! empty($entitlements['categories'])) {
                    $categoryId = $categories[mb_strtolower($row['category'])] ?? null;
                    if (! $categoryId) { $result['errors'][] = __('Row :row: category ":category" is not one of your approved categories; the product was saved without a category.', ['row' => $line, 'category' => $row['category']]); }
                    $data['medical_supplier_category_id'] = $categoryId;
                }

                if ($product = $existing[mb_strtolower($name)] ?? null) {
                    $product->update($data);
                    $result['updated']++;
                    continue;
                }
                if ($limit !== null && $limit > 0 && $count >= $limit) {
                    $result['errors'][] = __('Row :row: not added, your package allows :limit products. Upgrade to add more.', ['row' => $line, 'limit' => $limit]);
                    continue;
                }
                $base = Str::slug($name) ?: Str::random(8);
                $slug = $base; $i = 1;
                while (isset($slugs[$slug])) $slug = $base.'-'.$i++;
                $slugs[$slug] = true;
                $supplier->products()->create($data + ['slug' => $slug, 'additional_images' => [], 'is_featured' => false, 'catalogue_is_public' => false]);
                $count++;
                $result['created']++;
            }
        });
        fclose($handle);
        return $result;
    }
}
