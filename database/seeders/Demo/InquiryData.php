<?php

namespace Database\Seeders\Demo;

/**
 * Web inquiries at every stage. `from` is a customer code (an existing
 * customer writing in) or [name, company, email, phone, city] for a new
 * prospect. Lines are [part number or free text, quantity, unit, note].
 */
final class InquiryData
{
    public static function all(): array
    {
        return [
            [
                'days' => 52, 'status' => 'quoted', 'from' => 'GKJ', 'link' => true, 'assign' => 'capybara', 'decide' => 'capybara', 'source' => 'web',
                'message' => 'Please quote stainless chain for the conveyor coming out of the lehr, about 45 metres in total. Product is still hot in this area.',
                'lines' => [['BT-TT880SS-K325', 45, 'M', 'Line 2, lehr exit']],
            ],
            [
                'days' => 44, 'status' => 'approved', 'from' => 'TKM', 'link' => true, 'assign' => 'axolotl', 'decide' => 'capybara', 'source' => 'web',
                'message' => 'For the new PET line project. Please send a complete quotation including side-flex chain and wear strips.',
                'lines' => [['BT-TT820-K450', 90, 'M', null], ['BT-TT879-K325', 12, 'M', 'For two curves'], ['CX-WSC-20', 30, 'PCS', null]],
            ],
            [
                'days' => 38, 'status' => 'rejected', 'from' => ['Anonymous Ibex', 'Toko Berkah Jaya Teknik', 'ibex@berkahjaya.example', '+62 813 0000 4418', 'Surabaya'],
                'decide' => 'capybara', 'source' => 'web',
                'reason' => 'Asked for a full price list to resell, not a project requirement.',
                'message' => 'Please send your complete price list for all sprockets and belts at reseller prices.',
                'lines' => [['BT-SP300-Z12-25SQ', 1, 'PCS', 'Reseller price']],
            ],
            [
                'days' => 29, 'status' => 'approved', 'from' => ['Anonymous Pangolin', 'PT Aroma Rempah Sejati', 'pangolin@aromarempah.example', '+62 822 0000 7765', 'Surabaya'],
                'assign' => 'narwhal', 'decide' => 'capybara', 'source' => 'web',
                'message' => 'We are building a new powdered seasoning packing line. Please quote the belt and conveyor feet.',
                'lines' => [['BT-300FT-PP-0305', 24, 'M', null], ['BT-SP300-Z12-25SQ', 12, 'PCS', null], ['CX-LF100-80-M16', 32, 'PCS', null]],
            ],
            [
                'days' => 23, 'status' => 'approved', 'from' => 'MSM', 'link' => true, 'assign' => 'narwhal', 'decide' => 'demo', 'source' => 'email',
                'message' => 'Many modules on the line 3 seasoning belt have cracked. Please quote, and include a site survey to re-measure.',
                'lines' => [['BT-300FG-PP-0610', 30, 'M', 'Seasoning line 3'], ['Site survey and measurement', 1, 'LOT', null]],
            ],
            [
                'days' => 18, 'status' => 'rejected', 'from' => ['Anonymous Lemur', 'Digital Growth Agency', 'lemur@digitalgrowth.example', '+62 857 0000 2046', 'South Jakarta'],
                'decide' => 'demo', 'source' => 'web',
                'reason' => 'Not a quote request (selling marketing services).',
                'message' => 'Hi, we offer SEO and ads to grow your website sales. Could you share the contact for your marketing team?',
                'lines' => [['Website SEO package', 1, 'LOT', null]],
            ],
            [
                'days' => 14, 'status' => 'in_review', 'from' => 'SSI', 'link' => true, 'assign' => 'quokka', 'source' => 'web',
                'message' => 'Replacement chain and guide rails for the yoghurt cup line. Please confirm stock availability.',
                'lines' => [['BT-TT820-K325', 60, 'M', null], ['CX-SG3012-3000-W', 20, 'PCS', null]],
            ],
            [
                'days' => 11, 'status' => 'in_review', 'from' => ['Anonymous Wombat', 'Pacific Rim Foods Sdn Bhd', 'wombat@pacificrim.example', '+60 12 000 3021', 'Johor Bahru, Malaysia'],
                'assign' => 'capybara', 'source' => 'web',
                'message' => 'We are expanding our frozen dumpling line and need a quote for flush grid belting and sprockets, delivered to Pasir Gudang. Please include lead time.',
                'lines' => [['BT-520FG-PP-0610', 24, 'M', 'IQF tunnel infeed'], ['BT-SP520S-Z12-40SQ', 16, 'PCS', null]],
            ],
            [
                'days' => 6, 'status' => 'in_review', 'from' => ['Anonymous Okapi', 'PT Baja Ringan Prima', 'okapi@bajaringan.example', '+62 819 0000 5587', 'Cilegon'],
                'assign' => 'capybara', 'source' => 'email',
                'message' => 'Conveyor for light steel profiles, 12 m long. Please recommend a motor as well.',
                'lines' => [['BT-800HD-POM-0914', 12, 'M', null], ['Geared motor 1.5 kW, ratio 1:40', 2, 'UNIT', '380V supply']],
            ],
            [
                'days' => 5, 'status' => 'new', 'from' => ['Anonymous Tapir', 'PT Hijau Daun Agro', 'tapir@hijaudaun.example', '+62 821 0000 9923', 'Bandung'],
                'source' => 'web',
                'message' => 'Please quote a replacement belt for our tea dryer: 610 mm wide, about 18 m in total.',
                'lines' => [['BT-300FG-PP-0610', 18, 'M', 'Tea dryer'], ['BT-SP300-Z12-25SQ', 12, 'PCS', null]],
            ],
            [
                'days' => 3, 'status' => 'new', 'from' => ['Anonymous Platypus', 'PT Kue Lapis Nusantara', 'platypus@kuelapis.example', '+62 856 0000 1174', 'West Jakarta'],
                'source' => 'email',
                'message' => 'Looking for a replacement radius belt for our cooling conveyor. Current belt is 406 mm wide. Please also quote sprockets.',
                'lines' => [['BT-2200R-POM-0406', 36, 'M', null], ['BT-SP2200-Z16-40SQ', 16, 'PCS', null]],
            ],
            [
                'days' => 1, 'status' => 'new', 'from' => ['Anonymous Chinchilla', 'CV Sumber Plastik Medan', 'chinchilla@sumberplastik.example', '+62 812 0000 2238', 'Medan'],
                'source' => 'web',
                'message' => 'Rollers and bearings for our warehouse conveyor. Please advise price and stock.',
                'lines' => [['CX-RR50-400', 40, 'PCS', null], ['CX-FB4-25', 16, 'PCS', null]],
            ],
            [
                'days' => 0, 'status' => 'new', 'from' => ['Anonymous Meerkat', 'PT Sejahtera Mie Instan', 'meerkat@sejahteramie.example', '+62 877 0000 3316', 'Serang'],
                'source' => 'web',
                'message' => 'For a new instant noodle packing line, targeting installation next month.',
                'lines' => [['BT-300FT-POM-0610', 40, 'M', null], ['BT-SP300-Z16-40SQ', 18, 'PCS', null], ['BT-WS30-W', 24, 'PCS', null]],
            ],
        ];
    }
}
