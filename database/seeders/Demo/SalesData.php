<?php

namespace Database\Seeders\Demo;

/**
 * Reference data for the demo. Every company, person, address and number is
 * invented; email addresses use the reserved `.example` domain and postal
 * codes use `00000`.
 */
final class SalesData
{
    public const USERS = [
        'demo' => ['Demo Admin', 'admin'],
        'capybara' => ['Anonymous Capybara', 'admin'],
        'narwhal' => ['Anonymous Narwhal', 'sales'],
        'axolotl' => ['Anonymous Axolotl', 'sales'],
        'quokka' => ['Anonymous Quokka', 'sales'],
    ];

    public const UOMS = [
        'PCS' => 'Piece', 'M' => 'Metre', 'SET' => 'Set', 'UNIT' => 'Unit', 'BOX' => 'Box', 'LOT' => 'Lot', 'ROLL' => 'Roll',
    ];

    /**
     * part number => [description, UOM, list price in IDR, kind]. `kind`
     * picks the quantity range. Beltora and Conveyix are fictional brands.
     */
    public const PARTS = [
        'BT-300FT-PP-0305' => ['MODULAR BELT BELTORA S300 FLAT TOP PP WHITE W 305mm', 'M', 1650000, 'belt'],
        'BT-300FT-POM-0610' => ['MODULAR BELT BELTORA S300 FLAT TOP POM BLUE W 610mm', 'M', 3900000, 'belt'],
        'BT-520FT-PP-0610' => ['MODULAR BELT BELTORA S520 FLAT TOP PP GREY W 610mm', 'M', 3450000, 'belt'],
        'BT-800HD-POM-0914' => ['MODULAR BELT BELTORA S800 HEAVY DUTY FLAT TOP POM W 914mm', 'M', 7850000, 'belt'],
        'BT-300FG-PP-0610' => ['MODULAR BELT BELTORA S300 FLUSH GRID PP WHITE W 610mm', 'M', 3050000, 'belt'],
        'BT-520FG-PP-0610' => ['MODULAR BELT BELTORA S520 FLUSH GRID PP BLUE W 610mm', 'M', 3300000, 'belt'],
        'BT-2200R-POM-0406' => ['RADIUS BELT BELTORA S2200 FLUSH GRID POM W 406mm', 'M', 4650000, 'belt'],
        'BT-SP300-Z12-25SQ' => ['SPROCKET MOLDED S300 Z-12 BORE 25mm SQUARE PA', 'PCS', 285000, 'sprocket'],
        'BT-SP300-Z16-40SQ' => ['SPROCKET MOLDED S300 Z-16 BORE 40mm SQUARE PA', 'PCS', 365000, 'sprocket'],
        'BT-SP520S-Z12-40SQ' => ['SPLIT SPROCKET MOLDED S520 Z-12 BORE 40mm SQUARE PA', 'PCS', 520000, 'sprocket'],
        'BT-SP2200-Z16-40SQ' => ['SPROCKET MOLDED S2200 Z-16 BORE 40mm SQUARE PA', 'PCS', 410000, 'sprocket'],
        'BT-SP800SS-Z13-60RK' => ['SPROCKET MACHINED SS304 S800 Z-13 BORE 60mm KEYWAY', 'PCS', 2450000, 'sprocket'],
        'BT-TT820-K325' => ['TABLE TOP CHAIN TT820 POM K325 (82.5mm) SS PIN', 'M', 685000, 'chain'],
        'BT-TT820-K450' => ['TABLE TOP CHAIN TT820 POM K450 (114.3mm) SS PIN', 'M', 845000, 'chain'],
        'BT-TT880SS-K325' => ['TABLE TOP CHAIN TT880 STAINLESS STEEL K325 (82.5mm)', 'M', 1950000, 'chain'],
        'BT-TT879-K325' => ['SIDE FLEXING CHAIN TT879 POM K325 (82.5mm) R500', 'M', 1150000, 'chain'],
        'BT-WS25-G' => ['WEAR STRIP UHMW-PE GREEN 25mm L=3m', 'PCS', 265000, 'strip'],
        'BT-WS30-W' => ['WEAR STRIP UHMW-PE WHITE 30mm L=3m', 'PCS', 295000, 'strip'],
        'CX-WSC-20' => ['WEAR STRIP C-PROFILE UHMW-PE C20 L=3000mm', 'PCS', 245000, 'strip'],
        'CX-SG3012-3000-W' => ['SIDE GUIDE RAIL UHMW-PE 30x12mm WHITE L=3000mm', 'PCS', 385000, 'strip'],
        'CX-SGB-12' => ['SIDE GUIDE BRACKET ADJUSTABLE SS304 ROD 12mm', 'PCS', 145000, 'bracket'],
        'CX-LF100-80-M16' => ['LEVELLING FOOT LF-100 BASE D.80mm M16x100mm PA/ZINC', 'PCS', 98000, 'foot'],
        'CX-LF200-120-M24' => ['LEVELLING FOOT HEAVY DUTY LF-200 D.120mm M24x150mm SS304', 'PCS', 465000, 'foot'],
        'CX-RR50-400' => ['RETURN ROLLER PP D.50mm L=400mm SHAFT 12mm', 'PCS', 215000, 'roller'],
        'CX-RR60SS-500' => ['RETURN ROLLER SS304 D.60mm L=500mm SHAFT 15mm', 'PCS', 685000, 'roller'],
        'CX-FB4-25' => ['FLANGE BEARING 4-BOLT THERMOPLASTIC SHAFT 25mm SS INSERT', 'PCS', 385000, 'bearing'],
        'CX-FB4-30' => ['FLANGE BEARING 4-BOLT THERMOPLASTIC SHAFT 30mm SS INSERT', 'PCS', 445000, 'bearing'],
        'UCP-205' => ['PILLOW BLOCK BEARING UCP 205 CAST IRON SHAFT 25mm', 'PCS', 165000, 'bearing'],
        'SHAFT-SQ40-800' => ['SHAFT SS304 SQUARE 40mm L=800mm MACHINED BOTH ENDS', 'PCS', 1250000, 'shaft'],
        'PVC-GRN-3MM-600' => ['PVC BELT GREEN 3mm 2-PLY W 600mm', 'M', 425000, 'belt'],
        'PU-BLU-2MM-400' => ['PU BELT BLUE 2mm W 400mm', 'M', 465000, 'belt'],
        'GM-075-30' => ['GEARED MOTOR 0.75kW RATIO 1:30 3-PHASE 380V', 'UNIT', 8750000, 'motor'],
        'INV-075' => ['INVERTER 0.75kW 1-PHASE INPUT 220V', 'UNIT', 3250000, 'motor'],
        'SVC-SPLICE' => ['BELT SPLICING ON SITE (HOT SPLICE)', 'LOT', 1750000, 'service'],
        'SVC-TRANSPORT' => ['DELIVERY CHARGE', 'LOT', 850000, 'service'],
    ];

