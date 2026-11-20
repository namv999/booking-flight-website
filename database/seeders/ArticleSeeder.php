<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::where('role', 'admin')->value('id') ?? User::value('id');

        // [tiêu đề, chuyên mục, ảnh, ghim, mô tả ngắn, nội dung Markdown]
        $articles = [
            ['Chuẩn bị gì cho một chuyến bay thật nhẹ nhàng?', 'booking-tips', 'images/article-charter-flight.avif', true,
                'Kiểm tra giấy tờ, check-in sớm và chọn chỗ ngồi ưng ý để khởi đầu hành trình thoải mái nhất.',
                "Một chuyến bay suôn sẻ bắt đầu từ khâu chuẩn bị trước ngày bay.\n\n## Danh sách nên làm\n\n- Kiểm tra giấy tờ tùy thân còn hạn.\n- Hoàn tất check-in trực tuyến sớm.\n- Chọn chỗ ngồi phù hợp thói quen của bạn.\n- Đến sân bay sớm hơn giờ dự kiến."],
            ['5 hành trình đáng để lên kế hoạch trong mùa hè này', 'travel-guide', 'images/article-travel-guide.png', true,
                'Từ bãi biển thơ mộng đến đô thị sôi động, hãy lên lịch cho kỳ nghỉ cùng gia đình.',
                "Mùa hè là dịp lý tưởng để khám phá điểm đến mới.\n\n## Gợi ý\n\n1. Đà Nẵng và Hội An cho kỳ nghỉ biển.\n2. Hà Nội cho ẩm thực và phố cổ.\n3. Bangkok cho chuyến đi cuối tuần dài.\n4. Tokyo cho lần đầu đến Nhật Bản.\n5. Phú Quốc cho kỳ nghỉ thư giãn."],
            ['Một ngày ở Đà Nẵng: ăn gì, đi đâu?', 'travel-guide', 'images/destination-da-nang.jpg', false,
                'Gợi ý lịch trình một ngày gọn nhẹ cho chuyến đi ngắn tới Đà Nẵng.',
                "Đà Nẵng phù hợp cho chuyến đi ngắn vì các điểm tham quan nằm gần nhau.\n\n## Lịch trình gợi ý\n\n- Sáng: dạo biển và ăn sáng đặc sản.\n- Trưa: nghỉ ngơi, thưởng thức hải sản.\n- Chiều: tham quan cầu và bờ sông khi hoàng hôn."],
            ['Bangkok cuối tuần dài: gợi ý lịch trình 3 ngày', 'travel-guide', 'images/destination-bangkok.jpg', false,
                'Ba ngày cho chợ đêm, đền chùa và ẩm thực đường phố ở Bangkok.',
                "Bangkok có nhiều chuyến bay ngắn từ Việt Nam nên rất hợp với kỳ nghỉ cuối tuần.\n\n## Ba ngày\n\n- Ngày 1: đền chùa và khu phố cổ.\n- Ngày 2: mua sắm và chợ đêm.\n- Ngày 3: thư giãn và ra sân bay."],
            ['Tokyo lần đầu: những điều nên biết trước khi bay', 'travel-guide', 'images/destination-tokyo.jpg', false,
                'Giấy tờ, giờ bay và cách di chuyển cơ bản cho người lần đầu đến Tokyo.',
                "Trước khi bay, hãy kiểm tra yêu cầu nhập cảnh hiện hành và hạn hộ chiếu của bạn.\n\n## Cần chuẩn bị\n\n- Hộ chiếu còn hạn đủ dài.\n- Lịch trình và nơi lưu trú.\n- Cách di chuyển từ sân bay vào trung tâm."],
            ['Mẹo săn vé giá tốt: đặt sớm hay đặt muộn?', 'booking-tips', 'images/hero-banner.jpg', false,
                'So sánh giá theo nhiều ngày và khung giờ để chọn được chuyến vừa túi tiền.',
                "Giá vé thay đổi theo ngày bay, giờ bay và thời điểm đặt.\n\n## Mẹo\n\n- Linh hoạt ngày đi, tránh thứ Sáu và Chủ nhật.\n- Chuyến sáng sớm hoặc đêm thường rẻ hơn.\n- Dùng bộ lọc giá và sắp xếp để so sánh nhanh."],
            ['Ưu đãi cuối năm cho các chặng nội địa', 'promotion', 'images/destination-ha-noi.jpg', false,
                'Tổng hợp cách tìm vé giá tốt cho các chặng nội địa dịp cuối năm.',
                "Cuối năm nhu cầu đi lại tăng cao, hãy chủ động đặt vé sớm.\n\n- Theo dõi trang chủ để cập nhật ưu đãi.\n- Đặt khứ hồi để dễ so sánh tổng chi phí."],
            ['Tân Sơn Nhất: kinh nghiệm làm thủ tục nhanh gọn', 'airport-info', 'images/destination-ho-chi-minh.jpg', false,
                'Đến sớm, chuẩn bị giấy tờ và biết trước khu vực làm thủ tục giúp bạn đỡ vội.',
                "Sân bay đông nhất vào các khung giờ cao điểm.\n\n## Lưu ý\n\n- Check-in trực tuyến trước khi đến.\n- Chuẩn bị sẵn giấy tờ và mã đặt chỗ.\n- Đến sớm hơn giờ dự kiến."],
            ['Nội Bài: di chuyển từ sân bay về trung tâm', 'airport-info', 'images/destination-ha-noi.jpg', false,
                'Tổng quan các cách di chuyển phổ biến từ sân bay Nội Bài vào nội thành.',
                "Từ sân bay có nhiều cách để vào trung tâm: xe buýt, taxi hoặc xe công nghệ.\n\nHãy chọn phương tiện phù hợp với giờ đến và lượng hành lý."],
            ['Hạng Economy, Business, First khác nhau thế nào?', 'booking-tips', 'images/article-charter-flight.avif', false,
                'Hiểu rõ hạng vé, hành lý miễn phí và phí chọn ghế trước khi đặt.',
                "Mỗi hạng vé có hành lý miễn phí và phí chọn ghế khác nhau.\n\n- **Economy**: phổ thông, giá dễ tiếp cận.\n- **Business**: ghế rộng hơn, hành lý nhiều hơn.\n- **First**: dịch vụ cao cấp nhất."],
            ['Chuyến bay bị hoãn: bạn nên làm gì?', 'airline-news', 'images/hero-banner.jpg', false,
                'Cách theo dõi trạng thái chuyến bay và liên hệ hãng khi lịch bay thay đổi.',
                "Khi chuyến bay bị hoãn, hãy kiểm tra trạng thái trong lịch sử đặt vé và liên hệ hãng để được hướng dẫn.\n\nLuôn giữ mã đặt chỗ và thông tin liên hệ cập nhật."],
            ['Thông báo bảo trì hệ thống đặt vé', 'announcement', 'images/article-travel-guide.png', false,
                'Hệ thống có thể gián đoạn ngắn trong thời gian bảo trì định kỳ.',
                "Để nâng cao chất lượng dịch vụ, hệ thống sẽ bảo trì định kỳ.\n\nTrong thời gian này, một số chức năng có thể tạm thời không truy cập được. Xin cảm ơn sự thông cảm của bạn."],
        ];

        foreach ($articles as $i => [$title, $category, $thumb, $featured, $excerpt, $content]) {
            Article::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'user_id' => $authorId,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'thumbnail_path' => $thumb,
                    'category' => $category,
                    'status' => 'published',
                    'is_featured' => $featured,
                    'view_count' => random_int(20, 900),
                    'published_at' => now()->subDays($i * 2 + 1),
                ]
            );
        }
    }
}
