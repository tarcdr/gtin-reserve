<?php

// app/Services/MasterCatLookup.php
namespace App\Services;

use App\Models\MasterProductCat as PM;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MasterCatLookup
{
    private function cache(string $key, \Closure $cb, $ttl = 43200) // 12 ชม.
    {
        return Cache::remember("master_lookup:$key", $ttl, $cb);
    }

    /** รายการหมวดสินค้า (CATEGORY) */
    public function categories()
    {
        return $this->cache('categories', function () {
            return PM::query()
                ->select([
                    DB::raw('TRIM(CODE_PRODUCT_CAT) as code'),
                    DB::raw('MAX(TRIM(DESC_PRODUCT_CAT)) as name'),
                ])
                ->whereNotNull('CODE_PRODUCT_CAT')
                ->groupBy('CODE_PRODUCT_CAT')
                ->orderBy('code')
                ->get();
        });
    }

    /** รายการหมวดย่อยภายใต้หมวด X */
    public function subcategoriesOf(string $catCode)
    {
        return $this->cache("subcat:$catCode", function () use ($catCode) {
            return PM::query()
                ->where('CODE_PRODUCT_CAT', $catCode)
                ->whereNotNull('CODE_SUB_CAT')
                ->select([
                    DB::raw('TRIM(CODE_SUB_CAT) as code'),
                    DB::raw('TRIM(DESC_PRODUCT_CAT) as name'),
                ])
                ->distinct()
                ->orderBy('code')
                ->get();
        });
    }

    /** รายการแบรนด์ทั้งหมด (เลือกกรอง allowed ได้) */
    public function brands(?bool $onlyAllowed = null)
    {
        $key = 'brands'.($onlyAllowed === null ? ':all' : ($onlyAllowed?':allowed':':not_allowed'));
        return $this->cache($key, function () use ($onlyAllowed) {
            $q = PM::query()
                ->whereNotNull('ABBREVIATION_BRAND')
                ->select([
                    DB::raw('TRIM(ABBREVIATION_BRAND) as code'),
                    DB::raw('TRIM(BRAND) as name'),
                    DB::raw('TRIM(PRODUCT_ASSIGNMENT_ALLOWED) as allowed'),
                ])
                ->distinct()
                ->orderBy('name');

            if ($onlyAllowed === true)  $q->where('PRODUCT_ASSIGNMENT_ALLOWED', 'Yes');
            if ($onlyAllowed === false) $q->where('PRODUCT_ASSIGNMENT_ALLOWED', 'No');

            return $q->get();
        });
    }

    /** แบรนด์ที่พบภายใต้ “หมวด” หรือ “หมวดย่อย” ที่กำหนด */
    public function brandsBy(string $catCode, ?string $subCode = null, ?bool $onlyAllowed = null)
    {
        $key = "brandsBy:$catCode:".($subCode ?? '-').':'.($onlyAllowed===null?'*':($onlyAllowed?'Y':'N'));
        return $this->cache($key, function () use ($catCode, $subCode, $onlyAllowed) {
            $q = PM::query()->where('CODE_PRODUCT_CAT', $catCode);
            if ($subCode) $q->where('CODE_SUB_CAT', $subCode);

            $q->whereNotNull('ABBREVIATION_BRAND')
              ->select([
                  DB::raw('TRIM(ABBREVIATION_BRAND) as code'),
                  DB::raw('TRIM(BRAND) as name'),
                  DB::raw('TRIM(PRODUCT_ASSIGNMENT_ALLOWED) as allowed'),
              ])
              ->distinct()
              ->orderBy('name');

            if ($onlyAllowed === true)  $q->where('PRODUCT_ASSIGNMENT_ALLOWED', 'Yes');
            if ($onlyAllowed === false) $q->where('PRODUCT_ASSIGNMENT_ALLOWED', 'No');

            return $q->get();
        });
    }

    /** ค้นหาแบบรวม (category/sub/brand) เพื่อทำ autocomplete */
    public function search(string $term, int $limit = 20)
    {
        $term = trim($term);
        $cats = PM::query()
            ->select([
                DB::raw("'category' as type"),
                DB::raw('TRIM(CODE_PRODUCT_CAT) as code'),
                DB::raw('MAX(TRIM(DESC_PRODUCT_CAT)) as name'),
            ])
            ->whereNotNull('CODE_PRODUCT_CAT')
            ->where('DESC_PRODUCT_CAT', 'like', "%$term%")
            ->groupBy('CODE_PRODUCT_CAT');

        $subs = PM::query()
            ->select([
                DB::raw("'subcategory' as type"),
                DB::raw('TRIM(CODE_SUB_CAT) as code'),
                DB::raw('TRIM(DESC_PRODUCT_CAT) as name'),
            ])
            ->whereNotNull('CODE_SUB_CAT')
            ->where('DESC_PRODUCT_CAT', 'like', "%$term%")
            ->distinct();

        $brands = PM::query()
            ->select([
                DB::raw("'brand' as type"),
                DB::raw('TRIM(ABBREVIATION_BRAND) as code'),
                DB::raw('TRIM(BRAND) as name'),
            ])
            ->whereNotNull('ABBREVIATION_BRAND')
            ->where('BRAND', 'like', "%$term%")
            ->distinct();

        return $cats->unionAll($subs)->unionAll($brands)
            ->limit($limit)->get();
    }
}
