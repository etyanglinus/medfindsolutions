<?php

namespace Database\Seeders;

use App\Models\AboutUsPage;
use App\Models\AboutUsPageTranslation;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data for 'about_us_pages' table
        $aboutUsPageData = [
            'about_image'      => 'uploads/website-images/dummy/about_image.webp',
            'background_image' => 'uploads/website-images/dummy/background_image.webp',
            'mission_image'    => 'uploads/website-images/dummy/mission_image.webp',
            'vision_image'     => 'uploads/website-images/dummy/vision_image.webp',
        ];

        $aboutUsPage = AboutUsPage::create($aboutUsPageData);

        $translations = [
            [
                'lang_code'           => 'en',
                'about_description'   => "<h1>Special Doctors Are Dedicated to Our Patients</h1><p>We provide top-notch medical care and easy appointment scheduling. Our skilled healthcare team ensures personalized, compassionate treatment. Book your appointment today for exceptional care.</p><p>We have built a comprehensive database of doctors and specialists from various fields, allowing you to choose the right healthcare provider based on your specific needs. Whether you're looking for a general practitioner, a specialist, or a surgeon, our user-friendly interface makes it easy to find and schedule appointments. Our commitment to transparency and reliability ensures that you receive the highest standard of care.</p>",
                'mission_description' => "<h1><strong>Our Mission</strong></h1><p>We strive to offer the best medical care with a focus on patient well-being and accessibility.</p><p>We are committed to fostering a community of healthcare excellence. Our platform is built on the principles of trust, transparency, and reliability. We believe that everyone deserves access to quality healthcare, and we are dedicated to making this a reality through our innovative approach. Our mission is to empower individuals to take control of their health by providing them with the tools and resources they need.</p>",
                'vision_description'  => "<h1>Our Vision</h1><p>To be a leading healthcare provider known for innovation and excellence in patient care.</p><p>We aspire to create a healthcare ecosystem where patients and providers are seamlessly connected. By leveraging the latest technologies, we aim to provide a platform that not only meets but exceeds the expectations of our users. Our vision is to be the go-to resource for anyone seeking quality healthcare, making a positive impact on the lives of individuals and communities.</p>",
            ],
            [
                'lang_code'           => 'ar',
                'about_description'   => '<h1>الأطباء المتخصصون مكرسون لمرضانا</h1><p>نحن نقدم رعاية طبية ممتازة وتحديد مواعيد سهل. فريقنا الطبي الماهر يضمن لك علاجًا مخصصًا ومتعاطفًا. احجز موعدك اليوم لتجربة رعاية استثنائية.</p><p>لقد قمنا ببناء قاعدة بيانات شاملة للأطباء والمتخصصين من مختلف المجالات، مما يسمح لك باختيار مقدم الرعاية الصحية المناسب بناءً على احتياجاتك الخاصة. سواء كنت تبحث عن طبيب عام أو متخصص أو جراح، فإن واجهتنا سهلة الاستخدام تجعل من السهل العثور على المواعيد وجدولتها. يضمن التزامنا بالشفافية والموثوقية حصولك على أعلى مستوى من الرعاية.</p>',
                'mission_description' => '<h1><strong>مهمتنا</strong></h1><p>نسعى لتقديم أفضل رعاية طبية مع التركيز على رفاهية المرضى وسهولة الوصول.</p><p>نحن ملتزمون بتعزيز مجتمع التميز في الرعاية الصحية. تم بناء منصتنا على مبادئ الثقة والشفافية والموثوقية. نحن نؤمن بأن الجميع يستحق الحصول على رعاية صحية عالية الجودة، ونحن ملتزمون بتحويل ذلك إلى حقيقة من خلال نهجنا المبتكر. مهمتنا هي تمكين الأفراد من السيطرة على صحتهم من خلال تزويدهم بالأدوات والموارد التي يحتاجون إليها.</p>',
                'vision_description'  => '<h1>رؤيتنا</h1><p>أن نكون مزودين رعاية صحية رائدين يعرفون بالابتكار والتميز في رعاية المرضى.</p><p>نحن نطمح إلى إنشاء نظام بيئي للرعاية الصحية حيث يتم ربط المرضى ومقدمي الخدمات بسلاسة. ومن خلال الاستفادة من أحدث التقنيات، نهدف إلى توفير منصة لا تلبي توقعات مستخدمينا فحسب، بل تتجاوزها أيضًا. تتمثل رؤيتنا في أن نكون المصدر الأمثل لأي شخص يسعى للحصول على رعاية صحية عالية الجودة، وإحداث تأثير إيجابي على حياة الأفراد والمجتمعات.</p>',
            ],
        ];

        foreach ($translations as $translation) {
            AboutUsPageTranslation::create([
                'about_us_page_id'    => $aboutUsPage->id,
                'lang_code'           => $translation['lang_code'],
                'about_description'   => $translation['about_description'],
                'mission_description' => $translation['mission_description'],
                'vision_description'  => $translation['vision_description'],
            ]);
        }
    }
}