    /** Retired, soft-deleted, and still on an old completed quotation. */
    public const DISCONTINUED = ['BT-300FT-PP-0254', 'MODULAR BELT S300 FLAT TOP PP W 254mm (DISCONTINUED)', 'M', 1400000, 'belt'];

    /** Parts that end the history running low, for the inventory's low-stock filter. */
    public const LOW_STOCK = ['GM-075-30' => 2, 'INV-075' => 3, 'BT-SP800SS-Z13-60RK' => 5, 'CX-RR60SS-500' => 8];

    /** [candidates, chance]: each slot adds one of its parts, with that probability. */
    public const THEMES = [
        'flat_top_300' => [[['BT-300FT-PP-0305', 'BT-300FT-POM-0610'], 1.0], [['BT-SP300-Z12-25SQ', 'BT-SP300-Z16-40SQ'], 0.9], [['BT-WS25-G', 'BT-WS30-W'], 0.7], [['SVC-SPLICE', 'SVC-TRANSPORT'], 0.3]],
        'flat_top_520' => [[['BT-520FT-PP-0610'], 1.0], [['BT-SP520S-Z12-40SQ'], 0.9], [['SHAFT-SQ40-800'], 0.5], [['CX-FB4-25', 'CX-FB4-30'], 0.6], [['BT-WS30-W', 'CX-WSC-20'], 0.5]],
        'flush_grid' => [[['BT-300FG-PP-0610', 'BT-520FG-PP-0610'], 1.0], [['BT-SP300-Z12-25SQ', 'BT-SP520S-Z12-40SQ'], 0.9], [['CX-RR50-400'], 0.6], [['BT-WS25-G'], 0.5]],
        'heavy' => [[['BT-800HD-POM-0914'], 1.0], [['BT-SP800SS-Z13-60RK'], 1.0], [['SHAFT-SQ40-800'], 0.6], [['UCP-205'], 0.6], [['GM-075-30'], 0.4]],
        'radius' => [[['BT-2200R-POM-0406'], 1.0], [['BT-SP2200-Z16-40SQ'], 0.9], [['BT-WS25-G', 'BT-WS30-W'], 0.8], [['SVC-SPLICE'], 0.3]],
        'chain' => [[['BT-TT820-K325', 'BT-TT820-K450'], 1.0], [['BT-TT879-K325'], 0.4], [['CX-SG3012-3000-W'], 0.7], [['CX-SGB-12'], 0.7], [['CX-WSC-20'], 0.5]],
        'chain_ss' => [[['BT-TT880SS-K325'], 1.0], [['BT-TT879-K325'], 0.5], [['CX-WSC-20'], 0.6], [['SVC-TRANSPORT'], 0.3]],
        'frame' => [[['CX-LF100-80-M16', 'CX-LF200-120-M24'], 1.0], [['CX-RR50-400', 'CX-RR60SS-500'], 0.6], [['CX-FB4-25', 'UCP-205'], 0.6]],
        'pvc' => [[['PVC-GRN-3MM-600', 'PU-BLU-2MM-400'], 1.0], [['SVC-SPLICE'], 0.7], [['UCP-205'], 0.4]],
        'drive' => [[['GM-075-30'], 1.0], [['INV-075'], 0.6], [['SHAFT-SQ40-800'], 0.5], [['UCP-205', 'CX-FB4-25'], 0.6]],
        'spares' => [[['BT-SP300-Z12-25SQ', 'BT-SP520S-Z12-40SQ'], 1.0], [['BT-WS25-G', 'CX-WSC-20'], 0.6]],
    ];

