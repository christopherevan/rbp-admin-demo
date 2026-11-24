<?php

namespace Database\Seeders\Demo;

/**
 * Products with AI-written descriptions in the review queue. `review` is
 * where the draft stands; three pending drafts are deliberately flawed so
 * the guard has something to catch: an FDA claim with no evidence, a chain
 * described as a belt, and an invented load rating.
 */
final class CatalogData
{
    public static function all(): array
    {
        return [
            [
                'supplier' => 'Beltora', 'category' => 'Modular Belts › Flat Top', 'family' => 'belt',
                'title' => 'Series 300 Flat Top Belt', 'review' => 'approved',
                'facts' => ['Pitch (mm)' => ['25.4'], 'Width (mm)' => ['152', '305', '457', '610'], 'Material' => ['Polypropylene (PP)', 'Acetal (POM)'], 'Color' => ['White', 'Blue'], 'Belt strength (N/m)' => ['18000']],
                'manufacturer' => 'Closed-surface modular belt for conveying cartons, trays and packaged products. The flat top gives a stable, continuous surface for small or unstable items, and the modules are linked with hinge rods so a worn section can be replaced without splicing the belt.',
                'en' => "Series 300 Flat Top Belt is a modular belt from Beltora with a 25.4 mm pitch. Its closed top gives cartons, trays and small packs an even surface to ride on, with no gaps for narrow items to tip into.\n\nThe range covers widths of 152, 305, 457 and 610 mm in polypropylene or acetal. Because the belt is built from interlocking modules held together by hinge rods, a damaged row can be cut out and replaced on site instead of fitting a whole new belt, which keeps a stoppage short. We can quote the belt together with matching sprockets and wear strips, cut to the width and length of your conveyor frame.",
                'id' => "Series 300 Flat Top Belt adalah modular belt dari Beltora dengan pitch 25.4 mm. Permukaannya yang tertutup memberi karton, tray, dan kemasan kecil bidang yang rata tanpa celah, sehingga produk yang sempit tidak mudah terguling.\n\nTersedia lebar 152, 305, 457, dan 610 mm dengan material polypropylene atau acetal. Belt tersusun dari modul yang saling mengunci dengan hinge rod, sehingga baris yang rusak dapat diganti langsung di lokasi tanpa mengganti seluruh belt. Kami dapat menyiapkan penawaran belt beserta sprocket dan wear strip yang sesuai dengan rangka conveyor Anda.",
                'meta' => 'Series 300 Flat Top Belt by Beltora: 25.4 mm pitch modular belt in widths of 152 to 610 mm. Request a quotation.',
            ],
            [
                'supplier' => 'Beltora', 'category' => 'Modular Belts › Flush Grid', 'family' => 'belt',
                'title' => 'Series 300 Flush Grid Belt', 'review' => 'approved',
                'facts' => ['Pitch (mm)' => ['25.4'], 'Open area (%)' => ['23'], 'Width (mm)' => ['152', '305', '610'], 'Material' => ['Polypropylene (PP)'], 'Color' => ['White']],
                'manufacturer' => 'Open grid belt with 23% open area for draining, cooling and airflow through the belt. Flush edges and a smooth top allow side transfers. Easy to clean.',
                'en' => "Series 300 Flush Grid Belt is a modular belt from Beltora with a 25.4 mm pitch and a 23% open area. The grid lets water run off and air pass straight through, which suits rinsing, draining and cooling stages, and the flush edges let product transfer in from the side.\n\nIt comes in widths of 152, 305 and 610 mm in white polypropylene. Rows of modules are joined by hinge rods, so a worn section can be replaced on the line without splicing, and the open structure is quick to wash down between runs. We supply it with matching sprockets and return rollers for a complete belt change.",
                'id' => "Series 300 Flush Grid Belt adalah modular belt dari Beltora dengan pitch 25.4 mm dan open area 23%. Grid membuat air mudah terbuang dan udara mengalir langsung, cocok untuk tahap pembilasan, penirisan, dan pendinginan, sementara tepi yang rata memungkinkan transfer dari samping.\n\nTersedia lebar 152, 305, dan 610 mm dengan polypropylene putih. Baris modul disatukan dengan hinge rod sehingga bagian yang aus dapat diganti di lini tanpa penyambungan. Kami menyediakannya bersama sprocket dan return roller untuk penggantian belt yang lengkap.",
                'meta' => 'Series 300 Flush Grid Belt by Beltora: 23% open area for draining and cooling, widths 152 to 610 mm.',
            ],
            [
                'supplier' => 'Beltora', 'category' => 'Modular Belts › Radius', 'family' => 'belt',
                'title' => 'Series 2200 Radius Flush Grid Belt', 'review' => 'rejected',
                'note' => 'Generic brochure copy: says nothing about the radius, the open area or why the belt removes transfer points.',
                'facts' => ['Pitch (mm)' => ['25.4'], 'Open area (%)' => ['42'], 'Width (mm)' => ['254', '406', '508'], 'Inside radius (mm)' => ['559', '893', '1118'], 'Material' => ['Acetal (POM)'], 'Collapse factor' => ['2.2']],
                'manufacturer' => 'Side-flexing radius belt that runs straight and curved sections with a single belt, removing transfer points. Collapse factor 2.2 times belt width.',
                'en' => "Series 2200 Radius Flush Grid Belt is a modular belt from Beltora that is great for many applications. It is a high-quality product that offers excellent performance and reliability across a wide range of industries and working conditions.\n\nAvailable in widths of 254, 406 and 508 mm, it is the ideal choice for customers who want the very best for their production lines. Contact us today to find out more about this outstanding belt and how it can benefit your business.",
                'id' => "Series 2200 Radius Flush Grid Belt adalah modular belt dari Beltora yang sangat baik untuk berbagai aplikasi. Produk berkualitas tinggi dengan performa dan keandalan yang luar biasa di berbagai industri.\n\nTersedia lebar 254, 406, dan 508 mm, pilihan ideal bagi pelanggan yang menginginkan yang terbaik. Hubungi kami hari ini untuk informasi lebih lanjut.",
                'meta' => 'Series 2200 Radius Flush Grid Belt by Beltora. Request a quotation.',
            ],
            [
                'supplier' => 'Beltora', 'category' => 'Modular Belts › Flat Top', 'family' => 'belt',
                'title' => 'Series 800 Heavy-Duty Flat Top Belt', 'review' => 'pending',
                'facts' => ['Pitch (mm)' => ['50.8'], 'Width (mm)' => ['610', '914', '1219'], 'Material' => ['Acetal (POM)'], 'Color' => ['Blue'], 'Belt strength (N/m)' => ['42000']],
                'manufacturer' => 'Reinforced flat top module in acetal for heavy unit loads such as drums, pallets of cans and metal parts. High belt strength for long conveyors with accumulation.',
                'en' => "Series 800 Heavy-Duty Flat Top Belt is a modular belt from Beltora with a 50.8 mm pitch, moulded in blue acetal. It is built to carry drums, stacked cans and machined metal parts on a closed surface that stays flat under weight, and its high belt strength copes with long runs where product backs up against a stop.\n\nWidths of 610, 914 and 1219 mm are available. The modules lock together with hinge rods, so a damaged row can be swapped on site without replacing the belt. We can quote it with stainless sprockets and shafts sized for the load.",
                'id' => "Series 800 Heavy-Duty Flat Top Belt adalah modular belt dari Beltora dengan pitch 50.8 mm, berbahan acetal biru. Dirancang untuk membawa drum, tumpukan kaleng, dan komponen logam di atas permukaan tertutup yang tetap rata saat dibebani.\n\nTersedia lebar 610, 914, dan 1219 mm. Modul dikunci dengan hinge rod sehingga baris yang rusak dapat diganti di lokasi. Kami dapat menyiapkan penawaran bersama sprocket stainless dan poros yang sesuai dengan beban.",
                'meta' => 'Series 800 Heavy-Duty Flat Top Belt by Beltora: 50.8 mm pitch, widths 610 to 1219 mm, for heavy unit loads.',
            ],
            [
                'supplier' => 'Beltora', 'category' => 'Modular Belts › Flush Grid', 'family' => 'belt',
                'title' => 'Series 520 Flush Grid Belt', 'review' => 'pending',
                'facts' => ['Pitch (mm)' => ['50.8'], 'Open area (%)' => ['38'], 'Width (mm)' => ['305', '610', '914'], 'Material' => ['Polypropylene (PP)', 'Polyethylene (PE)'], 'Color' => ['Blue', 'Natural']],
                'manufacturer' => 'Flush grid belt with 38% open area for washing, blanching and freezing lines. Large openings let water and air through while the grid supports the product evenly.',
                'en' => "Series 520 Flush Grid Belt is a modular belt from Beltora with a 50.8 mm pitch and a 38% open area. Its wide openings pass water and cold air straight through on washing, blanching and freezing lines, while the grid keeps the product evenly supported. The belt is FDA-compliant for direct food contact.\n\nIt is made in widths of 305, 610 and 914 mm in polypropylene or polyethylene. Modules are joined with hinge rods, so a worn row can be replaced during a short stop. We can supply it with split sprockets that fit without removing the shaft.",
                'id' => "Series 520 Flush Grid Belt adalah modular belt dari Beltora dengan pitch 50.8 mm dan open area 38%. Bukaannya yang lebar meloloskan air dan udara dingin pada lini pencucian, blanching, dan pembekuan, sementara grid menopang produk secara merata.\n\nTersedia lebar 305, 610, dan 914 mm dengan polypropylene atau polyethylene. Modul disatukan dengan hinge rod sehingga baris yang aus dapat diganti saat lini berhenti sebentar.",
                'meta' => 'Series 520 Flush Grid Belt by Beltora: 38% open area for washing and freezing, widths 305 to 914 mm.',
            ],
            [
                'supplier' => 'Beltora', 'category' => 'Chains › Table Top', 'family' => 'chain',
                'title' => 'TT820 Table Top Chain', 'review' => 'pending',
                'facts' => ['Pitch (mm)' => ['38.1'], 'Chain width (mm)' => ['82.5', '114.3'], 'Material' => ['Acetal (POM)'], 'Pin' => ['Stainless steel']],
                'manufacturer' => 'Straight-running acetal table top chain with stainless steel pins for bottles, cans and containers on filling and packing lines.',
                'en' => "TT820 Table Top Chain is a stainless modular belt from Beltora with a 38.1 mm pitch, made in acetal with stainless steel hinge pins. It carries bottles, cans and other containers through filling and packing, where a smooth top and low friction let product accumulate without tipping.\n\nChain widths of 82.5 and 114.3 mm are available, and the chain is joined link by link, so a length can be shortened, extended or repaired on the line without special tools. We supply it by the metre with matching sprockets, wear strips and corner tracks.",
                'id' => "TT820 Table Top Chain adalah table top chain dari Beltora dengan pitch 38.1 mm, berbahan acetal dengan pin stainless steel. Rantai ini membawa botol, kaleng, dan kemasan lain melalui proses pengisian dan pengemasan.\n\nTersedia lebar 82.5 dan 114.3 mm, disambung per mata rantai sehingga panjangnya dapat disesuaikan di lini tanpa alat khusus.",
                'meta' => 'TT820 Table Top Chain by Beltora: 38.1 mm pitch acetal chain, widths 82.5 and 114.3 mm.',
            ],
            [
                'supplier' => 'Conveyix', 'category' => 'Frame Components › Levelling Feet', 'family' => 'foot',
                'title' => 'LF-200 Heavy-Duty Levelling Foot', 'review' => 'pending',
                'facts' => ['Base diameter (mm)' => ['120'], 'Thread' => ['M24'], 'Stem length (mm)' => ['150'], 'Load capacity (kg)' => ['2500'], 'Material' => ['Stainless steel AISI 304']],
                'manufacturer' => 'Heavy-duty levelling foot with a stainless steel base and M24 threaded stem for machine frames and conveyor stands carrying high loads.',
                'en' => "LF-200 Heavy-Duty Levelling Foot is a levelling foot from Conveyix with a 120 mm base and an M24 threaded stem 150 mm long. Load capacity is up to 6000 kg per foot, in stainless steel AISI 304.\n\nTurning the stem raises or lowers each corner of a frame on its own, so a conveyor or machine can be set level and at the right working height once it is in place. We can supply feet in sets for a complete frame, matched to the thread in the leg tubes and to the total weight the stand carries.",
                'id' => "LF-200 Heavy-Duty Levelling Foot adalah kaki penyetel dari Conveyix dengan dasar 120 mm dan batang ulir M24 sepanjang 150 mm, berbahan stainless steel AISI 304.\n\nDengan memutar batang ulir, setiap sudut rangka dapat diatur secara terpisah sehingga conveyor atau mesin dapat diatur rata pada ketinggian kerja yang tepat.",
                'meta' => 'LF-200 Heavy-Duty Levelling Foot by Conveyix: 120 mm stainless base, M24 thread, 150 mm stem.',
            ],
            [
                'supplier' => 'Conveyix', 'category' => 'Rollers & Bearings › Flange Bearings', 'family' => 'bearing',
                'title' => 'FB4 Thermoplastic Flange Bearing', 'review' => 'pending',
                'facts' => ['Shaft diameter (mm)' => ['25', '30'], 'Housing' => ['Glass-filled polyamide'], 'Insert' => ['Stainless steel'], 'Bolt holes' => ['4']],
                'manufacturer' => 'Four-bolt flange bearing unit with a corrosion-free thermoplastic housing and stainless steel insert. Maintenance-free, for wet and washdown areas.',
                'en' => "FB4 Thermoplastic Flange Bearing is a bearing unit from Conveyix for 25 or 30 mm shafts, with a glass-filled polyamide housing and a stainless steel insert. Because the housing does not rust and the unit runs without lubrication, it can stay on a wet conveyor frame without the regular greasing that cast-iron units need.\n\nIt bolts to the frame through 4 holes in the housing and takes a standard insert, so a worn insert can be replaced without changing the housing. We can supply bearing units together with shafts and sprockets for a complete drive or idler end, ready for 2 shifts a day of washdown.",
                'id' => "FB4 Thermoplastic Flange Bearing adalah bearing unit dari Conveyix untuk poros 25 atau 30 mm, dengan housing polyamide berisi serat kaca dan insert stainless steel. Housing tidak berkarat dan unit bekerja tanpa pelumasan, cocok untuk area basah.\n\nUnit dibaut ke rangka melalui 4 lubang dan memakai insert standar sehingga insert yang aus dapat diganti tanpa mengganti housing.",
                'meta' => 'FB4 Thermoplastic Flange Bearing by Conveyix: rust-free housing for 25 and 30 mm shafts, no lubrication needed.',
            ],
        ];
    }

