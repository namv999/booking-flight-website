<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
        ]);

        // Ghi nhận email thành công (lưu trữ hoặc tích hợp dịch vụ thư tín)
        return response()->json([
            'success' => true,
            'message' => 'Cảm ơn bạn đã đăng ký! Chúng tôi sẽ gửi thông tin ưu đãi mới nhất tới '.$validated['email'],
        ]);
    }
}
