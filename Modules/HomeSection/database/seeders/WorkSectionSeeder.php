<?php

namespace Modules\HomeSection\database\seeders;

use Illuminate\Database\Seeder;
use Modules\HomeSection\app\Models\WorkSection;
use Modules\HomeSection\app\Models\WorkSectionFaq;
use Modules\HomeSection\app\Models\WorkSectionFaqTranslation;
use Modules\HomeSection\app\Models\WorkSectionTranslation;

class WorkSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Seed work sections
        $dummyWorkSections = [
            [
                'image' => 'uploads/website-images/dummy/work-background.webp',
                'video' => 'https://www.youtube.com/watch?v=G07V0aOmWTI',
                'translations' => [
                    [
                        'lang_code' => 'en',
                        'title' => 'Ensure Your Health with Our Medical Services',
                    ],
                    [
                        'lang_code' => 'ar',
                        'title' => 'احصل على خدماتنا الطبية واعتنِ بصحتك',
                    ],
                ],
            ],
        ];

        foreach ($dummyWorkSections as $item) {
            // Insert work section
            $workSection = WorkSection::create([
                'image' => $item['image'],
                'video' => $item['video'],
            ]);

            // Insert work section translations
            foreach ($item['translations'] as $translation) {
                WorkSectionTranslation::create([
                    'work_section_id' => $workSection->id,
                    'lang_code' => $translation['lang_code'],
                    'title' => $translation['title'],
                ]);
            }
        }

        // Seed work section FAQs
        $dummyWorkSectionFaqs = [
            [
                'status' => 1,
                'faqs' => [
                    [
                        'lang_code' => 'en',
                        'question' => 'Who Are Our Patients?',
                        'answer' => 'Our patients include individuals from various demographics seeking medical care and services. We cater to a diverse range of needs, from routine check-ups to specialized treatments.',
                    ],
                    [
                        'lang_code' => 'ar',
                        'question' => 'من هم مرضانا؟',
                        'answer' => 'يشمل مرضانا أفرادًا من مختلف الفئات السكانية الذين يبحثون عن الرعاية والخدمات الطبية. نحن نلبي مجموعة متنوعة من الاحتياجات، بدءًا من الفحوصات الروتينية وحتى العلاجات المتخصصة.',
                    ],
                ],
            ],
            [
                'status' => 1,
                'faqs' => [
                    [
                        'lang_code' => 'en',
                        'question' => 'When Is A Doctor Available?',
                        'answer' => 'Doctors are available throughout the day and week. Our scheduling system ensures that you can book an appointment at a time that is convenient for you. For urgent matters, we provide emergency services to ensure that you receive timely care.',
                    ],
                    [
                        'lang_code' => 'ar',
                        'question' => 'متى يتوفر الطبيب؟',
                        'answer' => 'الأطباء متاحون على مدار اليوم والأسبوع. يضمن نظام الجدولة لدينا أنه يمكنك حجز موعد في الوقت الذي يناسبك. للحالات الطارئة، نقدم خدمات الطوارئ لضمان حصولك على الرعاية في الوقت المناسب.',
                    ],
                ],
            ],
            [
                'status' => 1,
                'faqs' => [
                    [
                        'lang_code' => 'en',
                        'question' => 'How Do I Register In This System?',
                        'answer' => 'To register in our system, you need to complete a simple online registration form. Provide your personal details, contact information, and any relevant medical history. Once registered, you will receive a confirmation email with further instructions on how to access your account and schedule appointments.',
                    ],
                    [
                        'lang_code' => 'ar',
                        'question' => 'كيف أسجل في هذا النظام؟',
                        'answer' => 'للتسجيل في نظامنا، تحتاج إلى ملء نموذج تسجيل عبر الإنترنت. قدم تفاصيلك الشخصية ومعلومات الاتصال وأي تاريخ طبي ذي صلة. بمجرد التسجيل، ستتلقى رسالة تأكيد عبر البريد الإلكتروني تحتوي على مزيد من التعليمات حول كيفية الوصول إلى حسابك وتحديد المواعيد.',
                    ],
                ],
            ],
        ];

        foreach ($dummyWorkSectionFaqs as $item) {
            $workSectionFaqs = WorkSectionFaq::create([
                'work_section_id' => WorkSection::first()->id,
                'status' => $item['status'],
            ]);

            // Insert work section FAQ translations
            foreach ($item['faqs'] as $faq) {
                WorkSectionFaqTranslation::create([
                    'work_section_faq_id' => $workSectionFaqs->id,
                    'lang_code' => $faq['lang_code'],
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }
    }
}
