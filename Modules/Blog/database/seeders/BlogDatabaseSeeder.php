<?php

namespace Modules\Blog\database\seeders;

use App\Models\Admin;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Modules\Blog\app\Models\Blog;
use Modules\Blog\app\Models\BlogComment;
use Modules\Blog\app\Models\BlogCategory;
use Modules\Blog\app\Models\BlogTranslation;
use Modules\Blog\app\Models\BlogCategoryTranslation;

class BlogDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $dummyCategories = [
            [
                'slug'         => "personal-treatment",
                'translations' => [
                    ['lang_code' => 'en', 'title' => "Personal Treatment"],
                    ['lang_code' => 'ar', 'title' => "العلاج الشخصي"],
                ],
            ],
            [
                'slug'         => "mental-health",
                'translations' => [
                    ['lang_code' => 'en', 'title' => "Mental Health"],
                    ['lang_code' => 'ar', 'title' => "الصحة النفسية"],
                ],
            ],
            [
                'slug'         => "dental-health",
                'translations' => [
                    ['lang_code' => 'en', 'title' => "Dental Health"],
                    ['lang_code' => 'ar', 'title' => "صحة الأسنان"],
                ],
            ],
            [
                'slug'         => "health-care",
                'translations' => [
                    ['lang_code' => 'en', 'title' => "Health care"],
                    ['lang_code' => 'ar', 'title' => "الرعاىة الصحية"],
                ],
            ],
        ];
        foreach ($dummyCategories as $item) {
            $category = new BlogCategory();
            $category->slug = $item['slug'];
            $category->status = true;
            $category->save();

            foreach ($item['translations'] as $translation) {
                $categoryTranslation = new BlogCategoryTranslation();
                $categoryTranslation->blog_category_id = $category->id;
                $categoryTranslation->lang_code = $translation['lang_code'];
                $categoryTranslation->title = $translation['title'];
                $categoryTranslation->save();
            }
        }

        //Blogs
        $dummyBlogs = [
            [
                'slug'             => "oral-hygiene",
                'blog_category_id' => 3,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-1.webp',
                'image'            => 'uploads/website-images/dummy/blog-1.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Best Practices for a Healthy Smile",
                        'short_description' => "Maintain a healthy smile with these best practices for oral hygiene.",
                        'description'       => "<p>Good oral hygiene is essential for a healthy smile and overall dental health. Here are some best practices:<br><br>1. Brush your teeth twice a day with fluoride toothpaste.<br>2. Floss daily to remove plaque between teeth.<br>3. Use mouthwash to help reduce plaque and freshen breath.<br>4. Replace your toothbrush every 3-4 months.<br>5. Schedule regular dental check-ups.<br></p>",
                        'seo_title'         => "Oral Hygiene Best Practices",
                        'seo_description'   => "Discover the best practices for maintaining a healthy smile and good oral hygiene.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "الصحة الفموية: أفضل الممارسات لابتسامة صحية",
                        'short_description' => "حافظ على ابتسامة صحية مع هذه أفضل الممارسات للصحة الفموية.",
                        'description'       => "<p>الصحة الفموية الجيدة أساسية لابتسامة صحية وصحة أسنان عامة. إليك بعض أفضل الممارسات:<br><br>1. تنظيف أسنانك مرتين في اليوم باستخدام معجون أسنان يحتوي على الفلورايد.<br>2. استخدام الخيط يوميًا لإزالة البلاك بين الأسنان.<br>3. استخدام غسول الفم للمساعدة في تقليل البلاك وإنعاش التنفس.<br>4. استبدال فرشاة الأسنان كل 3-4 أشهر.<br>5. تحديد مواعيد منتظمة لفحوصات الأسنان.<br></p>",
                        'seo_title'         => "أفضل ممارسات الصحة الفموية",
                        'seo_description'   => "اكتشف أفضل الممارسات للحفاظ على ابتسامة صحية وصحة فموية جيدة.",
                    ],
                ],
            ],
            [
                'slug'             => "healthy-skin",
                'blog_category_id' => 1,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-2.webp',
                'image'            => 'uploads/website-images/dummy/blog-2.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Achieving Healthy Skin: Tips and Tricks",
                        'short_description' => "Learn tips and tricks for achieving and maintaining healthy skin.",
                        'description'       => "<p>Healthy skin is a reflection of your overall health. Here are some tips and tricks to achieve and maintain healthy skin:<br><br>1. Follow a daily skincare routine.<br>2. Use products suitable for your skin type.<br>3. Stay hydrated and eat a balanced diet.<br>4. Avoid excessive sun exposure.<br>5. Get regular skin check-ups.<br></p>",
                        'seo_title'         => "Healthy Skin Tips",
                        'seo_description'   => "Discover tips and tricks for achieving and maintaining healthy skin.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "تحقيق بشرة صحية: نصائح وحيل",
                        'short_description' => "تعلم نصائح وحيل لتحقيق والحفاظ على بشرة صحية.",
                        'description'       => "<p>البشرة الصحية هي انعكاس لصحتك العامة. إليك بعض النصائح والحيل لتحقيق والحفاظ على بشرة صحية:<br><br>1. اتباع روتين يومي للعناية بالبشرة.<br>2. استخدام المنتجات المناسبة لنوع بشرتك.<br>3. الحفاظ على الترطيب واتباع نظام غذائي متوازن.<br>4. تجنب التعرض المفرط لأشعة الشمس.<br>5. الحصول على فحوصات جلدية منتظمة.<br></p>",
                        'seo_title'         => "نصائح للبشرة الصحية",
                        'seo_description'   => "اكتشف نصائح وحيل لتحقيق والحفاظ على بشرة صحية.",
                    ],
                ],
            ],
            [
                'slug'             => "childrens-health",
                'blog_category_id' => 4,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-3.webp',
                'image'            => 'uploads/website-images/dummy/blog-3.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Children's Health: Important Check-ups",
                        'short_description' => "Regular check-ups are crucial for your child's health and development.",
                        'description'       => "<p>Ensuring your child has regular check-ups is vital for their ongoing health and development. These visits can help catch potential issues early and provide guidance on nutrition and safety.<br><br>1. Growth and development tracking.<br>2. Immunization updates.<br>3. Health screenings.<br>4. Behavioral and learning assessments.<br>5. Nutritional guidance.<br></p>",
                        'seo_title'         => "Children's Health Check-ups",
                        'seo_description'   => "Learn about the importance of regular health check-ups for children's health and development.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "صحة الأطفال: الفحوصات الهامة",
                        'short_description' => "الفحوصات المنتظمة ضرورية لصحة طفلك ونموه.",
                        'description'       => "<p>ضمان أن طفلك يجري الفحوصات المنتظمة ضروري لصحتهم ونموهم المستمر. يمكن لهذه الزيارات أن تساعد في اكتشاف المشكلات المحتملة مبكرًا وتقديم الإرشادات حول التغذية والسلامة.<br><br>1. تتبع النمو والتطور.<br>2. تحديث التطعيمات.<br>3. الفحوصات الصحية.<br>4. تقييمات السلوك والتعلم.<br>5. إرشادات التغذية.<br></p>",
                        'seo_title'         => "فحوصات صحة الأطفال",
                        'seo_description'   => "تعرف على أهمية الفحوصات الصحية المنتظمة لصحة الأطفال ونموهم.",
                    ],
                ],
            ],
            [
                'slug'             => "mental-health",
                'blog_category_id' => 2,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-4.webp',
                'image'            => 'uploads/website-images/dummy/blog-4.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Maintaining Mental Health During Stressful",
                        'short_description' => "Here are some strategies to help maintain your mental health during stressful periods.",
                        'description'       => "<p>It's essential to take care of your mental health, especially during stressful times. Here are some strategies to help you stay mentally healthy:<br><br>1. Practice mindfulness and meditation.<br>2. Stay connected with loved ones.<br>3. Maintain a healthy routine.<br>4. Seek professional help if needed.<br>5. Engage in physical activity.<br></p>",
                        'seo_title'         => "Mental Health Strategies",
                        'seo_description'   => "Learn effective strategies to maintain mental health during stressful times.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "الحفاظ على الصحة النفسية في أوقات الإجهاد",
                        'short_description' => "إليك بعض الاستراتيجيات للمساعدة في الحفاظ على صحتك النفسية في فترات الإجهاد.",
                        'description'       => "<p>من الضروري العناية بالصحة النفسية، خاصة في أوقات الإجهاد. إليك بعض الاستراتيجيات لمساعدتك في الحفاظ على الصحة النفسية:<br><br>1. ممارسة اليقظة والتأمل.<br>2. البقاء على اتصال مع الأحباء.<br>3. الحفاظ على روتين صحي.<br>4. طلب المساعدة المهنية إذا لزم الأمر.<br>5. المشاركة في النشاط البدني.<br></p>",
                        'seo_title'         => "استراتيجيات الصحة النفسية",
                        'seo_description'   => "تعرف على استراتيجيات فعالة للحفاظ على الصحة النفسية في أوقات الإجهاد.",
                    ],
                ],
            ],
            [
                'slug'             => "whitening-teeth",
                'blog_category_id' => 3,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-5.webp',
                'image'            => 'uploads/website-images/dummy/blog-5.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Teeth Whitening: Safe and Effective Methods",
                        'short_description' => "Explore safe and effective methods for whitening your teeth and achieving a brighter smile.",
                        'description'       => "<p>Teeth whitening can enhance your smile and boost your confidence. Here are some safe and effective methods to consider:<br><br>1. Professional teeth whitening treatments.<br>2. Over-the-counter whitening products.<br>3. Natural whitening remedies.<br>4. Maintaining good oral hygiene to prolong results.<br>5. Consulting with your dentist about the best options.<br></p>",
                        'seo_title'         => "Teeth Whitening Methods",
                        'seo_description'   => "Discover safe and effective methods for whitening your teeth and achieving a brighter smile.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "تبييض الأسنان: طرق آمنة وفعالة",
                        'short_description' => "استكشف طرقًا آمنة وفعالة لتبييض أسنانك والحصول على ابتسامة أكثر إشراقًا.",
                        'description'       => "<p>تبييض الأسنان يمكن أن يعزز ابتسامتك ويزيد من ثقتك بنفسك. إليك بعض الطرق الآمنة والفعالة التي يمكنك التفكير فيها:<br><br>1. علاجات تبييض الأسنان الاحترافية.<br>2. منتجات التبييض التي تباع بدون وصفة طبية.<br>3. علاجات التبييض الطبيعية.<br>4. الحفاظ على صحة الفم الجيدة لتمديد النتائج.<br>5. استشارة طبيب الأسنان حول أفضل الخيارات.<br></p>",
                        'seo_title'         => "طرق تبييض الأسنان",
                        'seo_description'   => "اكتشف طرقًا آمنة وفعالة لتبييض أسنانك والحصول على ابتسامة أكثر إشراقًا.",
                    ],
                ],
            ],
            [
                'slug'             => "healthy-eating",
                'blog_category_id' => 4,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-6.webp',
                'image'            => 'uploads/website-images/dummy/blog-6.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Healthy Eating for a Better Lifestyle",
                        'short_description' => "Adopting healthy eating habits can significantly improve your overall well-being.",
                        'description'       => "<p>Healthy eating is crucial for maintaining good health and preventing chronic diseases. Here are some tips to help you adopt a healthier diet:<br><br>1. Eat a variety of foods.<br>2. Include plenty of fruits and vegetables.<br>3. Choose whole grains.<br>4. Limit sugar and salt intake.<br>5. Stay hydrated.<br></p>",
                        'seo_title'         => "Healthy Eating Tips",
                        'seo_description'   => "Discover tips for adopting healthy eating habits to improve your overall well-being.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "الأكل الصحي لنمط حياة أفضل",
                        'short_description' => "يمكن أن يؤدي تبني عادات الأكل الصحية إلى تحسين صحتك العامة بشكل كبير.",
                        'description'       => "<p>الأكل الصحي ضروري للحفاظ على صحة جيدة ومنع الأمراض المزمنة. إليك بعض النصائح لمساعدتك في تبني نظام غذائي صحي:<br><br>1. تناول مجموعة متنوعة من الأطعمة.<br>2. تناول الكثير من الفواكه والخضروات.<br>3. اختيار الحبوب الكاملة.<br>4. الحد من تناول السكر والملح.<br>5. البقاء رطبًا.<br></p>",
                        'seo_title'         => "نصائح الأكل الصحي",
                        'seo_description'   => "اكتشف نصائح لتبني عادات الأكل الصحي لتحسين صحتك العامة.",
                    ],
                ],
            ],
            [
                'slug'             => "exercise-benefits",
                'blog_category_id' => 1,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-7.webp',
                'image'            => 'uploads/website-images/dummy/blog-7.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "The Benefits of Regular Exercise",
                        'short_description' => "Regular exercise offers numerous health benefits, including improved mood and energy levels.",
                        'description'       => "<p>Engaging in regular exercise is one of the best things you can do for your health. Here are some benefits of staying active:<br><br>1. Improves mental health.<br>2. Boosts energy levels.<br>3. Aids in weight management.<br>4. Strengthens muscles and bones.<br>5. Reduces risk of chronic diseases.<br></p>",
                        'seo_title'         => "Exercise Benefits",
                        'seo_description'   => "Learn about the numerous health benefits of regular exercise.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "فوائد التمارين الرياضية المنتظمة",
                        'short_description' => "توفر التمارين الرياضية المنتظمة العديد من الفوائد الصحية، بما في ذلك تحسين المزاج ومستويات الطاقة.",
                        'description'       => "<p>المشاركة في التمارين الرياضية المنتظمة هي واحدة من أفضل الأشياء التي يمكنك القيام بها لصحتك. إليك بعض فوائد البقاء نشطًا:<br><br>1. تحسين الصحة النفسية.<br>2. زيادة مستويات الطاقة.<br>3. المساعدة في إدارة الوزن.<br>4. تقوية العضلات والعظام.<br>5. تقليل مخاطر الأمراض المزمنة.<br></p>",
                        'seo_title'         => "فوائد التمارين الرياضية",
                        'seo_description'   => "تعرف على العديد من الفوائد الصحية للتمارين الرياضية المنتظمة.",
                    ],
                ],
            ],
            [
                'slug'             => "sleep-hygiene",
                'blog_category_id' => 1,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-8.webp',
                'image'            => 'uploads/website-images/dummy/blog-8.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Improving Sleep Hygiene Habits",
                        'short_description' => "Good sleep hygiene practices can help you achieve better and more restful sleep.",
                        'description'       => "<p>Maintaining good sleep hygiene is essential for overall health and well-being. Here are some tips to improve your sleep hygiene:<br><br>1. Stick to a regular sleep schedule.<br>2. Create a relaxing bedtime routine.<br>3. Make your bedroom sleep-friendly.<br>4. Limit screen time before bed.<br>5. Avoid caffeine and heavy meals at night.<br></p>",
                        'seo_title'         => "Sleep Hygiene Tips",
                        'seo_description'   => "Discover tips for improving your sleep hygiene to achieve better and more restful sleep.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "تحسين نظافة النوم",
                        'short_description' => "يمكن أن تساعد ممارسات نظافة النوم الجيدة في تحقيق نوم أفضل وأكثر راحة.",
                        'description'       => "<p>الحفاظ على نظافة النوم الجيدة ضروري للصحة العامة والرفاهية. إليك بعض النصائح لتحسين نظافة نومك:<br><br>1. الالتزام بجدول نوم منتظم.<br>2. خلق روتين مريح لوقت النوم.<br>3. جعل غرفة النوم صديقة للنوم.<br>4. الحد من وقت الشاشة قبل النوم.<br>5. تجنب الكافيين والوجبات الثقيلة في الليل.<br></p>",
                        'seo_title'         => "نصائح نظافة النوم",
                        'seo_description'   => "اكتشف نصائح لتحسين نظافة نومك لتحقيق نوم أفضل وأكثر راحة.",
                    ],
                ],
            ],
            [
                'slug'             => "heart-health",
                'blog_category_id' => 4,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-9.webp',
                'image'            => 'uploads/website-images/dummy/blog-9.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Heart Health: Tips for a Healthy Heart",
                        'short_description' => "Maintaining a healthy heart is crucial for overall well-being. Here are some tips.",
                        'description'       => "<p>Your heart health is vital for your overall well-being. Follow these tips to maintain a healthy heart:<br><br>1. Eat a heart-healthy diet.<br>2. Exercise regularly.<br>3. Avoid smoking.<br>4. Manage stress effectively.<br>5. Monitor your blood pressure and cholesterol.<br></p>",
                        'seo_title'         => "Heart Health Tips",
                        'seo_description'   => "Learn tips for maintaining a healthy heart and overall well-being.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "صحة القلب: نصائح لقلب صحي",
                        'short_description' => "الحفاظ على صحة القلب ضروري للرفاهية العامة. إليك بعض النصائح.",
                        'description'       => "<p>صحة قلبك ضرورية لرفاهيتك العامة. اتبع هذه النصائح للحفاظ على صحة قلبك:<br><br>1. تناول نظام غذائي صحي للقلب.<br>2. ممارسة التمارين الرياضية بانتظام.<br>3. تجنب التدخين.<br>4. إدارة الإجهاد بفعالية.<br>5. مراقبة ضغط الدم والكوليسترول.<br></p>",
                        'seo_title'         => "نصائح لصحة القلب",
                        'seo_description'   => "تعلم نصائح للحفاظ على صحة القلب والرفاهية العامة.",
                    ],
                ],
            ],
            [
                'slug'             => "immunity-boost",
                'blog_category_id' => 1,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-10.webp',
                'image'            => 'uploads/website-images/dummy/blog-10.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "How to Boost Your Immune System",
                        'short_description' => "A strong immune system can help protect you from illnesses. Learn how to boost yours.",
                        'description'       => "<p>Boosting your immune system can help protect you from various illnesses. Here are some ways to strengthen your immunity:<br><br>1. Eat a balanced diet.<br>2. Exercise regularly.<br>3. Get enough sleep.<br>4. Stay hydrated.<br>5. Manage stress.<br></p>",
                        'seo_title'         => "Boost Your Immune System",
                        'seo_description'   => "Learn effective ways to boost your immune system and protect yourself from illnesses.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "كيفية تعزيز جهازك المناعي",
                        'short_description' => "يمكن أن يساعدك جهاز المناعة القوي في الحماية من الأمراض. تعلم كيفية تعزيز جهازك.",
                        'description'       => "<p>يمكن أن يساعد تعزيز جهازك المناعي في الحماية من الأمراض المختلفة. إليك بعض الطرق لتقوية مناعتك:<br><br>1. تناول نظام غذائي متوازن.<br>2. ممارسة التمارين الرياضية بانتظام.<br>3. الحصول على قسط كافٍ من النوم.<br>4. البقاء رطبًا.<br>5. إدارة الإجهاد.<br></p>",
                        'seo_title'         => "تعزيز جهازك المناعي",
                        'seo_description'   => "تعلم طرقًا فعالة لتعزيز جهازك المناعي وحماية نفسك من الأمراض.",
                    ],
                ],
            ],
            [
                'slug'             => "back-pain-relief",
                'blog_category_id' => 4,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-11.webp',
                'image'            => 'uploads/website-images/dummy/blog-11.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Effective Ways to Relieve Back Pain",
                        'short_description' => "Back pain can be debilitating. Here are some effective ways to find relief.",
                        'description'       => "<p>Back pain can greatly impact your quality of life. Here are some effective ways to relieve back pain:<br><br>1. Maintain good posture.<br>2. Stay active and exercise.<br>3. Use heat and cold therapy.<br>4. Try physical therapy.<br>5. Consider over-the-counter pain relievers.<br></p>",
                        'seo_title'         => "Back Pain Relief Tips",
                        'seo_description'   => "Discover effective ways to relieve back pain and improve your quality of life.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "طرق فعالة لتخفيف آلام الظهر",
                        'short_description' => "يمكن أن تكون آلام الظهر منهكة. إليك بعض الطرق الفعالة لتخفيف الألم.",
                        'description'       => "<p>يمكن أن تؤثر آلام الظهر بشكل كبير على جودة حياتك. إليك بعض الطرق الفعالة لتخفيف آلام الظهر:<br><br>1. الحفاظ على وضعية جيدة.<br>2. البقاء نشيطًا وممارسة الرياضة.<br>3. استخدام العلاج بالحرارة والبرودة.<br>4. تجربة العلاج الطبيعي.<br>5. النظر في مسكنات الألم التي تُصرف دون وصفة طبية.<br></p>",
                        'seo_title'         => "نصائح لتخفيف آلام الظهر",
                        'seo_description'   => "اكتشف طرقًا فعالة لتخفيف آلام الظهر وتحسين جودة حياتك.",
                    ],
                ],
            ],
            [
                'slug'             => "healthy-diet",
                'blog_category_id' => 4,
                'thumbnail_image'  => 'uploads/website-images/dummy/blog-thumbnail-12.webp',
                'image'            => 'uploads/website-images/dummy/blog-12.webp',
                'translations'     => [
                    [
                        'lang_code'         => "en",
                        'title'             => "Creating a Healthy Diet Plan",
                        'short_description' => "A balanced diet is key to maintaining overall health. Learn how to create a healthy diet plan.",
                        'description'       => "<p>A healthy diet is crucial for maintaining overall health and well-being. Here are some tips for creating a balanced diet plan:<br><br>1. Include a variety of fruits and vegetables.<br>2. Choose whole grains.<br>3. Incorporate lean proteins.<br>4. Limit added sugars and saturated fats.<br>5. Stay hydrated.<br></p>",
                        'seo_title'         => "Healthy Diet Plan Tips",
                        'seo_description'   => "Discover tips for creating a balanced diet plan to maintain overall health and well-being.",
                    ],
                    [
                        'lang_code'         => "ar",
                        'title'             => "إنشاء خطة غذائية صحية",
                        'short_description' => "النظام الغذائي المتوازن هو مفتاح الحفاظ على الصحة العامة. تعلم كيفية إنشاء خطة غذائية صحية.",
                        'description'       => "<p>النظام الغذائي الصحي أمر ضروري للحفاظ على الصحة العامة والرفاهية. إليك بعض النصائح لإنشاء خطة غذائية متوازنة:<br><br>1. تضمين مجموعة متنوعة من الفواكه والخضروات.<br>2. اختيار الحبوب الكاملة.<br>3. إدراج البروتينات الخالية من الدهون.<br>4. الحد من السكريات المضافة والدهون المشبعة.<br>5. الحفاظ على الترطيب.<br></p>",
                        'seo_title'         => "نصائح خطة غذائية صحية",
                        'seo_description'   => "اكتشف نصائح لإنشاء خطة غذائية متوازنة للحفاظ على الصحة العامة والرفاهية.",
                    ],
                ],
            ],

        ];

        foreach ($dummyBlogs as $value) {
            $blog = new Blog();
            $blog->admin_id = Admin::first()->id;
            $blog->blog_category_id = $value['blog_category_id'];
            $blog->slug = $value['slug'];
            $blog->image = $value['image'];
            $blog->thumbnail_image = $value['thumbnail_image'];
            $blog->show_homepage = $faker->boolean;
            $blog->is_feature = $value['slug'] == 'healthy-diet';
            $blog->status = true;

            $blog->save();

            foreach ($value['translations'] as $data) {
                $blogTranslation = new BlogTranslation();
                $blogTranslation->blog_id = $blog->id;
                $blogTranslation->lang_code = $data['lang_code'];
                $blogTranslation->title = $data['title'];
                $blogTranslation->sort_description = $data['short_description'];
                $blogTranslation->description = $data['description'];
                $blogTranslation->seo_title = $data['title'];
                $blogTranslation->seo_description = $data['short_description'];
                $blogTranslation->save();
            }

            for ($j = 0; $j < 3; $j++) {
                $comment = new BlogComment();
                $comment->blog_id = $blog->id;
                $comment->name = $faker->name;
                $comment->email = $faker->email;
                $comment->phone = $faker->phoneNumber;
                $comment->comment = $faker->paragraph;
                $comment->status = 1;
                $comment->save();
            }
        }
    }
}
