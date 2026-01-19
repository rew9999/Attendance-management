<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequestStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'requested_clock_in' => ['required'],
            'requested_clock_out' => ['required'],
            'reason' => ['required', 'string'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.break_id' => ['nullable', 'integer'],
            'breaks.*.requested_break_start' => ['nullable'],
            'breaks.*.requested_break_end' => ['nullable'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'requested_clock_in.required' => '出勤時間を入力してください',
            'requested_clock_out.required' => '退勤時間を入力してください',
            'reason.required' => '備考を記入してください',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 勤怠データを取得
            $attendanceId = $this->route('id');
            $attendance = \App\Models\Attendance::find($attendanceId);

            if (! $attendance) {
                return;
            }

            $date = \Carbon\Carbon::parse($attendance->date)->format('Y-m-d');

            // 時刻フォーマットを日時フォーマットに変換
            $requestedClockIn = $this->requested_clock_in;
            $requestedClockOut = $this->requested_clock_out;

            // 時刻のみの場合は日付を追加
            if ($requestedClockIn && ! str_contains($requestedClockIn, ' ')) {
                $requestedClockIn = $date.' '.$requestedClockIn;
            }
            if ($requestedClockOut && ! str_contains($requestedClockOut, ' ')) {
                $requestedClockOut = $date.' '.$requestedClockOut;
            }

            // 出勤時間が退勤時間より後でないかチェック
            if ($requestedClockIn && $requestedClockOut) {
                $clockIn = \Carbon\Carbon::parse($requestedClockIn);
                $clockOut = \Carbon\Carbon::parse($requestedClockOut);

                if ($clockIn->greaterThanOrEqualTo($clockOut)) {
                    $validator->errors()->add('requested_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // 休憩時間のバリデーション
            if ($this->has('breaks')) {
                foreach ($this->breaks as $index => $break) {
                    $hasStart = ! empty($break['requested_break_start']);
                    $hasEnd = ! empty($break['requested_break_end']);

                    if ($hasStart && $hasEnd) {
                        // 休憩時間の処理（フルの日時フォーマットor時刻のみ）
                        $breakStartStr = $break['requested_break_start'];
                        $breakEndStr = $break['requested_break_end'];

                        if (! str_contains($breakStartStr, ' ')) {
                            $breakStartStr = $date.' '.$breakStartStr;
                        }
                        if (! str_contains($breakEndStr, ' ')) {
                            $breakEndStr = $date.' '.$breakEndStr;
                        }

                        $breakStart = \Carbon\Carbon::parse($breakStartStr);
                        $breakEnd = \Carbon\Carbon::parse($breakEndStr);
                        $clockOut = \Carbon\Carbon::parse($requestedClockOut);

                        // 休憩開始時間が退勤時間より後でないか
                        if ($breakStart->greaterThan($clockOut)) {
                            $validator->errors()->add("breaks.{$index}.requested_break_start", '休憩時間が不適切な値です');
                        }

                        // 休憩終了時間が退勤時間より後でないか
                        if ($breakEnd->greaterThan($clockOut)) {
                            $validator->errors()->add("breaks.{$index}.requested_break_end", '休憩時間もしくは退勤時間が不適切な値です');
                        }
                    }
                }
            }
        });
    }
}
