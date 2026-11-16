<?php

namespace Modules\Doctor\database\seeders;

use App\Models\DoctorSocialMedia;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorTranslation;

class DoctorInfoSeeder extends Seeder {
    public function run() {
        $faker = Faker::create();
        $doctors = [
            [
                'id'                => 1,
                'department_id'     => 1,
                'location_id'       => 1,
                'name'              => 'Dr. Tommy Shank',
                'slug'              => 'dr-tommy-shank',
                'email'             => 'doctor@gmail.com',
                'phone'             => '111-222-1234',
                'fee'               => 10,
                'image'             => 'uploads/website-images/dummy/doctor-1.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),

                'about' => 'Dr. Tommy Shank is a highly skilled neurologist with a deep understanding of neurological conditions and disorders. He specializes in diagnosing and treating a wide range of conditions affecting the brain, spinal cord, and nerves, offering personalized care to each patient.',
        'address' => '456 Neurology Street, New York, NY, 10002, USA',
        'educations' => '
            <ul>
                <li>MBBS - New York University School of Medicine (2006)</li>
                <li>FCPS (Neurology) - American Academy of Neurology (2011)</li>
                <li>FRCS (Neurology) - Royal College of Surgeons, Edinburgh (2015)</li>
            </ul>',
        'experience' => '
            <ul>
                <li>Neurology Resident - Mount Sinai Hospital, New York (2006-2011)</li>
                <li>Consultant Neurologist - Neurology Associates, New York (2011-Present)</li>
            </ul>',
        'qualifications' => '
            <ul>
                <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                <li>Board Certified Neurologist</li>
            </ul>',
                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'Dr. Tommy Shank', 'seo_description' => 'Dr. Tommy Shank'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'د. تومي شانك', 'seo_description' => 'د. تومي شانك'],
                ],
            ],
            [
                'id'                => 2,
                'department_id'     => 2,
                'location_id'       => 1,
                'name'              => 'Dr. Aaron Bemis',
                'slug'              => 'dr-aaron-bemis',
                'email'             => 'aaron@gmail.com',
                'phone'             => '111-222-1236',
                'fee'               => 420,
                'image'             => 'uploads/website-images/dummy/doctor-2.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),

                'about' => 'Dr. Aaron Bemis is a renowned cardiologist with extensive experience in diagnosing and treating cardiovascular diseases. He specializes in managing heart diseases, conducting non-invasive tests, and providing personalized treatment plans for his patients to ensure a healthier heart.',
                'address' => '123 Cardiology Lane, New York, NY, 10001, USA',
                'educations' => '
                    <ul>
                        <li>MBBS - New York University School of Medicine (2004)</li>
                        <li>FCPS (Cardiology) - American College of Cardiology (2009)</li>
                        <li>FRCS (Cardiology) - Royal College of Surgeons, Edinburgh (2013)</li>
                    </ul>',
                'experience' => '
                    <ul>
                        <li>Cardiology Resident - New York-Presbyterian Hospital (2004-2009)</li>
                        <li>Consultant Cardiologist - New York Heart Institute (2009-Present)</li>
                    </ul>',
                'qualifications' => '
                    <ul>
                        <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                        <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                        <li>Board Certified Cardiologist</li>
                    </ul>',

                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'Dr. Aaron Bemis', 'seo_description' => 'Dr. Aaron Bemis'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'د. آرون بيميس', 'seo_description' => 'د. آرون بيميس'],
                ],
            ],
            [
                'id'                => 3,
                'department_id'     => 3,
                'location_id'       => 1,
                'name'              => 'Dr. Jesse Moran',
                'slug'              => 'dr-jesse-moran',
                'email'             => 'moran@gmail.com',
                'phone'             => '111-222-3333',
                'fee'               => 12,
                'image'             => 'uploads/website-images/dummy/doctor-3.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
                'about' => 'Dr. Jesse Moran is an experienced ophthalmologist specializing in advanced eye care, including surgical procedures and treatment of complex eye conditions. With years of practice, Dr. Moran is committed to improving patient vision and overall eye health.',
        'address' => '456 Vision Street, New York, NY, 10001, USA',
        'educations' => '
            <ul>
                <li>MBBS - New York University School of Medicine (2005)</li>
                <li>FCPS (Ophthalmology) - American Academy of Ophthalmology (2010)</li>
                <li>FRCS (Ophthalmology) - Royal College of Surgeons, Edinburgh (2014)</li>
            </ul>',
        'experience' => '
            <ul>
                <li>Ophthalmology Resident - NY Eye and Ear Infirmary (2005-2010)</li>
                <li>Consultant Ophthalmologist - New York Vision Center (2010-Present)</li>
            </ul>',
        'qualifications' => '
            <ul>
                <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                <li>Board Certified Ophthalmologist</li>
            </ul>',
                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'Dr. Jesse Moran', 'seo_description' => 'Dr. Jesse Moran'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'د. جيسي موران', 'seo_description' => 'د. جيسي موران'],
                ],
            ],
            [
                'id'                => 4,
                'department_id'     => 4,
                'location_id'       => 1,
                'name'              => 'Dr. Miguel Silva',
                'slug'              => 'dr-miguel-silva',
                'email'             => 'silva@gmail.com',
                'phone'             => '111-444-5454',
                'fee'               => 9,
                'image'             => 'uploads/website-images/dummy/doctor-4.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
                'about' => 'Dr. Miguel Silva is a compassionate pediatrician with a special interest in children\'s health, from infancy through adolescence. He is skilled in diagnosing and treating a wide range of pediatric conditions, with a focus on preventive care and child development.',
                'address' => '123 Pediatric Lane, New York, NY, 10001, USA',
                'educations' => '
                    <ul>
                        <li>MBBS - New York University School of Medicine (2007)</li>
                        <li>FCPS (Pediatrics) - American Academy of Pediatrics (2012)</li>
                        <li>FRCS (Pediatrics) - Royal College of Surgeons, Edinburgh (2016)</li>
                    </ul>',
                'experience' => '
                    <ul>
                        <li>Pediatrics Resident - NYU Langone Health (2007-2012)</li>
                        <li>Consultant Pediatrician - New York Children\'s Hospital (2012-Present)</li>
                    </ul>',
                'qualifications' => '
                    <ul>
                        <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                        <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                        <li>Board Certified Pediatrician</li>
                    </ul>',
                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'Dr. Miguel Silva', 'seo_description' => 'Dr. Miguel Silva'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'د. ميغيل سيلفا', 'seo_description' => 'د. ميغيل سيلفا'],
                ],
            ],
            [
                'id'                => 5,
                'department_id'     => 1,
                'location_id'       => 2,
                'name'              => 'Dr. John M Brown',
                'slug'              => 'dr-john-m-brown',
                'email'             => 'john@gmail.com',
                'phone'             => '123-343-4444',
                'fee'               => 25,
                'image'             => 'uploads/website-images/dummy/doctor-5.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
                'about' => 'Dr. John M Brown is a distinguished neurologist with expertise in diagnosing and treating neurological disorders, including stroke, epilepsy, and neurodegenerative diseases. His extensive experience in the field allows him to offer personalized treatment plans to his patients.',
                'address' => '101 Neuro Drive, Chicago, IL, 60601, USA',
                'educations' => '
                    <ul>
                        <li>MBBS - University of Chicago Pritzker School of Medicine (2004)</li>
                        <li>FCPS (Neurology) - American Academy of Neurology (2009)</li>
                        <li>FRCS (Neurology) - Royal College of Surgeons, Edinburgh (2013)</li>
                    </ul>',
                'experience' => '
                    <ul>
                        <li>Neurology Resident - University of Chicago Medical Center (2004-2009)</li>
                        <li>Consultant Neurologist - Chicago Neurology Associates (2009-Present)</li>
                    </ul>',
                'qualifications' => '
                    <ul>
                        <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                        <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                        <li>Board Certified Neurologist</li>
                    </ul>',
                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'Dr. John M Brown', 'seo_description' => 'Dr. John M Brown'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'د. جون إم براون', 'seo_description' => 'د. جون إم براون'],
                ],
            ],
            [
                'id'                => 6,
                'department_id'     => 6,
                'location_id'       => 2,
                'name'              => 'Dr. Nicholas Fox',
                'slug'              => 'dr-nicholas-fox',
                'email'             => 'nicholas@gmail.com',
                'phone'             => '123-556-7890',
                'fee'               => 30,
                'image'             => 'uploads/website-images/dummy/doctor-6.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
                'about' => 'Dr. Nicholas Fox is a highly skilled urologist with years of experience in diagnosing and treating urological disorders. He specializes in minimally invasive surgery, prostate health, and urinary tract conditions, providing comprehensive care to his patients.',
                'address' => '456 Urology Street, Chicago, IL, 60611, USA',
                'educations' => '
                    <ul>
                        <li>MBBS - University of Chicago Pritzker School of Medicine (2005)</li>
                        <li>FCPS (Urology) - American Urological Association (2010)</li>
                        <li>FRCS (Urology) - Royal College of Surgeons, Edinburgh (2014)</li>
                    </ul>',
                'experience' => '
                    <ul>
                        <li>Urology Resident - University of Chicago Medical Center (2005-2010)</li>
                        <li>Consultant Urologist - Chicago Urology Associates (2010-Present)</li>
                    </ul>',
                'qualifications' => '
                    <ul>
                        <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                        <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                        <li>Board Certified Urologist</li>
                    </ul>',
                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'Dr. Nicholas Fox', 'seo_description' => 'Dr. Nicholas Fox'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'د. نيكولاس فوكس', 'seo_description' => 'د. نيكولاس فوكس'],
                ],
            ],
            [
                'id'                => 7,
                'department_id'     => 4,
                'location_id'       => 3,
                'name'              => 'Dr. Sarah Adams',
                'slug'              => 'dr-sarah-adams',
                'email'             => 'sarah@gmail.com',
                'phone'             => '321-654-9870',
                'fee'               => 20,
                'image'             => 'uploads/website-images/dummy/doctor-7.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
                'about' => 'Dr. Sarah Adams is a dedicated pediatrician specializing in the care of infants, children, and adolescents. She focuses on early childhood development, vaccinations, and the management of common pediatric illnesses, ensuring every child receives personalized and comprehensive care.',
        'address' => '789 Pediatric Avenue, Boston, MA, 02115, USA',
        'educations' => '
            <ul>
                <li>MBBS - Harvard Medical School (2008)</li>
                <li>FCPS (Pediatrics) - American Academy of Pediatrics (2013)</li>
                <li>FRCS (Pediatrics) - Royal College of Surgeons, Edinburgh (2017)</li>
            </ul>',
        'experience' => '
            <ul>
                <li>Pediatrics Resident - Boston Children\'s Hospital (2008-2013)</li>
                <li>Consultant Pediatrician - Boston Pediatric Associates (2013-Present)</li>
            </ul>',
        'qualifications' => '
            <ul>
                <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                <li>Board Certified Pediatrician</li>
            </ul>',
                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'Dr. Sarah Adams', 'seo_description' => 'Dr. Sarah Adams'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'د. سارة آدامز', 'seo_description' => 'د. سارة آدامز'],
                ],
            ],
            [
                'id'                => 8,
                'department_id'     => 3,
                'location_id'       => 3,
                'name'              => 'Dr. Emily Johnson',
                'slug'              => 'dr-emily-johnson',
                'email'             => 'emily@gmail.com',
                'phone'             => '321-654-9871',
                'fee'               => 22,
                'image'             => 'uploads/website-images/dummy/doctor-8.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
                'about' => 'Dr. Emily Johnson is a renowned ophthalmologist with a focus on advanced eye care and surgical procedures. Her dedication to restoring vision and enhancing eye health has made her a leader in the field.',
        'address' => '321 Vision Avenue, Boston, MA, 02108, USA',
        'educations' => '
            <ul>
                <li>MBBS - Harvard Medical School (2003)</li>
                <li>FCPS (Ophthalmology) - American Academy of Ophthalmology (2008)</li>
                <li>FRCS (Ophthalmology) - Royal College of Surgeons, Edinburgh (2012)</li>
            </ul>',
        'experience' => '
            <ul>
                <li>Ophthalmology Resident - Massachusetts Eye and Ear Infirmary (2003-2008)</li>
                <li>Senior Ophthalmologist - Boston Eye Care Center (2008-Present)</li>
            </ul>',
        'qualifications' => '
            <ul>
                <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                <li>Board Certified Ophthalmologist</li>
            </ul>',

                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'Dr. Emily Johnson', 'seo_description' => 'Dr. Emily Johnson'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'د. إميلي جونسون', 'seo_description' => 'د. إميلي جونسون'],
                ],
            ],
            [
                'id'                => 9,
                'department_id'     => 2,
                'location_id'       => 4,
                'name'              => 'Dr. William Green',
                'slug'              => 'dr-william-green',
                'email'             => 'william@gmail.com',
                'phone'             => '432-567-8910',
                'fee'               => 18,
                'image'             => 'uploads/website-images/dummy/doctor-9.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),

                'about' => 'Dr. William Green is a leading cardiologist with a passion for heart health. He specializes in diagnosing cardiovascular diseases, conducting complex heart surgeries, and offering preventive care to ensure long-term heart health for his patients.',
        'address' => '789 Heart Care Avenue, Los Angeles, CA, 90001, USA',
        'educations' => '
            <ul>
                <li>MBBS - University of California, Los Angeles (2007)</li>
                <li>FCPS (Cardiology) - American College of Cardiology (2012)</li>
                <li>FRCS (Cardiology) - Royal College of Surgeons, Edinburgh (2016)</li>
            </ul>',
        'experience' => '
            <ul>
                <li>Cardiology Resident - UCLA Medical Center, Los Angeles (2007-2012)</li>
                <li>Consultant Cardiologist - Heart Care Clinic, Los Angeles (2012-Present)</li>
            </ul>',
        'qualifications' => '
            <ul>
                <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                <li>Board Certified Cardiologist</li>
            </ul>',

                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'Dr. William Green', 'seo_description' => 'Dr. William Green'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'د. ويليام غرين', 'seo_description' => 'د. ويليام غرين'],
                ],
            ],
            [
                'id'                => 10,
                'department_id'     => 5,
                'location_id'       => 4,
                'name'              => 'Dr. Jessica Roberts',
                'slug'              => 'dr-jessica-roberts',
                'email'             => 'jessica@gmail.com',
                'phone'             => '432-567-8911',
                'fee'               => 28,
                'image'             => 'uploads/website-images/dummy/doctor-10.webp',
                'status'            => 1,
                'show_homepage'     => 1,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
                'about' => 'Dr. Jessica Roberts is a skilled radiologist specializing in diagnostic imaging, including CT scans, MRIs, and X-rays. She has a wealth of experience in interpreting complex radiological images to assist in accurate diagnosis and treatment planning.',
        'address' => '789 Radiology Blvd, Los Angeles, CA, 90001, USA',
        'educations' => '
            <ul>
                <li>MBBS - University of California, Los Angeles (2006)</li>
                <li>FCPS (Radiology) - American College of Radiology (2011)</li>
                <li>FRCS (Radiology) - Royal College of Surgeons, Edinburgh (2015)</li>
            </ul>',
        'experience' => '
            <ul>
                <li>Radiology Resident - UCLA Medical Center (2006-2011)</li>
                <li>Consultant Radiologist - Los Angeles Imaging Center (2011-Present)</li>
            </ul>',
        'qualifications' => '
            <ul>
                <li>Fellow of the College of Physicians and Surgeons (FCPS)</li>
                <li>Fellow of the Royal College of Surgeons (FRCS)</li>
                <li>Board Certified Radiologist</li>
            </ul>',
                'translations'      => [
                    ['lang_code' => 'en', 'designations' => 'MBBS, FCPS, FRCS','seo_title' => 'Dr. Jessica Roberts', 'seo_description' => 'Dr. Jessica Roberts'],
                    ['lang_code' => 'ar', 'designations' => 'MBBS, FCPS, FRCS', 'seo_title' => 'د. جيسيكا روبرتس', 'seo_description' => 'د. جيسيكا روبرتس'],
                ],
            ],
        ];

        foreach ($doctors as $doctor) {
            $doctorData = [
                'id'                  => $doctor['id'],
                'department_id'       => $doctor['department_id'],
                'location_id'         => $doctor['location_id'],
                'name'                => $doctor['name'],
                'slug'                => $doctor['slug'],
                'email'               => $doctor['email'],
                'password'            => bcrypt(1234),
                'phone'               => $doctor['phone'],
                'fee'                 => $doctor['fee'],
                'years_of_experience' => $faker->numberBetween(1, 5),
                'image'               => $doctor['image'],
                'status'              => $doctor['status'],
                'show_homepage'       => $doctor['show_homepage'],
                'email_verified_at'   => $doctor['email_verified_at'],
                'created_at'          => $doctor['created_at'],
                'updated_at'          => $doctor['updated_at'],
            ];

            Doctor::updateOrCreate(['id' => $doctor['id']], $doctorData);

            foreach ($doctor['translations'] as $translation) {
                DoctorTranslation::updateOrCreate(
                    [
                        'doctor_id' => $doctor['id'],
                        'lang_code' => $translation['lang_code'],
                    ],
                    [
                        ...$translation,
                        'about' => $doctor['about'],
                        'address' => $doctor['address'],
                        'educations' => $doctor['educations'],
                        'experience' => $doctor['experience'],
                        'qualifications' => $doctor['qualifications'],
                    ]
                );
            }

            DoctorSocialMedia::insert([
                [
                    'doctor_id' => $doctor['id'],
                    'icon'      => 'uploads/website-images/facebook.svg',
                    'link'      => 'https://www.facebook.com',
                ],
                [
                    'doctor_id' => $doctor['id'],
                    'icon'      => 'uploads/website-images/twitter.svg',
                    'link'      => 'https://www.twitter.com',
                ],
                [
                    'doctor_id' => $doctor['id'],
                    'icon'      => 'uploads/website-images/linkedin.svg',
                    'link'      => 'https://www.linkedin.com',
                ],
                [
                    'doctor_id' => $doctor['id'],
                    'icon'      => 'uploads/website-images/instragram.svg',
                    'link'      => 'https://www.instagram.com',
                ],
                [
                    'doctor_id' => $doctor['id'],
                    'icon'      => 'uploads/website-images/website.svg',
                    'link'      => 'https://www.yourwebsite.com',
                ]
            ]);
        }
    }
}
