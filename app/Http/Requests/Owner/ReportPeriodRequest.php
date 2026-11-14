<?php

namespace App\Http\Requests\Owner;

use App\Services\AccountingReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', Rule::in(AccountingReportService::PERIODS)],
            'start_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'format' => ['nullable', 'string', Rule::in(['csv', 'print'])],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->period() !== AccountingReportService::PERIOD_CUSTOM || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $days = Carbon::parse($this->input('start_date'))->diffInDays(Carbon::parse($this->input('end_date'))) + 1;

                if ($days > AccountingReportService::MAX_RANGE_DAYS) {
                    $validator->errors()->add(
                        'end_date',
                        'Rentang laporan maksimal '.AccountingReportService::MAX_RANGE_DAYS.' hari.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'period' => 'Periode Laporan',
            'start_date' => 'Tanggal Awal',
            'end_date' => 'Tanggal Akhir',
            'format' => 'Format Ekspor',
        ];
    }

    public function period(): string
    {
        return (string) ($this->input('period') ?: AccountingReportService::PERIOD_THIS_MONTH);
    }

    /**
     * @return array{preset: string, start: Carbon, end: Carbon, label: string}
     */
    public function resolvedPeriod(AccountingReportService $service): array
    {
        return $service->resolvePeriod($this->period(), $this->input('start_date'), $this->input('end_date'));
    }
}