    /** kind => [min, max, step] */
    public const QUANTITIES = [
        'belt' => [6, 42, 1], 'chain' => [18, 120, 6], 'sprocket' => [4, 24, 2], 'strip' => [6, 30, 2],
        'bracket' => [10, 40, 2], 'foot' => [8, 48, 4], 'roller' => [6, 30, 2], 'bearing' => [2, 12, 2],
        'shaft' => [1, 4, 1], 'motor' => [1, 2, 1], 'service' => [1, 1, 1],
    ];

    /** kind => opening stock range */
    public const STOCK = [
        'belt' => [300, 600], 'chain' => [600, 1200], 'sprocket' => [120, 260], 'strip' => [200, 400],
        'bracket' => [200, 400], 'foot' => [240, 480], 'roller' => [150, 300], 'bearing' => [80, 160],
        'shaft' => [30, 60], 'motor' => [8, 14], 'service' => [0, 0],
    ];

    /**
     * code => [name, address 1, address 2, delivery point, notes, channel, themes, weight, account manager].
     * `weight` is how often the customer asks for a quotation.
     */
    public const CUSTOMERS = [
        'SPL' => ['PT Sinar Pangan Lestari', 'Example Industrial Park, Lot A-01', 'Bekasi Regency 00000', 'Bekasi Regency', 'Snack manufacturer with four packing lines.', 'direct', ['flat_top_300', 'flush_grid', 'radius', 'frame'], 5, 'narwhal'],
        'TKM' => ['PT Tirta Kencana Mandiri', 'Sample Factory Road No. 02', 'Bogor Regency 00000', 'Bogor Regency', 'Bottled water: PET bottle and gallon lines.', 'direct', ['chain', 'frame', 'drive'], 5, 'axolotl'],
        'KPU' => ['PT Kemasindo Prima Utama', 'Example Industrial Park, Lot B-03', 'Bekasi Regency 00000', 'Bekasi Regency', 'Cartons and flexible packaging.', 'direct', ['flat_top_520', 'pvc', 'drive'], 3, 'narwhal'],
        'NRS' => ['PT Nusantara Roti Sejahtera', 'Demo Manufacturing Zone, Unit 04', 'East Jakarta 00000', 'East Jakarta', 'Industrial bakery: spiral cooler and proofer.', 'direct', ['radius', 'flush_grid', 'pvc'], 4, 'quokka'],
        'FCM' => ['PT Farmasi Cahaya Medika', 'Sample Tech Estate, Block C-05', 'Tangerang Regency 00000', 'Tangerang Regency', 'Pharmaceuticals: blister and cartoner lines.', 'direct', ['flat_top_300', 'chain'], 3, 'quokka'],
        'GKJ' => ['PT Gelas Kristal Jaya', 'Demo Logistics Avenue No. 06', 'Sidoarjo 00000', 'Sidoarjo', 'Glass bottles; hot conveyors after the lehr.', 'direct', ['chain_ss', 'heavy', 'drive'], 3, 'capybara'],
        'SSI' => ['PT Segar Susu Indonesia', 'Example Food Park, Lot D-07', 'Pasuruan 00000', 'Pasuruan', 'UHT milk and yoghurt, aseptic lines.', 'direct', ['chain', 'flush_grid'], 4, 'quokka'],
        'MSM' => ['PT Mega Snack Makmur', 'Demo Industrial Estate, Plot E-08', 'Karawang 00000', 'Karawang', 'Chips and extruded snacks: seasoning conveyors.', 'direct', ['flush_grid', 'radius', 'spares'], 3, 'narwhal'],
        'TBE' => ['PT Tunas Baja Engineering', 'Sample Engineering Row No. 09', 'Surabaya 00000', 'Surabaya', 'Conveyor fabricator and integrator (reseller).', 'direct', ['flat_top_300', 'flat_top_520', 'chain', 'radius', 'frame', 'drive'], 5, 'capybara'],
        'KCM' => ['CV Karya Conveyor Mandiri', 'Example Workshop Lane No. 10', 'Gresik 00000', 'Gresik', 'Local conveyor fabricator.', 'direct', ['flat_top_300', 'chain', 'pvc', 'frame', 'spares'], 3, 'capybara'],
        'RKN' => ['PT Rasa Kopi Nusantara', 'Demo Roastery Block, Unit 11', 'Semarang 00000', 'Semarang', 'Coffee roastery and sachet packing.', 'direct', ['flat_top_300', 'pvc', 'spares'], 2, 'quokka'],
        'MJT' => ['Workshop Maju Teknik', 'Sample Workshop Alley No. 12', 'Yogyakarta 00000', 'Yogyakarta', 'Buys through the marketplace store.', 'marketplace', ['spares'], 1, 'quokka'],
    ];

