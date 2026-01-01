<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionStoreRequest extends FormRequest
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
            'requested_clock_in' => ['required', 'date_format:Y-m-d H:i'],
            'requested_clock_out' => ['required', 'date_format:Y-m-d H:i', 'after:requested_clock_in'],
            'breaks' => ['array'],
            'breaks.*.requested_break_start' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:requested_clock_in'],
            'breaks.*.requested_break_end' => ['required', 'date_format:Y-m-d H:i', 'before_or_equal:requested_clock_out'],
            'reason' => ['required', 'string'],
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
            'requested_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.requested_break_start.after_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.requested_break_end.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
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
            $clockIn = $this->input('requested_clock_in');
            $clockOut = $this->input('requested_clock_out');
            $breaks = $this->input('breaks', []);

            // 出勤・退勤時間の妥当性チェック
            if ($clockIn && $clockOut && strtotime($clockIn) >= strtotime($clockOut)) {
                $validator->errors()->add('requested_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 休憩時間の妥当性チェック
            foreach ($breaks as $index => $break) {
                if (isset($break['requested_break_start']) && isset($break['requested_break_end'])) {
                    $breakStart = $break['requested_break_start'];
                    $breakEnd = $break['requested_break_end'];

                    // 休憩開始が出勤より前、または退勤より後
                    if (strtotime($breakStart) < strtotime($clockIn) || strtotime($breakStart) > strtotime($clockOut)) {
                        $validator->errors()->add("breaks.{$index}.requested_break_start", '休憩時間が不適切な値です');
                    }

                    // 休憩終了が退勤より後
                    if (strtotime($breakEnd) > strtotime($clockOut)) {
                        $validator->errors()->add("breaks.{$index}.requested_break_end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}
