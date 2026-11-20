<?php

namespace App\Http\Requests\Admin;

use App\Notifications\AppNotification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationBroadcastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(AppNotification::BROADCAST_TYPES))],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'string', 'max:255', 'regex:#^/(?!/)[^\s\\\\]*$#'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Loại thông báo không hợp lệ.',
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'title.max' => 'Tiêu đề tối đa 150 ký tự.',
            'message.required' => 'Vui lòng nhập nội dung.',
            'message.max' => 'Nội dung tối đa 500 ký tự.',
            'url.regex' => 'Liên kết phải là đường dẫn nội bộ, bắt đầu bằng một dấu / (ví dụ /news/ten-bai).',
        ];
    }
}
