<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorTranslation;

/**
 * KenyanDoctorSeeder
 *
 * Seeds 12 Kenyan doctors using the theme's built-in dummy images
 * (doctor-1.webp through doctor-12.webp in public/uploads/website-images/dummy/).
 *
 * Locations used (seeded by KenyaLocationSeeder):
 *   5 = Nairobi  |  6 = Mombasa  |  7 = Kisumu  |  8 = Nakuru
 *
 * Departments (already seeded by DepartmentSeeder):
 *   1 = Neurology  |  2 = Cardiology  |  3 = Ophthalmology
 *   4 = Pediatric  |  5 = Radiology   |  6 = Urology
 */
class KenyanDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = $this->doctorData();

        foreach ($doctors as $data) {
            // Upsert by email so the seeder is safe to re-run
            $doctor = Doctor::updateOrCreate(
                ['email' => $data['email']],
                [
                    'department_id'    => $data['department_id'],
                    'location_id'      => $data['location_id'],
                    'name'             => $data['name'],
                    'slug'             => $data['slug'],
                    'phone'            => $data['phone'],
                    'fee'              => $data['fee'],
                    'image'            => $data['image'],
                    'status'           => 1,
                    'show_homepage'    => 1,
                    'email_verified_at'=> now(),
                    'password'         => Hash::make('password'),
                ]
            );

            // Upsert English translation
            DoctorTranslation::updateOrCreate(
                ['doctor_id' => $doctor->id, 'lang_code' => 'en'],
                [
                    'designations'    => $data['designations'],
                    'licensing_body'  => $data['licensing_body'],
                    'about'           => $data['about'],
                    'address'         => $data['address'],
                    'educations'      => $data['educations'],
                    'experience'      => $data['experience'],
                    'qualifications'  => $data['qualifications'],
                    'seo_title'       => $data['name'] . ' | MedFind Kenya',
                    'seo_description' => 'Book an appointment with ' . $data['name'] . ', ' . $data['designations'] . ' in Kenya.',
                ]
            );
        }
    }

    // -------------------------------------------------------------------------
    // Doctor data — all 12 doctors
    // -------------------------------------------------------------------------
    private function doctorData(): array
    {
        return [
            // ── 1. Dr. Amina Wanjiku ─────────────────────────────────────────
            [
                'department_id'  => 2, // Cardiology
                'location_id'    => 5, // Nairobi
                'name'           => 'Dr. Amina Wanjiku',
                'slug'           => 'dr-amina-wanjiku',
                'email'          => 'amina.wanjiku@medfind.ke',
                'phone'          => '+254-700-100-001',
                'fee'            => 2500,
                'image'          => 'uploads/website-images/dummy/doctor-1.webp',
                'designations'   => 'MBChB, MMed (Cardiology)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Amina Wanjiku is a leading cardiologist at Kenyatta National Hospital with over 12 years of experience managing heart disease in Kenya. She specialises in echocardiography, hypertension, and heart failure, and has trained extensively at the University of Nairobi and the Aga Khan University Hospital.',
                'address'        => 'Kenyatta National Hospital, Hospital Rd, Nairobi, Kenya',
                'educations'     => '<ul><li>MBChB – University of Nairobi (2008)</li><li>MMed Cardiology – University of Nairobi (2013)</li><li>Fellowship, Echocardiography – Groote Schuur Hospital, Cape Town (2015)</li></ul>',
                'experience'     => '<ul><li>Medical Officer – KNH, Nairobi (2008–2010)</li><li>Cardiology Registrar – KNH (2010–2013)</li><li>Consultant Cardiologist – KNH (2013–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, University of Nairobi</li><li>MMed (Cardiology), University of Nairobi</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 2. Dr. Kelvin Ochieng ────────────────────────────────────────
            [
                'department_id'  => 1, // Neurology
                'location_id'    => 5, // Nairobi
                'name'           => 'Dr. Kelvin Ochieng',
                'slug'           => 'dr-kelvin-ochieng',
                'email'          => 'kelvin.ochieng@medfind.ke',
                'phone'          => '+254-700-100-002',
                'fee'            => 3000,
                'image'          => 'uploads/website-images/dummy/doctor-2.webp',
                'designations'   => 'MBChB, MMed (Neurology)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Kelvin Ochieng is a neurologist at the Aga Khan University Hospital, Nairobi, with a special interest in stroke management, epilepsy, and neurodegenerative conditions. He completed his fellowship in neurology at the University of Cape Town and is passionate about improving neurological care access across East Africa.',
                'address'        => 'Aga Khan University Hospital, 3rd Parklands Ave, Nairobi, Kenya',
                'educations'     => '<ul><li>MBChB – Moi University (2007)</li><li>MMed Neurology – University of Nairobi (2013)</li><li>Fellowship – University of Cape Town (2015)</li></ul>',
                'experience'     => '<ul><li>Medical Officer – Moi Teaching & Referral Hospital (2007–2010)</li><li>Neurology Registrar – KNH (2010–2013)</li><li>Consultant Neurologist – AKUH Nairobi (2015–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, Moi University</li><li>MMed (Neurology)</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 3. Dr. Grace Muthoni ─────────────────────────────────────────
            [
                'department_id'  => 4, // Pediatric
                'location_id'    => 6, // Mombasa
                'name'           => 'Dr. Grace Muthoni',
                'slug'           => 'dr-grace-muthoni',
                'email'          => 'grace.muthoni@medfind.ke',
                'phone'          => '+254-700-100-003',
                'fee'            => 1800,
                'image'          => 'uploads/website-images/dummy/doctor-3.webp',
                'designations'   => 'MBChB, MMed (Paediatrics)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Grace Muthoni is a dedicated paediatrician at Coast General Teaching and Referral Hospital in Mombasa. Her focus areas include neonatal care, childhood nutrition, and infectious diseases common in coastal Kenya. She is known for her compassionate approach to family-centred care.',
                'address'        => 'Coast General Teaching & Referral Hospital, Mombasa, Kenya',
                'educations'     => '<ul><li>MBChB – University of Nairobi (2009)</li><li>MMed Paediatrics – University of Nairobi (2014)</li></ul>',
                'experience'     => '<ul><li>Medical Officer – Coast General Hospital (2009–2011)</li><li>Paediatrics Registrar – KNH (2011–2014)</li><li>Consultant Paediatrician – Coast General Hospital (2014–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, University of Nairobi</li><li>MMed (Paediatrics)</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 4. Dr. Samuel Kipkoech ───────────────────────────────────────
            [
                'department_id'  => 3, // Ophthalmology
                'location_id'    => 8, // Nakuru
                'name'           => 'Dr. Samuel Kipkoech',
                'slug'           => 'dr-samuel-kipkoech',
                'email'          => 'samuel.kipkoech@medfind.ke',
                'phone'          => '+254-700-100-004',
                'fee'            => 2000,
                'image'          => 'uploads/website-images/dummy/doctor-4.webp',
                'designations'   => 'MBChB, MMed (Ophthalmology)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Samuel Kipkoech is an ophthalmologist at Nakuru Level 5 Hospital, specialising in cataract surgery, diabetic retinopathy, and glaucoma management. He has performed over 3,000 sight-restoring surgeries and participates in outreach programmes in the Rift Valley region.',
                'address'        => 'Nakuru Level 5 Hospital, Nakuru, Kenya',
                'educations'     => '<ul><li>MBChB – Moi University (2008)</li><li>MMed Ophthalmology – University of Nairobi (2014)</li></ul>',
                'experience'     => '<ul><li>Medical Officer – Nakuru Level 5 Hospital (2008–2011)</li><li>Ophthalmology Registrar – KNH (2011–2014)</li><li>Consultant Ophthalmologist – Nakuru Level 5 Hospital (2014–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, Moi University</li><li>MMed (Ophthalmology)</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 5. Dr. Fatuma Hassan ─────────────────────────────────────────
            [
                'department_id'  => 5, // Radiology
                'location_id'    => 6, // Mombasa
                'name'           => 'Dr. Fatuma Hassan',
                'slug'           => 'dr-fatuma-hassan',
                'email'          => 'fatuma.hassan@medfind.ke',
                'phone'          => '+254-700-100-005',
                'fee'            => 2200,
                'image'          => 'uploads/website-images/dummy/doctor-5.webp',
                'designations'   => 'MBChB, MMed (Radiology)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Fatuma Hassan is a consultant radiologist at the Aga Khan Hospital Mombasa. She specialises in CT, MRI and ultrasound imaging, with a particular interest in oncology radiology and interventional procedures. She trained at King\'s College Hospital London and brings international-standard diagnostics to the coast.',
                'address'        => 'Aga Khan Hospital, Vanga Rd, Mombasa, Kenya',
                'educations'     => '<ul><li>MBChB – University of Nairobi (2007)</li><li>MMed Radiology – University of Nairobi (2013)</li><li>Interventional Radiology Fellowship – King\'s College Hospital, London (2015)</li></ul>',
                'experience'     => '<ul><li>Radiology Registrar – KNH (2010–2013)</li><li>Consultant Radiologist – Aga Khan Mombasa (2015–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, University of Nairobi</li><li>MMed (Radiology)</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 6. Dr. Brian Otieno ──────────────────────────────────────────
            [
                'department_id'  => 6, // Urology
                'location_id'    => 7, // Kisumu
                'name'           => 'Dr. Brian Otieno',
                'slug'           => 'dr-brian-otieno',
                'email'          => 'brian.otieno@medfind.ke',
                'phone'          => '+254-700-100-006',
                'fee'            => 2800,
                'image'          => 'uploads/website-images/dummy/doctor-6.webp',
                'designations'   => 'MBChB, MMed (Urology)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Brian Otieno is a urologist at Jaramogi Oginga Odinga Teaching and Referral Hospital in Kisumu. He is one of the few urologists serving western Kenya and specialises in urinary tract disorders, prostate disease, and minimally invasive stone surgery. He leads urology training for medical students at Maseno University.',
                'address'        => 'Jaramogi Oginga Odinga Teaching & Referral Hospital, Kisumu, Kenya',
                'educations'     => '<ul><li>MBChB – Maseno University (2006)</li><li>MMed Urology – University of Nairobi (2013)</li></ul>',
                'experience'     => '<ul><li>Medical Officer – JOOTRH Kisumu (2006–2010)</li><li>Urology Registrar – Kenyatta National Hospital (2010–2013)</li><li>Consultant Urologist – JOOTRH Kisumu (2013–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, Maseno University</li><li>MMed (Urology)</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 7. Dr. Njeri Kamau ───────────────────────────────────────────
            [
                'department_id'  => 2, // Cardiology
                'location_id'    => 5, // Nairobi
                'name'           => 'Dr. Njeri Kamau',
                'slug'           => 'dr-njeri-kamau',
                'email'          => 'njeri.kamau@medfind.ke',
                'phone'          => '+254-700-100-007',
                'fee'            => 2500,
                'image'          => 'uploads/website-images/dummy/doctor-7.webp',
                'designations'   => 'MBChB, MMed (Cardiology), FESC',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Njeri Kamau is a consultant cardiologist and Fellow of the European Society of Cardiology, practising at Nairobi Hospital. She specialises in interventional cardiology, including coronary angioplasty and stenting. She is passionate about preventive cardiology and women\'s heart health.',
                'address'        => 'Nairobi Hospital, Argwings Kodhek Rd, Nairobi, Kenya',
                'educations'     => '<ul><li>MBChB – University of Nairobi (2006)</li><li>MMed Cardiology – University of Nairobi (2012)</li><li>Interventional Cardiology Fellowship – Leiden University Medical Centre, Netherlands (2014)</li></ul>',
                'experience'     => '<ul><li>Cardiology Registrar – KNH (2009–2012)</li><li>Interventional Fellow – Leiden UMC (2012–2014)</li><li>Consultant Cardiologist – Nairobi Hospital (2014–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, University of Nairobi</li><li>MMed (Cardiology)</li><li>FESC – Fellow of the European Society of Cardiology</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 8. Dr. Peter Mwangi ──────────────────────────────────────────
            [
                'department_id'  => 1, // Neurology
                'location_id'    => 8, // Nakuru
                'name'           => 'Dr. Peter Mwangi',
                'slug'           => 'dr-peter-mwangi',
                'email'          => 'peter.mwangi@medfind.ke',
                'phone'          => '+254-700-100-008',
                'fee'            => 2000,
                'image'          => 'uploads/website-images/dummy/doctor-8.webp',
                'designations'   => 'MBChB, MMed (Neurology)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Peter Mwangi is a neurologist serving the Rift Valley region from Nakuru. His clinical interest is in epilepsy care — a condition significantly underserved in rural Kenya. He runs community epilepsy clinics and has been instrumental in expanding neurological services to Nakuru County.',
                'address'        => 'Nakuru Level 5 Hospital, Nakuru, Kenya',
                'educations'     => '<ul><li>MBChB – Egerton University (2007)</li><li>MMed Neurology – University of Nairobi (2014)</li></ul>',
                'experience'     => '<ul><li>Medical Officer – Nakuru Level 5 Hospital (2007–2011)</li><li>Neurology Registrar – KNH (2011–2014)</li><li>Consultant Neurologist – Nakuru Level 5 Hospital (2014–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, Egerton University</li><li>MMed (Neurology)</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 9. Dr. Aisha Abdi ────────────────────────────────────────────
            [
                'department_id'  => 4, // Pediatric
                'location_id'    => 5, // Nairobi
                'name'           => 'Dr. Aisha Abdi',
                'slug'           => 'dr-aisha-abdi',
                'email'          => 'aisha.abdi@medfind.ke',
                'phone'          => '+254-700-100-009',
                'fee'            => 1500,
                'image'          => 'uploads/website-images/dummy/doctor-9.webp',
                'designations'   => 'MBChB, MMed (Paediatrics), DCH',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Aisha Abdi is a paediatrician at Gertrude\'s Children\'s Hospital in Nairobi, one of Kenya\'s premier children\'s hospitals. She specialises in paediatric oncology and palliative care for children, and is a member of the Kenya Paediatric Association.',
                'address'        => "Gertrude's Children's Hospital, Muthaiga Rd, Nairobi, Kenya",
                'educations'     => '<ul><li>MBChB – University of Nairobi (2008)</li><li>DCH – Royal College of Paediatrics, UK (2011)</li><li>MMed Paediatrics – University of Nairobi (2014)</li></ul>',
                'experience'     => '<ul><li>Paediatrics Registrar – KNH (2011–2014)</li><li>Consultant Paediatrician – Gertrude\'s Children\'s Hospital (2014–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, University of Nairobi</li><li>MMed (Paediatrics)</li><li>DCH, Royal College of Paediatrics</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 10. Dr. James Kariuki ────────────────────────────────────────
            [
                'department_id'  => 3, // Ophthalmology
                'location_id'    => 7, // Kisumu
                'name'           => 'Dr. James Kariuki',
                'slug'           => 'dr-james-kariuki',
                'email'          => 'james.kariuki@medfind.ke',
                'phone'          => '+254-700-100-010',
                'fee'            => 1800,
                'image'          => 'uploads/website-images/dummy/doctor-10.webp',
                'designations'   => 'MBChB, MMed (Ophthalmology)',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. James Kariuki is an ophthalmologist at Kisumu Eye Hospital. He focuses on community eye health and has run eye camps in Siaya, Homa Bay and Kisii. His clinical specialties include glaucoma, paediatric ophthalmology, and corneal disease.',
                'address'        => 'Kisumu Eye Hospital, Oginga Odinga St, Kisumu, Kenya',
                'educations'     => '<ul><li>MBChB – Maseno University (2008)</li><li>MMed Ophthalmology – University of Nairobi (2015)</li></ul>',
                'experience'     => '<ul><li>Medical Officer – JOOTRH (2008–2012)</li><li>Ophthalmology Registrar – KNH (2012–2015)</li><li>Ophthalmologist – Kisumu Eye Hospital (2015–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, Maseno University</li><li>MMed (Ophthalmology)</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 11. Dr. Margaret Waweru ──────────────────────────────────────
            [
                'department_id'  => 5, // Radiology
                'location_id'    => 5, // Nairobi
                'name'           => 'Dr. Margaret Waweru',
                'slug'           => 'dr-margaret-waweru',
                'email'          => 'margaret.waweru@medfind.ke',
                'phone'          => '+254-700-100-011',
                'fee'            => 3000,
                'image'          => 'uploads/website-images/dummy/doctor-11.webp',
                'designations'   => 'MBChB, MMed (Radiology), FRCR',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Margaret Waweru is a Fellow of the Royal College of Radiologists and a consultant radiologist at MP Shah Hospital, Nairobi. She is one of Kenya\'s leading experts in breast imaging and musculoskeletal radiology, and has helped establish Kenya\'s first dedicated breast cancer screening programme.',
                'address'        => 'MP Shah Hospital, Shivachi Rd, Nairobi, Kenya',
                'educations'     => '<ul><li>MBChB – University of Nairobi (2005)</li><li>MMed Radiology – University of Nairobi (2011)</li><li>FRCR – Royal College of Radiologists, UK (2013)</li></ul>',
                'experience'     => '<ul><li>Radiology Registrar – KNH (2008–2011)</li><li>Fellowship – Royal Marsden Hospital, London (2011–2013)</li><li>Consultant Radiologist – MP Shah Hospital (2013–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, University of Nairobi</li><li>MMed (Radiology)</li><li>FRCR, Royal College of Radiologists</li><li>Licensed by KMPDC</li></ul>',
            ],
            // ── 12. Dr. Collins Omondi ───────────────────────────────────────
            [
                'department_id'  => 6, // Urology
                'location_id'    => 5, // Nairobi
                'name'           => 'Dr. Collins Omondi',
                'slug'           => 'dr-collins-omondi',
                'email'          => 'collins.omondi@medfind.ke',
                'phone'          => '+254-700-100-012',
                'fee'            => 3500,
                'image'          => 'uploads/website-images/dummy/doctor-12.webp',
                'designations'   => 'MBChB, MMed (Urology), FEBU',
                'licensing_body' => 'Kenya Medical Practitioners & Dentists Council',
                'about'          => 'Dr. Collins Omondi is a Fellow of the European Board of Urology and a consultant at the Aga Khan University Hospital, Nairobi. He specialises in laparoscopic urology, kidney stone disease, and uro-oncology. He has trained under leading urologists in Germany and regularly publishes in peer-reviewed journals.',
                'address'        => 'Aga Khan University Hospital, 3rd Parklands Ave, Nairobi, Kenya',
                'educations'     => '<ul><li>MBChB – University of Nairobi (2004)</li><li>MMed Urology – University of Nairobi (2011)</li><li>FEBU – European Board of Urology (2013)</li></ul>',
                'experience'     => '<ul><li>Urology Registrar – KNH (2008–2011)</li><li>Laparoscopic Urology Fellowship – Hamburg, Germany (2011–2013)</li><li>Consultant Urologist – AKUH Nairobi (2013–Present)</li></ul>',
                'qualifications' => '<ul><li>MBChB, University of Nairobi</li><li>MMed (Urology)</li><li>FEBU, European Board of Urology</li><li>Licensed by KMPDC</li></ul>',
            ],
        ];
    }
}