    /**
     * Supplier category trees mirrored into `catalog_categories`.
     *
     * @return list<array{supplier: string, full_path: string}>
     */
    public static function categories(): array
    {
        return [
            ['supplier' => 'Beltora', 'full_path' => 'Modular Belts › Flat Top'],
            ['supplier' => 'Beltora', 'full_path' => 'Modular Belts › Flush Grid'],
            ['supplier' => 'Beltora', 'full_path' => 'Modular Belts › Radius'],
            ['supplier' => 'Beltora', 'full_path' => 'Modular Belts › Sprockets'],
            ['supplier' => 'Beltora', 'full_path' => 'Chains › Table Top'],
            ['supplier' => 'Conveyix', 'full_path' => 'Frame Components › Levelling Feet'],
            ['supplier' => 'Conveyix', 'full_path' => 'Frame Components › Guide Rails'],
            ['supplier' => 'Conveyix', 'full_path' => 'Rollers & Bearings › Flange Bearings'],
        ];
    }

    /**
     * Supplier-specific specification fields mirrored into `catalog_attribute_groups`.
     *
     * @return list<array{supplier: string, label: string, unit: ?string, position: int}>
     */
    public static function attributeGroups(): array
    {
        return [
            ['supplier' => 'Beltora', 'label' => 'Pitch', 'unit' => 'mm', 'position' => 1],
            ['supplier' => 'Beltora', 'label' => 'Width', 'unit' => 'mm', 'position' => 2],
            ['supplier' => 'Beltora', 'label' => 'Chain width', 'unit' => 'mm', 'position' => 3],
            ['supplier' => 'Beltora', 'label' => 'Open area', 'unit' => '%', 'position' => 4],
            ['supplier' => 'Beltora', 'label' => 'Inside radius', 'unit' => 'mm', 'position' => 5],
            ['supplier' => 'Beltora', 'label' => 'Material', 'unit' => null, 'position' => 6],
            ['supplier' => 'Beltora', 'label' => 'Color', 'unit' => null, 'position' => 7],
            ['supplier' => 'Beltora', 'label' => 'Belt strength', 'unit' => 'N/m', 'position' => 8],
            ['supplier' => 'Beltora', 'label' => 'Pin', 'unit' => null, 'position' => 9],
            ['supplier' => 'Beltora', 'label' => 'Collapse factor', 'unit' => null, 'position' => 10],
            ['supplier' => 'Conveyix', 'label' => 'Base diameter', 'unit' => 'mm', 'position' => 1],
            ['supplier' => 'Conveyix', 'label' => 'Thread', 'unit' => null, 'position' => 2],
            ['supplier' => 'Conveyix', 'label' => 'Stem length', 'unit' => 'mm', 'position' => 3],
            ['supplier' => 'Conveyix', 'label' => 'Load capacity', 'unit' => 'kg', 'position' => 4],
            ['supplier' => 'Conveyix', 'label' => 'Shaft diameter', 'unit' => 'mm', 'position' => 5],
            ['supplier' => 'Conveyix', 'label' => 'Housing', 'unit' => null, 'position' => 6],
            ['supplier' => 'Conveyix', 'label' => 'Insert', 'unit' => null, 'position' => 7],
            ['supplier' => 'Conveyix', 'label' => 'Bolt holes', 'unit' => null, 'position' => 8],
        ];
    }

