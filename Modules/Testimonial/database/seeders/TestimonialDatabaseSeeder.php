<?php

namespace Modules\Testimonial\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Testimonial\app\Models\Testimonial;
use Modules\Testimonial\app\Models\TestimonialTranslation;

class TestimonialDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $testimonials = [
            [
                'image'        => 'uploads/website-images/dummy/testimonial-1.webp',
                'rating'       => '5',
                'translations' => [
                    ['lang_code' => 'en', 'name' => 'Hattie Peterman', 'designation' => 'CEO, ABC IT Limited', 'comment' => 'The doctors provided exceptional care and were extremely attentive throughout my entire visit. Every aspect of the treatment was handled with the utmost professionalism, ensuring that all my concerns were addressed. I felt genuinely valued and highly recommend their services to anyone in need of quality medical attention.'],
                    ['lang_code' => 'ar', 'name' => 'Hattie Peterman', 'designation' => 'الرئيس التنفيذي، ABC IT Limited', 'comment' => 'قدم الأطباء رعاية استثنائية وكانوا منتبهين للغاية طوال زيارتي بالكامل. تم التعامل مع كل جانب من جوانب العلاج بأقصى درجات الاحترافية، مما يضمن معالجة جميع مخاوفي. شعرت بأنني موضع تقدير حقيقي وأوصي بشدة بخدماتهم لأي شخص يحتاج إلى رعاية طبية عالية الجودة.'],
                ],
            ],
            [
                'image'        => 'uploads/website-images/dummy/testimonial-2.webp',
                'rating'       => '4',
                'translations' => [
                    ['lang_code' => 'en', 'name' => 'Paul Kelley', 'designation' => 'MD, Nice Multimedia', 'comment' => 'My experience was very positive. The staff displayed a high level of professionalism, and the entire appointment was managed efficiently and effectively. From start to finish, the care I received was thorough and attentive, making me feel confident in returning for future medical needs. I truly appreciate their dedication to patient care.'],
                    ['lang_code' => 'ar', 'name' => 'Paul Kelley', 'designation' => 'المدير الطبي، Nice Multimedia', 'comment' => 'كانت تجربتي إيجابية للغاية. أظهر الموظفون مستوى عالٍ من الاحتراف، وتم إدارة الموعد بالكامل بكفاءة وفعالية. من البداية إلى النهاية، كانت الرعاية التي تلقيتها شاملة ومنتبهة، مما جعلني أشعر بالثقة في العودة لاحتياجاتي الطبية المستقبلية. أقدر حقًا تفانيهم في رعاية المرضى.'],
                ],
            ],
            [
                'image'        => 'uploads/website-images/dummy/testimonial-3.webp',
                'rating'       => '5',
                'translations' => [
                    ['lang_code' => 'en', 'name' => 'Thomas West', 'designation' => 'CTO, KMC Limited', 'comment' => 'The service provided by the medical team was outstanding. From the moment I arrived, the level of care and attention to detail was remarkable. The staff was dedicated and professional, ensuring that all my health concerns were addressed comprehensively. I am thoroughly impressed and would highly recommend their services to others.'],
                    ['lang_code' => 'ar', 'name' => 'Thomas West', 'designation' => 'مدير التكنولوجيا، KMC Limited', 'comment' => 'كانت الخدمة المقدمة من الفريق الطبي ممتازة. من لحظة وصولي، كان مستوى الرعاية والانتباه للتفاصيل ملحوظًا. كان الموظفون مخلصين ومحترفين، مما يضمن معالجة جميع مخاوفي الصحية بشكل شامل. أنا منبهر تمامًا وأوصي بشدة بخدماتهم للآخرين.'],
                ],
            ],
        ];

        foreach ($testimonials as $testimonialData) {
            // Create the testimonial
            $testimonial = Testimonial::create([
                'image'  => $testimonialData['image'],
                'rating' => $testimonialData['rating'],
            ]);

            // Create translations
            foreach ($testimonialData['translations'] as $translation) {
                TestimonialTranslation::create([
                    'testimonial_id' => $testimonial->id,
                    'lang_code'      => $translation['lang_code'],
                    'name'           => $translation['name'],
                    'designation'    => $translation['designation'],
                    'comment'        => $translation['comment'],
                ]);
            }
        }
    }
}