    /** Archived: it closed down. Keeps old quotations so the archive has history to show. */
    public const ARCHIVED = ['AMS', 'PT Aneka Minuman Segar', 'Example Warehouse Road No. 13', 'Bogor Regency 00000', 'Bogor Regency', 'Inactive: the plant has closed.', 'direct', ['chain'], 0, 'axolotl'];

    /**
     * Customer contacts are "Anonymous <Animal>", the way Google Docs names
     * viewers it can't identify. Staff and inquiry senders use other animals.
     */
    public const CONTACT_ANIMALS = [
        'Fox', 'Otter', 'Badger', 'Heron', 'Lynx', 'Moose', 'Panda', 'Koala', 'Raccoon', 'Walrus',
        'Hedgehog', 'Penguin', 'Dolphin', 'Kiwi', 'Squirrel', 'Beaver', 'Giraffe', 'Kangaroo', 'Llama', 'Turtle',
    ];

    public const QUOTATION_NOTES = [
        'Price includes delivery to site.',
        'Installation not included.',
        '6-month warranty against manufacturing defects.',
        'Indent item; arrival follows the shipping schedule.',
        'Revised after the site survey.',
        'Includes on-site belt splicing.',
        'Ready stock in our Jakarta warehouse.',
        'Replacement belt for the packing line.',
    ];

    public const DO_NOTES = [
        'Please confirm receipt with the warehouse.',
        'Sent by courier, packed in a wooden crate.',
        'Call the contact person before delivery.',
        'Unload at the rear warehouse, door 3.',
    ];

    public const PAYMENT_NOTES = ['Bank transfer', 'Bank transfer, ref. 4471', 'Bank transfer, ref. 9020', 'Giro, due on receipt'];

    public const CANCEL_REASONS = [
        'Customer chose a cheaper local supplier.',
        'Project postponed by the customer.',
        'Customer went with a different belt series.',
        'No response after three follow-ups.',
    ];
}
