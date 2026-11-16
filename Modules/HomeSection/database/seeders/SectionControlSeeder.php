<?php

namespace Modules\HomeSection\database\seeders;

use Illuminate\Database\Seeder;
use Modules\HomeSection\app\Models\SectionControl;
use Modules\HomeSection\app\Models\SectionControlTranslation;

class SectionControlSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run() {
        $translations = [
            [
                'lang_code'                 => 'en',
                'work_first_heading'        => 'How',
                'work_second_heading'       => 'We Work',
                'work_description'          => 'We deliver exceptional results through collaboration, expertise, and continuous improvement. Our process ensures quality and efficiency, with open communication and a commitment to excellence for optimal client satisfaction.',
                'service_first_heading'     => 'Our',
                'service_second_heading'    => 'Service',
                'service_description'       => 'We offer comprehensive, high-quality solutions tailored to meet your unique needs, ensuring satisfaction through expert execution, personalized attention, and continuous support.',
                'department_first_heading'  => 'Our',
                'department_second_heading' => 'Departments',
                'department_description'    => 'We provide specialized medical services across various departments, ensuring comprehensive care, expert diagnosis, and personalized treatment for all your healthcare needs.',
                'patient_first_heading'     => 'Our',
                'patient_second_heading'    => 'Patients',
                'patient_description'       => 'We prioritize our patients well-being by providing compassionate care, personalized treatment plans, and continuous support to ensure their health and satisfaction.',
                'doctor_first_heading'      => 'Our',
                'doctor_second_heading'     => 'Doctors',
                'doctor_description'        => 'Our doctors are highly skilled professionals dedicated to providing exceptional care, accurate diagnoses, and personalized treatment plans to ensure the best health outcomes for our patients.',
                'blog_first_heading'        => 'Our',
                'blog_second_heading'       => 'Blog',
                'blog_description'          => 'Stay informed with our latest health tips, medical news, and expert advice. Our blog is dedicated to helping you maintain a healthy lifestyle and stay updated on healthcare trends.',
            ],
            [
                'lang_code'                 => 'ar',
                'work_first_heading'        => 'كيفية',
                'work_second_heading'       => 'نحن نعمل',
                'work_description'          => 'نحن نقدم نتائج استثنائية من خلال التعاون والخبرة والتحسين المستمر. تضمن عمليتنا الجودة والكفاءة، مع التواصل المفتوح والالتزام بالتميز لتحقيق الرضا الأمثل للعملاء.',
                'service_first_heading'     => 'خدمتنا',
                'service_second_heading'    => 'خدمات',
                'service_description'       => 'نحن نقدم حلولاً شاملة وعالية الجودة مصممة خصيصًا لتلبية احتياجاتك الفريدة، مع ضمان الرضا من خلال تنفيذ الخبراء والاهتمام الشخصي والدعم المستمر.',
                'department_first_heading'  => 'أقسامنا',
                'department_second_heading' => 'الأقسام',
                'department_description'    => 'نحن نقدم خدمات طبية متخصصة عبر مختلف الأقسام، مما يضمن رعاية شاملة وتشخيصًا متخصصًا وعلاجًا شخصيًا لجميع احتياجات الرعاية الصحية الخاصة بك.',
                'patient_first_heading'     => 'مرضانا',
                'patient_second_heading'    => 'المرضى',
                'patient_description'       => 'نحن نعطي الأولوية لرفاهية مرضانا من خلال توفير الرعاية الرحيمة وخطط العلاج الشخصية والدعم المستمر لضمان صحتهم ورضاهم.',
                'doctor_first_heading'      => 'أطباؤنا',
                'doctor_second_heading'     => 'الأطباء',
                'doctor_description'        => 'أطباؤنا هم من المهنيين ذوي المهارات العالية الذين يكرسون جهودهم لتقديم رعاية استثنائية وتشخيصات دقيقة وخطط علاجية مخصصة لضمان أفضل النتائج الصحية لمرضانا.',
                'blog_first_heading'        => 'مدونتنا',
                'blog_second_heading'       => 'المدونة',
                'blog_description'          => 'ابق على اطلاع بأحدث النصائح الصحية والأخبار الطبية ونصائح الخبراء. مدونتنا مخصصة لمساعدتك في الحفاظ على نمط حياة صحي والبقاء على اطلاع دائم باتجاهات الرعاية الصحية.',
            ],
        ];

        $sectionControl = SectionControl::create();
        // Insert section control translations
        foreach ($translations as $translation) {
            SectionControlTranslation::create([
                'section_control_id'        => $sectionControl->id,
                'lang_code'                 => $translation['lang_code'],
                'work_first_heading'        => $translation['work_first_heading'],
                'work_second_heading'       => $translation['work_second_heading'],
                'work_description'          => $translation['work_description'],
                'service_first_heading'     => $translation['service_first_heading'],
                'service_second_heading'    => $translation['service_second_heading'],
                'service_description'       => $translation['service_description'],
                'department_first_heading'  => $translation['department_first_heading'],
                'department_second_heading' => $translation['department_second_heading'],
                'department_description'    => $translation['department_description'],
                'patient_first_heading'     => $translation['patient_first_heading'],
                'patient_second_heading'    => $translation['patient_second_heading'],
                'patient_description'       => $translation['patient_description'],
                'doctor_first_heading'      => $translation['doctor_first_heading'],
                'doctor_second_heading'     => $translation['doctor_second_heading'],
                'doctor_description'        => $translation['doctor_description'],
                'blog_first_heading'        => $translation['blog_first_heading'],
                'blog_second_heading'       => $translation['blog_second_heading'],
                'blog_description'          => $translation['blog_description'],
            ]);
        }
    }
}
