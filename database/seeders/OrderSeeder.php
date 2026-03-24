<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    private array $suppliers = ['AAH', 'Alliance', 'BNS', 'Cavendish', 'DayLewis', 'Phoenix', 'Smartway', 'Target', 'TestSuppliers'];

    private array $categories = ['Brand', 'Branded Generics', 'CD2', 'CD3', 'CD4', 'CD5', 'Fridge', 'Generic', 'OTC', 'Surgical', 'Other'];

    private array $dtCategories = ['Generics', 'CAT-C Brands', 'CAT-H Brands', 'Part IX Appliances', 'ZD'];

    private array $stockStatuses = ['in_stock', 'out_of_stock', 'excess_stock'];

    private array $flags = ['red', 'green', 'black', 'blue', 'none'];

    private array $responses = [
        'IN STOCK',
        'IN STOCK - ALLOCATED',
        'AWAITING RESPONSE',
        'AWAITING STOCK',
        'NOT ORDERED - PRODUCT NOT FOUND',
        'NOT ORDERED - OUT OF STOCK',
        'ORDERED',
        'CONFIRMED',
        'OUT OF STOCK',
        'EXCESS STOCK',
        'PARTIALLY ORDERED',
    ];

    private array $products = [
        ['desc' => 'Amoxicillin 500mg Capsules',        'pip' => '0034211'],
        ['desc' => 'Atorvastatin 20mg Tablets',          'pip' => '0123456'],
        ['desc' => 'Amlodipine 10mg Tablets',            'pip' => '0234567'],
        ['desc' => 'Metformin 500mg Tablets',            'pip' => '0345678'],
        ['desc' => 'Omeprazole 20mg Capsules',           'pip' => '0456789'],
        ['desc' => 'Ramipril 5mg Capsules',              'pip' => '0567890'],
        ['desc' => 'Lisinopril 10mg Tablets',            'pip' => '0678901'],
        ['desc' => 'Salbutamol 100mcg Inhaler',          'pip' => '0789012'],
        ['desc' => 'Simvastatin 40mg Tablets',           'pip' => '0890123'],
        ['desc' => 'Lansoprazole 30mg Capsules',         'pip' => '0901234'],
        ['desc' => 'Bisoprolol 5mg Tablets',             'pip' => '1012345'],
        ['desc' => 'Bendroflumethiazide 2.5mg Tabs',    'pip' => '1123456'],
        ['desc' => 'Codeine Phosphate 30mg Tablets',    'pip' => '1234567'],
        ['desc' => 'Diazepam 5mg Tablets',               'pip' => '1345678'],
        ['desc' => 'Fluoxetine 20mg Capsules',           'pip' => '1456789'],
        ['desc' => 'Metoprolol 50mg Tablets',            'pip' => '1567890'],
        ['desc' => 'Naproxen 500mg Tablets',             'pip' => '1678901'],
        ['desc' => 'Prednisolone 5mg Tablets',           'pip' => '1789012'],
        ['desc' => 'Sertraline 100mg Tablets',           'pip' => '1890123'],
        ['desc' => 'Warfarin 5mg Tablets',               'pip' => '1901234'],
        ['desc' => 'Tramadol 50mg Capsules',             'pip' => '2012345'],
        ['desc' => 'Ibuprofen 400mg Tablets',            'pip' => '2123456'],
        ['desc' => 'Doxycycline 100mg Capsules',         'pip' => '2234567'],
        ['desc' => 'Levothyroxine 100mcg Tablets',      'pip' => '2345678'],
        ['desc' => 'Cetirizine 10mg Tablets',            'pip' => '2456789'],
        ['desc' => 'Clopidogrel 75mg Tablets',           'pip' => '2567890'],
        ['desc' => 'Furosemide 40mg Tablets',            'pip' => '2678901'],
        ['desc' => 'Gabapentin 300mg Capsules',          'pip' => '2789012'],
        ['desc' => 'Morphine 10mg/5ml Oral Solution',   'pip' => '2890123'],
        ['desc' => 'Paracetamol 500mg Tablets 32s',     'pip' => '2901234'],
        ['desc' => 'Insulin Glargine 100u/ml Inj',      'pip' => '3012345'],
        ['desc' => 'Rivaroxaban 20mg Tablets',           'pip' => '3123456'],
        ['desc' => 'Candesartan 16mg Tablets',           'pip' => '3234567'],
        ['desc' => 'Escitalopram 10mg Tablets',          'pip' => '3345678'],
        ['desc' => 'Quetiapine 100mg Tablets',           'pip' => '3456789'],
    ];

    private array $notes = [
        'Urgent order — patient waiting',
        'Regular monthly stock',
        'Price checked against tariff',
        'Alternative supplier requested',
        'CD order — signed requisition attached',
        'Fridge item — cold chain required',
        'Bulk purchase — discount applied',
        'Short-dated batch accepted',
        null,
        null,
        null,
    ];

    public function run(): void
    {
        $rows = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 150; $i++) {
            $product = $this->products[array_rand($this->products)];
            $supplier = $this->suppliers[array_rand($this->suppliers)];
            $category = $this->categories[array_rand($this->categories)];
            $dtCat = $this->dtCategories[array_rand($this->dtCategories)];
            $stock = $this->stockStatuses[array_rand($this->stockStatuses)];
            $flag = $this->flags[array_rand($this->flags)];
            $response = $this->responses[array_rand($this->responses)];
            $note = $this->notes[array_rand($this->notes)];

            // Spread dates across last 60 days — ensures today/yesterday/last7days all have hits
            $daysAgo = match (true) {
                $i <= 10 => 0,                        // today
                $i <= 20 => 1,                        // yesterday
                $i <= 40 => random_int(2, 3),         // last 3 days
                $i <= 70 => random_int(4, 7),         // last 7 days
                $i <= 110 => random_int(8, 30),       // this month range
                default => random_int(31, 60),      // last month
            };

            $orderDate = $now->copy()->subDays($daysAgo)->setTime(random_int(7, 18), random_int(0, 59));

            $dtPrice = round(random_int(50, 5000) / 100, 4);

            // Some orders above DT, some below — ensures DT filter works
            $aboveDT = ($i % 5 === 0);
            $price = $aboveDT
                ? round($dtPrice * (1 + random_int(5, 30) / 100), 4)
                : round($dtPrice * (1 - random_int(0, 15) / 100), 4);

            $maxPrice = round($dtPrice * 1.05, 4);
            $qty = random_int(1, 500);
            $approvedQty = (int) round($qty * (random_int(80, 100) / 100));

            $rows[] = [
                'order_number' => 'ORD-'.str_pad((string) (1000 + $i), 6, '0', STR_PAD_LEFT),
                'ordernumber' => 'ON-'.str_pad((string) (1000 + $i), 6, '0', STR_PAD_LEFT),
                'product_id' => random_int(100, 9999),
                'product_description' => $product['desc'],
                'pipcode' => $product['pip'],
                'supplier_id' => $supplier,
                'quantity' => $qty,
                'approved_qty' => $approvedQty,
                'price' => $price,
                'max_price' => $maxPrice,
                'dt_price' => $dtPrice,
                'rule_price' => round($dtPrice * 0.98, 4),
                'parent_id' => ($i % 10 === 0) ? random_int(1, $i) : null,
                'status' => 'processed',
                'sent_date' => $orderDate->toDateTimeString(),
                'is_opened' => (bool) random_int(0, 1),
                'is_transmitted' => (bool) random_int(0, 1),
                'transmit_method' => ['EDI', 'Email', 'Fax', 'Portal'][random_int(0, 3)],
                'transmit_date' => $orderDate->copy()->addMinutes(random_int(5, 60))->toDateTimeString(),
                'orderdate' => $orderDate->toDateTimeString(),
                'response' => $response,
                'category' => $category,
                'price_range' => $dtCat,
                'source' => ['Manual', 'Automatic', 'Imported'][random_int(0, 2)],
                'notes' => $note,
                'flag' => $flag,
                'stock_status' => $stock,
                'created_at' => $orderDate->toDateTimeString(),
                'updated_at' => $orderDate->toDateTimeString(),
            ];
        }

        DB::table('orders')->insert($rows);

        $this->command->info('Seeded 150 orders successfully.');
    }
}