    /**
     * Initial product types, their assigned supplier categories, and key specs.
     * `Conveyix / Rollers & Bearings › Flange Bearings` is left unmapped so one
     * product starts untyped.
     *
     * @return list<array{
     *     name: string,
     *     slug: string,
     *     header_image: ?string,
     *     sort_order: int,
     *     categories: list<array{0: string, 1: string}>,
     *     key_attributes: list<array{label: string, unit: ?string, groups: list<array{0: string, 1: string}>}>,
     * }>
     */
    public static function types(): array
    {
        return [
            [
                'name' => 'Modular Belts',
                'slug' => 'modular-belts',
                'header_image' => 'belts-modular.avif',
                'sort_order' => 1,
                'categories' => [
                    ['Beltora', 'Modular Belts › Flat Top'],
                    ['Beltora', 'Modular Belts › Flush Grid'],
                    ['Beltora', 'Modular Belts › Radius'],
                ],
                'key_attributes' => [
                    ['label' => 'Pitch', 'unit' => 'mm', 'groups' => [['Beltora', 'Pitch']]],
                    ['label' => 'Width', 'unit' => 'mm', 'groups' => [['Beltora', 'Width']]],
                    ['label' => 'Open area', 'unit' => '%', 'groups' => [['Beltora', 'Open area']]],
                    ['label' => 'Material', 'unit' => null, 'groups' => [['Beltora', 'Material']]],
                ],
            ],
            [
                'name' => 'Table Top Chains',
                'slug' => 'table-top-chains',
                'header_image' => 'chains-tabletop.avif',
                'sort_order' => 2,
                'categories' => [
                    ['Beltora', 'Chains › Table Top'],
                ],
                'key_attributes' => [
                    ['label' => 'Pitch', 'unit' => 'mm', 'groups' => [['Beltora', 'Pitch']]],
                    ['label' => 'Width', 'unit' => 'mm', 'groups' => [['Beltora', 'Chain width']]],
                    ['label' => 'Material', 'unit' => null, 'groups' => [['Beltora', 'Material']]],
                ],
            ],
            [
                'name' => 'Levelling Feet & Frames',
                'slug' => 'levelling-feet-frames',
                'header_image' => 'frame-components.avif',
                'sort_order' => 3,
                'categories' => [
                    ['Conveyix', 'Frame Components › Levelling Feet'],
                ],
                'key_attributes' => [
                    ['label' => 'Base diameter', 'unit' => 'mm', 'groups' => [['Conveyix', 'Base diameter']]],
                    ['label' => 'Thread', 'unit' => null, 'groups' => [['Conveyix', 'Thread']]],
                    ['label' => 'Load capacity', 'unit' => 'kg', 'groups' => [['Conveyix', 'Load capacity']]],
                ],
            ],
        ];
    }
}
