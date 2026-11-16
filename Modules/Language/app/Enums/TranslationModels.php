<?php

namespace Modules\Language\app\Enums;

enum TranslationModels: string {
/**
 * whenever update new case also update getAll() method
 * to return all values in array
 */
case AboutusPage = "App\Models\AboutUsPageTranslation";
case Blog = "Modules\Blog\app\Models\BlogTranslation";
case BlogCategory = "Modules\Blog\app\Models\BlogCategoryTranslation";
case ContactInfo = "Modules\ContactMessage\app\Models\ContactInfoTranslation";
case CustomizablePage = "Modules\PageBuilder\app\Models\CustomizablePageTranslation";
case Counter = "Modules\HomeSection\app\Models\CounterTranslation";
case Day = "Modules\Day\app\Models\DayTranslation";
case Department = "Modules\Doctor\app\Models\DepartmentTranslation";
case DepartmentFaq = "Modules\Doctor\app\Models\DepartmentFaqTranslation";
case Doctor = "Modules\Doctor\app\Models\DoctorTranslation";
case Facility = "Modules\Doctor\app\Models\FacilityTranslation";
case FaqCategory = "Modules\Faq\app\Models\FaqCategoryTranslation";
case Faq = "Modules\Faq\app\Models\FaqTranslation";
case Feature = "Modules\HomeSection\app\Models\FeatureTranslation";
case Location = "Modules\Doctor\app\Models\LocationTranslation";
case Habit = "Modules\Habit\app\Models\HabitTranslation";
case Testimonial = "Modules\Testimonial\app\Models\TestimonialTranslation";
case Menu = "Modules\CustomMenu\app\Models\MenuTranslation";
case MenuItem = "Modules\CustomMenu\app\Models\MenuItemTranslation";
case Medicine = "Modules\Medicine\app\Models\MedicineTranslation";
case MedicineType = "Modules\Medicine\app\Models\MedicineTypeTranslation";
case SectionControl = "Modules\HomeSection\app\Models\SectionControlTranslation";
case Service = "Modules\Service\app\Models\ServiceTranslation";
case ServiceFaq = "Modules\Service\app\Models\ServiceFaqTranslation";
case SubscriberContent = "Modules\NewsLetter\app\Models\SubscriberContentTranslation";
case WorkSection = "Modules\HomeSection\app\Models\WorkSectionTranslation";
case WorkSectionFaq = "Modules\HomeSection\app\Models\WorkSectionFaqTranslation";

    public static function getAll(): array {
        return [
            self::AboutusPage->value,
            self::Blog->value,
            self::BlogCategory->value,
            self::ContactInfo->value,
            self::CustomizablePage->value,
            self::Counter->value,
            self::Day->value,
            self::Department->value,
            self::DepartmentFaq->value,
            self::Doctor->value,
            self::Facility->value,
            self::FaqCategory->value,
            self::Faq->value,
            self::Feature->value,
            self::Habit->value,
            self::Location->value,
            self::Testimonial->value,
            self::Menu->value,
            self::MenuItem->value,
            self::Medicine->value,
            self::MedicineType->value,
            self::SectionControl->value,
            self::Service->value,
            self::ServiceFaq->value,
            self::SubscriberContent->value,
            self::WorkSection->value,
            self::WorkSectionFaq->value,
        ];
    }

    public static function igonreColumns(): array {
        return [
            'id',
            'lang_code',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }
}
