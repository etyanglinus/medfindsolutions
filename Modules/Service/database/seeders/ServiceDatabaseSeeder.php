<?php

namespace Modules\Service\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Service\app\Models\Service;
use Modules\Service\app\Models\ServiceFaq;
use Modules\Service\app\Models\ServiceFaqTranslation;
use Modules\Service\app\Models\ServiceImage;
use Modules\Service\app\Models\ServiceTranslation;
use Modules\Service\app\Models\ServiceVideo;

class ServiceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $services = [
            [
                'slug'         => 'heart-surgery',
                'icon'         => 'uploads/website-images/dummy/heart_fill.png',
                'translations' => [
                    [
                        'lang_code'        => 'en',
                        'title'            => 'Heart Surgery',
                        'sort_description' => 'Comprehensive and specialized surgical care for heart conditions',
                        'description'      => '<p>Heart surgery is a complex and intricate procedure performed to treat a variety of heart conditions and diseases. Our specialized surgical team employs advanced techniques and state-of-the-art technology to ensure the best outcomes for our patients.</p><p>We offer a wide range of heart surgeries, including coronary artery bypass grafting, heart valve repair and replacement, aneurysm repair, and more. Our multidisciplinary approach ensures that each patient receives personalized care tailored to their specific needs.</p><p>With a focus on patient safety and recovery, we provide comprehensive preoperative and postoperative care to support our patients through every step of their heart surgery journey.</p>',
                        'seo_title'        => 'Heart Surgery',
                        'seo_description'  => 'Heart Surgery',
                    ],
                    [
                        'lang_code'        => 'ar',
                        'title'            => 'جراحة القلب',
                        'sort_description' => 'رعاية جراحية شاملة ومتخصصة لحالات وأمراض القلب.',
                        'description'      => '<p>جراحة القلب هي إجراء معقد ودقيق يتم إجراؤه لعلاج مجموعة متنوعة من حالات وأمراض القلب. يستخدم فريقنا الجراحي المتخصص تقنيات متقدمة وتكنولوجيا حديثة لضمان أفضل النتائج لمرضانا.</p><p>نقدم مجموعة واسعة من جراحات القلب، بما في ذلك جراحة تحويل مسار الشريان التاجي، وإصلاح واستبدال صمامات القلب، وإصلاح تمدد الأوعية الدموية، والمزيد. يضمن نهجنا متعدد التخصصات حصول كل مريض على رعاية شخصية مصممة خصيصًا لاحتياجاتهم الخاصة.</p><p>مع التركيز على سلامة المرضى والتعافي، نقدم رعاية شاملة قبل الجراحة وبعدها لدعم مرضانا خلال كل خطوة من رحلتهم في جراحة القلب.</p>',
                        'seo_title'        => 'جراحة القلب',
                        'seo_description'  => 'جراحة القلب',
                    ],
                ],
                'videos'       => [
                    [
                        'link' => 'https://www.youtube.com/watch?v=ZEjmGY6PG0U',
                        'code' => 'ZEjmGY6PG0U',
                    ],
                    [
                        'link' => 'https://www.youtube.com/watch?v=SApPLEhd_34',
                        'code' => 'SApPLEhd_34',
                    ],
                ],
            ],
            [
                'slug'         => 'dna-testing',
                'icon'         => 'uploads/website-images/dummy/dna-strand.png',
                'translations' => [
                    [
                        'lang_code'        => 'en',
                        'title'            => 'DNA Testing',
                        'sort_description' => 'Accurate and confidential DNA testing for a variety of purposes.',
                        'description'      => '<p>DNA testing provides vital information for a range of purposes, including ancestry tracing, paternity testing, and health screenings. Our state-of-the-art facilities ensure precise and confidential results.</p><p>We offer various DNA testing services, utilizing advanced technology to deliver accurate results. Whether you need DNA testing for legal, personal, or medical reasons, our dedicated team is here to assist you every step of the way.</p><p>Our commitment to quality and confidentiality ensures that your DNA testing experience is seamless and secure.</p>',
                        'seo_title'        => 'DNA Testing',
                        'seo_description'  => 'DNA Testing',
                    ],
                    [
                        'lang_code'        => 'ar',
                        'title'            => 'اختبار الحمض النووي',
                        'sort_description' => 'اختبارات الحمض النووي الدقيقة والسرية لمجموعة متنوعة من الأغراض.',
                        'description'      => '<p>يقدم اختبار الحمض النووي معلومات حيوية لمجموعة من الأغراض، بما في ذلك تتبع الأنساب، واختبار الأبوة، والفحوصات الصحية. تضمن مرافقنا الحديثة نتائج دقيقة وسرية.</p><p>نقدم خدمات اختبار الحمض النووي المختلفة، باستخدام تقنية متقدمة لتقديم نتائج دقيقة. سواء كنت بحاجة إلى اختبار الحمض النووي لأسباب قانونية أو شخصية أو طبية، فإن فريقنا المخصص هنا لمساعدتك في كل خطوة.</p><p>التزامنا بالجودة والسرية يضمن أن تجربتك في اختبار الحمض النووي سلسة وآمنة.</p>',
                        'seo_title'        => 'اختبار الحمض النووي',
                        'seo_description'  => 'اختبار الحمض النووي',
                    ],
                ],
                'videos'       => [
                    [
                        'link' => 'https://www.youtube.com/watch?v=gG7uCskUOrA',
                        'code' => 'gG7uCskUOrA',
                    ],
                    [
                        'link' => 'https://www.youtube.com/watch?v=TNKWgcFPHqw',
                        'code' => 'TNKWgcFPHqw',
                    ],
                ],
            ],
            [
                'slug'         => 'general-treatment',
                'icon'         => 'uploads/website-images/dummy/medical.png',
                'translations' => [
                    [
                        'lang_code'        => 'en',
                        'title'            => 'General Treatment',
                        'sort_description' => 'Comprehensive care for a wide range of common health issues.',
                        'description'      => '<p>Our general treatment services cover a broad spectrum of common health issues, providing comprehensive and personalized care to patients of all ages.</p><p>From routine check-ups and vaccinations to treatment of minor illnesses and injuries, our experienced healthcare professionals are dedicated to maintaining your health and well-being.</p><p>We prioritize patient education and preventive care, ensuring that you have the knowledge and resources to lead a healthy life.</p>',
                        'seo_title'        => 'General Treatment',
                        'seo_description'  => 'General Treatment',
                    ],
                    [
                        'lang_code'        => 'ar',
                        'title'            => 'العلاج العام',
                        'sort_description' => 'رعاية شاملة لمجموعة واسعة من القضايا الصحية الشائعة.',
                        'description'      => '<p>تشمل خدمات العلاج العام لدينا مجموعة واسعة من القضايا الصحية الشائعة، مما يوفر رعاية شاملة وشخصية للمرضى من جميع الأعمار.</p><p>من الفحوصات الروتينية والتطعيمات إلى علاج الأمراض والإصابات الطفيفة، يكرس مهنيونا الصحيون ذوو الخبرة للحفاظ على صحتك ورفاهيتك.</p><p>نحن نولي الأولوية لتعليم المرضى والرعاية الوقائية، مما يضمن أن يكون لديك المعرفة والموارد لقيادة حياة صحية.</p>',
                        'seo_title'        => 'العلاج العام',
                        'seo_description'  => 'العلاج العام',
                    ],
                ],
                'videos'       => [
                    [
                        'link' => 'https://www.youtube.com/watch?v=s7a7jdLdhus',
                        'code' => 's7a7jdLdhus',
                    ],
                    [
                        'link' => 'https://www.youtube.com/watch?v=xSTUoWYCmdo',
                        'code' => 'xSTUoWYCmdo',
                    ],
                ],
            ],
            [
                'slug'         => 'eye-treatment',
                'icon'         => 'uploads/website-images/dummy/eye.png',
                'translations' => [
                    [
                        'lang_code'        => 'en',
                        'title'            => 'Eye Treatment',
                        'sort_description' => 'Comprehensive care for vision problems and eye health.',
                        'description'      => '<p>We offer a range of specialized services, each tailored to meet specific requirements and exceed expectations. Our team is dedicated to providing expert solutions and personalized care, ensuring that every aspect of your needs is addressed effectively.</p>
<p>From initial consultations to ongoing support, we strive to deliver excellence at every step. Our commitment to quality and customer satisfaction drives us to continuously improve and enhance our services to better serve you.</p>',
                        'seo_title'        => 'Eye Treatment',
                        'seo_description'  => 'Eye Treatment',
                    ],
                    [
                        'lang_code'        => 'ar',
                        'title'            => 'علاج العيون',
                        'sort_description' => 'رعاية شاملة لمشاكل الرؤية وصحة العين وحالاتها.',
                        'description'      => '<p>نحن نقدم مجموعة من الخدمات المتخصصة، كل منها مصمم لتلبية متطلبات محددة وتجاوز التوقعات. يكرس فريقنا جهوده لتقديم حلول الخبراء والرعاية الشخصية، مما يضمن معالجة كل جانب من جوانب احتياجاتك بفعالية.</p>
<p>بدءًا من المشاورات الأولية وحتى الدعم المستمر، نسعى جاهدين لتحقيق التميز في كل خطوة. إن التزامنا بالجودة ورضا العملاء يدفعنا إلى التحسين المستمر لخدماتنا وتعزيزها لخدمتك بشكل أفضل.</p>',
                        'seo_title'        => 'علاج العيون',
                        'seo_description'  => 'علاج العيون',
                    ],
                ],
                'videos'       => [
                    [
                        'link' => 'https://www.youtube.com/watch?v=HPDpSiAJj4k',
                        'code' => 'HPDpSiAJj4k',
                    ],
                ],
            ],
            [
                'slug'         => 'dental-service',
                'icon'         => 'uploads/website-images/dummy/tooth.png',
                'translations' => [
                    [
                        'lang_code'        => 'en',
                        'title'            => 'Dental Service',
                        'sort_description' => 'Comprehensive dental care for a healthy and beautiful smile.',
                        'description'      => '<p>Our dental services encompass a range of treatments designed to maintain and improve your oral health. From routine check-ups and cleanings to advanced procedures, our expert team provides personalized care to ensure optimal results.</p><p>We utilize state-of-the-art technology and techniques to offer top-quality dental care. Whether you need preventive care, restorative treatments, or cosmetic enhancements, we are dedicated to delivering exceptional service in a comfortable environment.</p><p>Experience professional and compassionate dental care aimed at preserving your smile and enhancing your overall well-being.</p>',
                        'seo_title'        => 'Dental Service',
                        'seo_description'  => 'Dental Service',
                    ],
                    [
                        'lang_code'        => 'ar',
                        'title'            => 'خدمة الأسنان',
                        'sort_description' => 'رعاية شاملة للأسنان من أجل ابتسامة صحية وجميلة.',
                        'description'      => '<p>تشمل خدماتنا في طب الأسنان مجموعة من العلاجات المصممة للحفاظ على صحة فمك وتحسينها. من الفحوصات الروتينية وتنظيف الأسنان إلى الإجراءات المتقدمة، يقدم فريقنا الخبير رعاية مخصصة لضمان أفضل النتائج.</p><p>نستخدم أحدث التقنيات والتكنولوجيا لتقديم رعاية أسنان عالية الجودة. سواء كنت بحاجة إلى الرعاية الوقائية، أو العلاجات الترميمية، أو تحسينات تجميلية، نحن ملتزمون بتقديم خدمة استثنائية في بيئة مريحة.</p><p>اختبر الرعاية المهنية والرحيمة التي تهدف إلى الحفاظ على ابتسامتك وتعزيز صحتك العامة.</p>',
                        'seo_title'        => 'خدمة الأسنان',
                        'seo_description'  => 'خدمة الأسنان',
                    ],
                ],
                'videos'       => [
                    [
                        'link' => 'https://www.youtube.com/watch?v=kZ6HRcxjwLg',
                        'code' => 'kZ6HRcxjwLg',
                    ],
                ],
            ],
            [
                'slug'         => 'ambulance-service',
                'icon'         => 'uploads/website-images/dummy/emergency-ambulance.png',
                'translations' => [
                    [
                        'lang_code'        => 'en',
                        'title'            => 'Ambulance Service',
                        'sort_description' => 'Comprehensive dental care for improving your smile.',
                        'description'      => '<p>Our dental service provides a full range of treatments to ensure your oral health and enhance your smile. We offer routine check-ups, cleanings, and advanced procedures tailored to your needs.</p><p>Our experienced team uses the latest technology and techniques to deliver top-notch care in a comfortable environment. From preventive care to restorative and cosmetic treatments, we are dedicated to achieving the best outcomes for your dental health.</p><p>Discover exceptional dental care designed to keep your smile bright and healthy.</p>',
                        'seo_title'        => 'Ambulance Service',
                        'seo_description'  => 'Ambulance Service',
                    ],
                    [
                        'lang_code'        => 'ar',
                        'title'            => 'خدمة الاسعاف',
                        'sort_description' => 'رعاية شاملة للأسنان للحفاظ على ابتسامتك وتحسينها.',
                        'description'      => '<p>تقدم خدمتنا لطب الأسنان مجموعة كاملة من العلاجات لضمان صحة فمك وتعزيز ابتسامتك. نقدم فحوصات روتينية وتنظيفات وإجراءات متقدمة مصممة حسب احتياجاتك.</p><p>يستخدم فريقنا المتمرس أحدث التقنيات والتكنولوجيا لتقديم رعاية عالية الجودة في بيئة مريحة. من الرعاية الوقائية إلى العلاجات الترميمية والتجميلية، نحن ملتزمون بتحقيق أفضل النتائج لصحة فمك.</p><p>اكتشف رعاية الأسنان الاستثنائية المصممة للحفاظ على ابتسامتك مشرقة وصحية.</p>',
                        'seo_title'        => 'خدمة الاسعاف',
                        'seo_description'  => 'خدمة الاسعاف',
                    ],
                ],
                'videos'       => [
                    [
                        'link' => 'https://www.youtube.com/watch?v=CZNmRPJjd4Y',
                        'code' => 'CZNmRPJjd4Y',
                    ],
                ],
            ],
        ];
        $faqs = [
            [
                'translations' => [
                    [
                        'lang_code' => 'en',
                        'question'  => 'Lorem ipsum dolor sit amet per mollis?',
                        'answer'    => 'Lorem ipsum dolor sit amet, per mollis aeterno nostrud in, nam timeam fastidii eu. Commodo nonumes vim eu. Quo indoctum voluptatibus delicatissimi no. Eu cum dico melius. Cum impetus scribentur ad.',
                    ],
                    [
                        'lang_code' => 'ar',
                        'question'  => 'لوريم إيبسوم دولور الجلوس أميت لكل موليس؟',
                        'answer'    => 'لوريم إيبسوم دولور الجلوس أميت لكل موليس؟لوريم إيبسوم دولور الجلوس أميت لكل موليس؟لوريم إيبسوم دولور الجلوس أميت لكل موليس؟',
                    ],
                ],
            ],
            [
                'translations' => [
                    [
                        'lang_code' => 'en',
                        'question'  => 'Ut alterum dissentiunt eam nobis audire?',
                        'answer'    => 'Ut alterum dissentiunt eam, nobis audire verterem ut vel. Vidisse persius mea no. Melius imperdiet his at. Ex has zril convenire, cu eos eros dolor, omittam adversarium suscipiantur mea ea.',
                    ],
                    [
                        'lang_code' => 'ar',
                        'question'  => 'هل من الممكن أن أختلف مع أي شخص آخر؟',
                        'answer'    => 'هل من الممكن أن أختلف مع أي شخص آخرهل من الممكن أن أختلف مع أي شخص آخرهل من الممكن أن أختلف مع أي شخص آخر',
                    ],
                ],

            ],
        ];

        foreach ($services as $item) {
            $service = Service::create([
                'slug' => $item['slug'],
                'icon' => $item['icon'],
            ]);

            foreach ($item['translations'] as $translation) {
                ServiceTranslation::create([
                    'service_id'       => $service->id,
                    'lang_code'        => $translation['lang_code'],
                    'title'            => $translation['title'],
                    'description'      => $translation['description'],
                    'sort_description' => $translation['sort_description'],
                    'seo_title'        => $translation['seo_title'],
                    'seo_description'  => $translation['seo_description'],
                ]);
            }
            foreach ($item['videos'] as $video) {
                ServiceVideo::create([
                    'service_id' => $service->id,
                    'link'       => $video['link'],
                    'code'       => $video['code'],
                ]);
            }

            $images = [
                [
                    'small_image' => 'uploads/website-images/dummy/service-small-1.webp',
                    'large_image' => 'uploads/website-images/dummy/service-large-1.webp',
                ],
                [
                    'small_image' => 'uploads/website-images/dummy/service-small-2.webp',
                    'large_image' => 'uploads/website-images/dummy/service-large-2.webp',
                ],
                [
                    'small_image' => 'uploads/website-images/dummy/service-small-3.webp',
                    'large_image' => 'uploads/website-images/dummy/service-large-3.webp',
                ],
                [
                    'small_image' => 'uploads/website-images/dummy/service-small-4.webp',
                    'large_image' => 'uploads/website-images/dummy/service-large-4.webp',
                ],
            ];

            foreach ($images as $image) {
                ServiceImage::create([
                    'service_id'  => $service->id,
                    'small_image' => $image['small_image'],
                    'large_image' => $image['large_image'],
                ]);
            }
            foreach ($faqs as $faq) {
                $serviceFaq = ServiceFaq::create(['service_id' => $service->id]);
                foreach ($faq['translations'] as $value) {
                    ServiceFaqTranslation::create([
                        'service_faq_id' => $serviceFaq->id,
                        'lang_code'      => $value['lang_code'],
                        'question'       => $value['question'],
                        'answer'         => $value['answer'],
                    ]);
                }
            }
        }
    }
}
