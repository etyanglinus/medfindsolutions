<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Doctor\app\Models\Service;

class DoctorServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = <<<'SERVICES'
Dental Clinic
Eye Clinic (Ophthalmology)
ENT Clinic
Women's Health & Gynecology Clinic
Fertility Clinic (IVF & Reproductive Health)
Antenatal & Maternity Clinic
Pediatric Clinic
Vaccination & Child Wellness Clinic
Cardiology Clinic
Orthopedic & Spine Clinic
Physiotherapy & Rehabilitation Clinic
Neurology Clinic
Neurosurgery Clinic
Psychiatry & Mental Health Clinic
Psychology & Counseling Clinic
Dermatology & Skin Clinic
Cosmetic Dermatology Clinic
Plastic & Reconstructive Surgery Clinic
Diabetes Clinic
Endocrinology Clinic
Hypertension Clinic
Gastroenterology & Endoscopy Clinic
Liver Clinic (Hepatology)
Renal Care Clinic (Dialysis)
Urology Clinic
Men's Health Clinic
Chest & Respiratory Clinic
Asthma Clinic
Allergy & Immunology Clinic
Rheumatology Clinic
Pain Management Clinic
Oncology (Cancer) Clinic
Chemotherapy Clinic
Palliative Care Clinic
Infectious Disease Clinic
HIV Care & Treatment Clinic
Tuberculosis (TB) Clinic
Sexual Health & STI Clinic
Family Planning Clinic
Breast Care Clinic
Colorectal Clinic
General Surgery Clinic
Bariatric (Weight Loss) Clinic
Nutrition & Dietetics Clinic
Sleep Disorders Clinic
Sports Medicine Clinic
Occupational Health Clinic
Travel Medicine Clinic
Geriatric (Elderly Care) Clinic
Memory & Dementia Clinic
Neonatal Follow-up Clinic
Developmental & Autism Clinic
Speech & Language Therapy Clinic
Audiology & Hearing Clinic
Prosthetics & Orthotics Clinic
Wound Care Clinic
Diabetic Foot Clinic
Vascular Clinic
Heart Failure Clinic
Anticoagulation Clinic
Chronic Pain Clinic
Lifestyle Medicine Clinic
Preventive Health & Wellness Clinic
Executive Health Screening Clinic
Medical Aesthetics Clinic
Hair Restoration Clinic
Occupational Therapy Clinic
Chiropractic Clinic
Hepatobiliary Clinic
Endoscopy Clinic
Minor Surgery Clinic
Day Surgery Clinic
Vaccination & Travel Vaccination Clinic
Osteoporosis Clinic
Arthritis Clinic
Epilepsy Clinic
Migraine & Headache Clinic
Stroke Follow-up Clinic
Smoking Cessation Clinic
Obesity Management Clinic
Hormone Replacement Therapy (HRT) Clinic
SERVICES;

        foreach (preg_split('/\R/', $services) as $name) {
            Service::updateOrCreate(['name' => $name], ['status' => true]);
        }
    }
}
