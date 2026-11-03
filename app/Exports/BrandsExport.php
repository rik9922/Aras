<?php

namespace App\Exports;

use App\Models\Brand;
use App\Models\BrandCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BrandsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected ?string $search = null,
        protected ?int $brandCategoryId = null,
    ) {}

    public function collection()
    {
        $categoryMap = BrandCategory::query()->pluck('name', 'id');

        return Brand::query()
            ->with(['parent', 'allowedBillToClients:id,name'])
            ->when($this->search, function ($query) {
                $query->where('brand_name', 'like', '%' . $this->search . '%');
            })
            ->when($this->brandCategoryId, function ($query) {
                $query->whereRaw('FIND_IN_SET(?, brand_category_id)', [$this->brandCategoryId]);
            })
            ->orderByDesc('id')
            ->get()
            ->map(function (Brand $brand) use ($categoryMap) {
                $rawCategoryIds = $brand->getRawOriginal('brand_category_id');
                $categoryNames = '-';
                if ($rawCategoryIds) {
                    $names = collect(explode(',', (string) $rawCategoryIds))
                        ->map(fn ($id) => $categoryMap[(int) trim($id)] ?? null)
                        ->filter()
                        ->values();
                    $categoryNames = $names->isNotEmpty() ? $names->implode(', ') : '-';
                }

                $billToNames = $brand->allowedBillToClients
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->implode(', ');

                return [
                    'Brand Name' => $brand->brand_name ?? '-',
                    'Parent Brand' => $brand->parent?->brand_name ?? '-',
                    'Active' => (int) $brand->status === 1 ? 'Yes' : 'No',
                    'Brand Categories' => $categoryNames,
                    'Budget Alert Email' => $brand->budget_alert_email ?? '-',
                    'Allowed Bill To Clients' => $billToNames !== '' ? $billToNames : 'All Bill To clients',
                    'Notes' => $brand->notes ?? '-',
                    'Created At' => $brand->created_at?->format('Y-m-d H:i:s') ?? '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Brand Name',
            'Parent Brand',
            'Active',
            'Brand Categories',
            'Budget Alert Email',
            'Allowed Bill To Clients',
            'Notes',
            'Created At',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
