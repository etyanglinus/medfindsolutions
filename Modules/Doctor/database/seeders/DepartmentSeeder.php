<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\DepartmentFaq;
use Modules\Doctor\app\Models\DepartmentFaqTranslation;
use Modules\Doctor\app\Models\DepartmentImage;
use Modules\Doctor\app\Models\DepartmentTranslation;
use Modules\Doctor\app\Models\DepartmentVideo;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            'Obstetrician & Gynecologist (OB/GYN)',
            'Pediatrician',
            'Dermatologist',
            'Orthopedic Surgeon',
            'Cardiologist',
            'General Surgeon',
            'Ear, Nose & Throat (ENT) Specialist',
            'Ophthalmologist',
            'Dentist',
            'Orthodontist',
            'Oral & Maxillofacial Surgeon',
            'Urologist',
            'Psychiatrist',
            'Clinical Psychologist',
            'Neurologist',
            'Neurosurgeon',
            'Endocrinologist',
            'Diabetologist',
            'Gastroenterologist',
            'Pulmonologist (Chest Physician)',
            'Nephrologist',
            'Oncologist',
            'Hematologist',
            'Rheumatologist',
            'Plastic & Reconstructive Surgeon',
            'Vascular Surgeon',
            'Cardiothoracic Surgeon',
            'Colorectal Surgeon',
            'Hepatobiliary Surgeon',
            'Pediatric Surgeon',
            'Fertility Specialist (Reproductive Medicine)',
            'Maternal-Fetal Medicine Specialist',
            'Infectious Disease Specialist',
            'Allergy & Immunology Specialist',
            'Geriatrician',
            'Palliative Care Specialist',
            'Pain Management Specialist',
            'Sleep Medicine Specialist',
            'Sports Medicine Specialist',
            'Rehabilitation Medicine (Physiatrist)',
            'Family Physician',
            'Internal Medicine Specialist',
            'Emergency Medicine Specialist',
            'Anesthesiologist',
            'Radiologist',
            'Interventional Radiologist',
            'Nuclear Medicine Specialist',
            'Pathologist',
            'Clinical Geneticist',
            'Clinical Pharmacologist',
            'Physiotherapist',
            'Occupational Therapist',
            'Speech & Language Therapist',
            'Audiologist',
            'Optometrist',
            'Optician',
            'Nutritionist',
            'Dietitian',
            'Podiatrist',
            'Chiropractor',
            'Osteopath',
            'Respiratory Therapist',
            'Renal Nurse Specialist',
            'Diabetes Educator',
            'Lactation Consultant',
            'Wound Care Specialist',
            'Stoma Care Specialist',
            'Clinical Social Worker',
            'Counseling Psychologist',
            'Addiction Medicine Specialist',
            'Sexual & Reproductive Health Specialist',
            'Travel Medicine Specialist',
            'Preventive Medicine Specialist',
            'Public Health Specialist',
            'Cosmetic Physician',
            'Aesthetic Medicine Specialist',
            'Trichologist (Hair Specialist)',
            'Venereologist (Sexually Transmitted Infections Specialist)',
            'Andrologist (Male Reproductive Health Specialist)',
            'Pediatric Cardiologist',
            'Pediatric Neurologist',
            'Pediatric Endocrinologist',
            'Pediatric Nephrologist',
            'Pediatric Gastroenterologist',
            'Pediatric Pulmonologist',
            'Pediatric Hematologist & Oncologist',
            'Pediatric Infectious Disease Specialist',
            'Neonatologist',
            'Critical Care Specialist (Intensivist)',
            'Hospice Care Specialist',
        ];

        $departments = array_map(function ($title) {
            return [
                'slug'            => Str::slug($title),
                'thumbnail_image' => 'uploads/website-images/dummy/department-thumbnail-1.webp',
                'translations'    => [
                    [
                        'lang_code'       => 'en',
                        'title'           => $title,
                        'description'     => "<p>{$title} specialists provide expert care in their field.</p>",
                        'seo_title'       => $title,
                        'seo_description' => $title,
                    ],
                    [
                        'lang_code'       => 'ar',
                        'title'           => $title,
                        'description'     => "<p>{$title} specialists provide expert care in their field.</p>",
                        'seo_title'       => $title,
                        'seo_description' => $title,
                    ],
                ],
                'videos'          => [],
                'images'          => [],
            ];
        }, $specialties);

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

        foreach ($departments as $item) {
            $english = collect($item['translations'])->firstWhere('lang_code', 'en');
            $department = Department::whereHas('translations', function ($query) use ($english) {
                $query->where('lang_code', 'en')->where('name', $english['title']);
            })->first();

            if (! $department) {
                $department = Department::create([
                    'slug'            => $item['slug'],
                    'thumbnail_image' => $item['thumbnail_image'],
                    'status'          => 1,
                ]);
            }

            foreach ($item['translations'] as $value) {
                DepartmentTranslation::updateOrCreate([
                    'department_id' => $department->id,
                    'lang_code' => $value['lang_code'],
                ], [
                    'name'           => $value['title'],
                    'description'     => $value['description'],
                    'seo_title'       => $value['seo_title'],
                    'seo_description' => $value['seo_description'],
                ]);
            }
            foreach ($faqs as $faq) {
                $englishQuestion = $faq['translations'][0]['question'];
                $departmentFaq = DepartmentFaq::where('department_id', $department->id)
                    ->whereHas('translations', fn ($query) => $query->where('lang_code', 'en')->where('question', $englishQuestion))
                    ->first();
                $departmentFaq ??= DepartmentFaq::create(['department_id' => $department->id]);
                foreach ($faq['translations'] as $value) {
                    DepartmentFaqTranslation::updateOrCreate([
                        'department_faq_id' => $departmentFaq->id,
                        'lang_code' => $value['lang_code'],
                    ], [
                        'question'      => $value['question'],
                        'answer'        => $value['answer'],
                    ]);
                }
            }
            foreach ($item['videos'] as $video) {
                DepartmentVideo::create([
                    'department_id' => $department->id,
                    'link'          => $video['link'],
                    'code'          => $video['code'],
                ]);
            }
            foreach ($item['images'] as $image) {
                DepartmentImage::create([
                    'department_id' => $department->id,
                    'small_image'   => $image['small_image'],
                    'large_image'   => $image['large_image'],
                ]);
            }
        }
    }
}
