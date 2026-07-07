<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question_id' => 'Apa itu Luvion?',
                'answer_id' => 'Luvion adalah platform SaaS berbasis AI yang memudahkan bisnis untuk membangun ekosistem digital secara instan (kasir, stok, kurir, pre-order) melalui deskripsi bahasa sehari-hari tanpa perlu memahami coding.',
                'question_en' => 'What is Luvion?',
                'answer_en' => 'Luvion is an AI-powered SaaS platform that makes it easy for businesses to instantly build digital ecosystems (cashier, inventory, courier, pre-orders) through natural language descriptions without needing to understand coding.',
                'sort_order' => 1,
            ],
            [
                'question_id' => 'Bagaimana cara kerja Luvion AI?',
                'answer_id' => 'Cukup ketik alur kerja atau masalah operasional bisnis Anda pada kolom AI di bagian Hero. AI kami akan menganalisis kebutuhan Anda, menyusun rekomendasi modul sistem, dan menyiapkannya untuk diluncurkan dalam waktu kurang dari 5 menit.',
                'question_en' => 'How does Luvion AI work?',
                'answer_en' => 'Simply type your business workflow or operational problems into the AI input in the Hero section. Our AI will analyze your needs, compile system module recommendations, and prepare them to launch in less than 5 minutes.',
                'sort_order' => 2,
            ],
            [
                'question_id' => 'Apakah data bisnis saya aman dengan Luvion?',
                'answer_id' => 'Keamanan data Anda adalah prioritas kami. Semua komunikasi dienkripsi melalui protokol SSL/TLS, data sensitif dilindungi, dan infrastruktur database kami didukung oleh server cloud yang aman dan andal.',
                'question_en' => 'Is my business data safe with Luvion?',
                'answer_en' => 'Your data security is our priority. All communications are encrypted via SSL/TLS protocols, sensitive data is protected, and our database infrastructure is supported by secure and reliable cloud servers.',
                'sort_order' => 3,
            ],
            [
                'question_id' => 'Apakah ada biaya tersembunyi?',
                'answer_id' => 'Tidak ada biaya tersembunyi. Harga kami sangat transparan: Paket Starter gratis selamanya untuk 3 proyek dasar, Paket Pro flat senilai $20/bulan untuk proyek tanpa batas, dan Paket Enterprise disesuaikan dengan SLA perusahaan Anda.',
                'question_en' => 'Are there any hidden fees?',
                'answer_en' => 'There are no hidden fees. Our pricing is highly transparent: Starter Plan is free forever for 3 basic projects, Pro Plan is a flat $20/month for unlimited projects, and Enterprise Plan is tailored to your company\'s SLA.',
                'sort_order' => 4,
            ],
            [
                'question_id' => 'Apakah saya bisa menggunakan domain sendiri?',
                'answer_id' => 'Ya, pada Paket Paid Pro, Anda dapat sepenuhnya menghubungkan domain kustom Anda sendiri (.com, .net, dll.), lengkap dengan konfigurasi sertifikat SSL otomatis dari Luvion.',
                'question_en' => 'Can I use my own domain?',
                'answer_en' => 'Yes, on the Paid Pro Plan, you can fully connect your own custom domain (.com, .net, etc.), complete with automated SSL certificate configuration from Luvion.',
                'sort_order' => 5,
            ],
            [
                'question_id' => 'Bagaimana jika saya membutuhkan bantuan teknis?',
                'answer_id' => 'Pengguna Paket Starter mendapatkan akses ke forum diskusi komunitas. Sementara itu, pengguna Paket Pro dan Enterprise memiliki dukungan teknis prioritas 24/7 langsung dari tim engineering kami.',
                'question_en' => 'What if I need technical assistance?',
                'answer_en' => 'Starter Plan users get access to community discussion forums. Meanwhile, Pro and Enterprise Plan users have priority 24/7 technical support directly from our engineering team.',
                'sort_order' => 6,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
