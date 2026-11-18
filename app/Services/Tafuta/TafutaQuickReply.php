<?php

namespace App\Services\Tafuta;

use App\Support\KenyaPlaces;
use Illuminate\Support\Str;

class TafutaQuickReply
{
    private const SPECIALTIES = [
        'heart doctor' => 'Cardiology', 'cardiologist' => 'Cardiology', 'cardiology' => 'Cardiology',
        'skin doctor' => 'Dermatology', 'dermatologist' => 'Dermatology', 'dermatology' => 'Dermatology',
        'child doctor' => 'Paediatrics', 'paediatrician' => 'Paediatrics', 'pediatrician' => 'Paediatrics',
        'eye doctor' => 'Ophthalmology', 'ophthalmologist' => 'Ophthalmology',
        'dentist' => 'Dental', 'ent specialist' => 'ENT',
    ];

    private const TYPES = [
        'doctor' => 'doctor', 'specialist' => 'doctor', 'clinic' => 'clinic', 'hospital' => 'clinic',
        'facility' => 'clinic', 'healthcare school' => 'school', 'medical school' => 'school',
        'nursing school' => 'school', 'medical supplier' => 'supplier', 'equipment supplier' => 'supplier',
    ];

    private const DEFINITIONS = [
        'cardiologist' => 'A cardiologist is a doctor who specialises in the heart and blood vessels.',
        'dermatologist' => 'A dermatologist is a doctor who specialises in skin, hair and nails.',
        'paediatrician' => 'A paediatrician is a doctor who cares for infants, children and adolescents.',
        'pediatrician' => 'A paediatrician is a doctor who cares for infants, children and adolescents.',
        'ophthalmologist' => 'An ophthalmologist is a medical doctor who specialises in eye care.',
        'ent specialist' => 'An ENT specialist is a doctor who specialises in the ear, nose and throat.',
    ];

    public function answer(string $message): ?array
    {
        $text = mb_strtolower(trim(Str::squish($message), " \t\n\r?!."));
        if (in_array($text, ['hi', 'hello', 'hey', 'habari', 'jambo'], true)) {
            return ['reply' => __('Hi! I can look up specialists, facilities, healthcare schools and medical suppliers. Try “find a heart doctor in Kisumu”.')];
        }
        if (in_array($text, ['thanks', 'thank you', 'asante'], true)) {
            return ['reply' => __('You’re welcome. What else can I help you find?')];
        }
        if (preg_match('/^(?:what is|what does) an? (.+?)(?: do)?$/u', $text, $match) && isset(self::DEFINITIONS[$match[1]])) {
            return ['reply' => __(self::DEFINITIONS[$match[1]])];
        }

        $tools = new TafutaTools();
        if (preg_match('/^how do i (?:join medfind|list my (?:practice|clinic|facility|school|business)(?: on medfind)?)$/u', $text)) {
            $help = $tools->run('platform_help', ['topic' => 'join']);
            return ['reply' => __($help['how'])."\n\n".implode("\n", $help['links'])];
        }
        if (preg_match('/^(?:where (?:is|can i find) my (?:payment )?receipt|how do i (?:find|get|download) my (?:payment )?receipt)$/u', $text)) {
            $help = $tools->run('platform_help', ['topic' => 'receipts']);
            return ['reply' => __($help['how'])."\n\n".$help['sign_in']];
        }
        if (in_array($text, ['how do i pay', 'how do i pay for my plan', 'how do i renew my plan'], true)) {
            $help = $tools->run('platform_help', ['topic' => 'payment']);
            return ['reply' => __($help['where'])."\n\n".implode("\n", $help['options'])."\n\n".$help['sign_in']];
        }
        if (in_array($text, ['how do i contact medfind', 'medfind support', 'contact medfind'], true)) {
            $help = $tools->run('platform_help', ['topic' => 'contact']);
            return ['reply' => __('You can reach the MedFind team here.')."\n\n".$help['contact_page']];
        }
        if (in_array($text, ['what specialties are available', 'list specialties', 'show me the specialties'], true)) {
            $list = $tools->run('list_specialties', []);
            return ['reply' => __('Browse specialties with listed providers.')."\n\n".implode("\n", array_map(
                fn (array $item) => $item['name'].': '.$item['url'],
                array_slice($list['specialties'], 0, 8),
            ))."\n\n".$list['all_specialists_url']];
        }

        if (preg_match('/^find a (clinic|hospital|facility) that accepts (sha|aar|jubilee)$/u', $text, $match)) {
            return $this->search($tools, ['type' => 'clinic', 'insurance' => strtoupper($match[2]), 'keywords' => $match[1] === 'hospital' ? 'hospital' : '']);
        }
        if (!preg_match('/^(?:find(?: me)? |show me |i (?:need|am looking for) |where can i find )?(?:an? )?(.+?)(?: (?:in|near) (.+))?$/u', $text, $match)) {
            return null;
        }
        $provider = $match[1];
        if (str_ends_with($provider, 's') && isset(self::TYPES[substr($provider, 0, -1)])) {
            $provider = substr($provider, 0, -1);
        }
        $specialty = self::SPECIALTIES[$provider] ?? null;
        $type = self::TYPES[$provider] ?? ($specialty ? 'doctor' : null);
        if (!$type) {
            return null;
        }
        $place = $match[2] ?? '';
        $knownPlace = KenyaPlaces::all()[Str::slug($place)] ?? null;
        if ($place === 'me') {
            return ['reply' => __('Which town or neighbourhood should I search? Try “find a specialist in Nairobi”.')];
        }
        if ($place !== '' && !$knownPlace) {
            return null;
        }

        $keywords = $provider === 'hospital' ? 'hospital' : ($provider === 'nursing school' ? 'nursing' : '');
        return $this->search($tools, ['type' => $type, 'specialty' => $specialty, 'place' => $knownPlace, 'keywords' => $keywords]);
    }

    private function search(TafutaTools $tools, array $filters): array
    {
        $result = $tools->run('search_providers', $filters);
        if (!$result['total_matches']) {
            return ['reply' => __('I couldn’t find a listed match. Try a nearby town or browse the directory.')."\n\n".$result['see_all_url']];
        }

        return [
            'reply' => trans_choice('I found :count listed match.|I found :count listed matches.', $result['total_matches'])
                ."\n\n".$result['see_all_url'],
            'cards' => array_values($tools->found),
        ];
    }
}
