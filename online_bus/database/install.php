<?php
/**
 * Shreenathji Travels - Database Setup & Auto-Seeder
 * Run via CLI: php database/install.php
 * Or access via browser: http://localhost/online_bus/database/install.php
 */

define('IN_APP', true);

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'shreenathji_travels';

header('Content-Type: text/plain; charset=utf-8');
echo "=== Shreenathji Travels Database Installer ===\n\n";

try {
    // 1. Connect without db to ensure database exists
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[✓] Database `{$dbname}` created/verified.\n";

    // 2. Connect to the database
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 3. Execute database.sql
    echo "[...] Executing database.sql...\n";
    $sqlSchema = file_get_contents(__DIR__ . '/database.sql');
    $pdo->exec($sqlSchema);
    echo "[✓] Core database schema imported successfully.\n";

    // 4. Seed Cities
    $cityCount = $pdo->query("SELECT COUNT(*) FROM `indian_cities`")->fetchColumn();
    if ($cityCount == 0) {
        echo "[...] Seeding Indian cities...\n";
        $sqlCities = file_get_contents(__DIR__ . '/seed_cities.sql');
        $pdo->exec($sqlCities);
        $newCount = $pdo->query("SELECT COUNT(*) FROM `indian_cities`")->fetchColumn();
        echo "[✓] Seeded {$newCount} Indian cities.\n";
    } else {
        echo "[i] Cities already seeded ({$cityCount} cities found).\n";
    }

    // 5. Seed Base Sample Data (Admins, Users, Settings, FAQs, Buses)
    echo "[...] Seeding sample admins, settings, and buses...\n";
    $sqlSample = file_get_contents(__DIR__ . '/sample_data.sql');
    $pdo->exec($sqlSample);
    echo "[✓] Sample data imported.\n";

    // 6. Generate Seats for Buses
    echo "[...] Generating seat layouts for buses...\n";
    $buses = $pdo->query("SELECT id, bus_name, seating_layout FROM buses")->fetchAll();

    foreach ($buses as $bus) {
        $busId = $bus['id'];
        $layout = $bus['seating_layout'];

        // Check if seats already exist
        $existingSeats = $pdo->query("SELECT COUNT(*) FROM bus_seats WHERE bus_id = {$busId}")->fetchColumn();
        if ($existingSeats > 0) {
            continue;
        }

        $stmt = $pdo->prepare("INSERT INTO bus_seats (bus_id, seat_number, deck, seat_type, row_num, col_num, is_ladies_preferred, price_extra) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        if ($layout === 'sleeper_2x1') {
            // Lower Deck (15 berths: 5 rows x 3 columns: Col 1 Single, Col 2 & 3 Double)
            $seatIdx = 1;
            for ($r = 1; $r <= 5; $r++) {
                // Col 1: Single window
                $sNum = 'L' . $seatIdx++;
                $stmt->execute([$busId, $sNum, 'lower', 'sleeper', $r, 1, 0, 50.00]);

                // Col 2: Double inner
                $sNum = 'L' . $seatIdx++;
                $isLadies = ($r <= 2) ? 1 : 0;
                $stmt->execute([$busId, $sNum, 'lower', 'sleeper', $r, 2, $isLadies, 0.00]);

                // Col 3: Double window
                $sNum = 'L' . $seatIdx++;
                $stmt->execute([$busId, $sNum, 'lower', 'sleeper', $r, 3, $isLadies, 0.00]);
            }

            // Upper Deck (15 berths: 5 rows x 3 columns)
            $seatIdx = 1;
            for ($r = 1; $r <= 5; $r++) {
                // Col 1: Single window
                $sNum = 'U' . $seatIdx++;
                $stmt->execute([$busId, $sNum, 'upper', 'sleeper', $r, 1, 0, 50.00]);

                // Col 2: Double inner
                $sNum = 'U' . $seatIdx++;
                $isLadies = ($r <= 2) ? 1 : 0;
                $stmt->execute([$busId, $sNum, 'upper', 'sleeper', $r, 2, $isLadies, 0.00]);

                // Col 3: Double window
                $sNum = 'U' . $seatIdx++;
                $stmt->execute([$busId, $sNum, 'upper', 'sleeper', $r, 3, $isLadies, 0.00]);
            }
        } elseif ($layout === 'seater_2x2') {
            // 40 Seater Seats (10 rows x 4 seats: A, B [aisle] C, D)
            for ($r = 1; $r <= 10; $r++) {
                $cols = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
                foreach ($cols as $colChar => $colNum) {
                    $sNum = $r . $colChar;
                    $isLadies = ($r === 1 && ($colChar === 'A' || $colChar === 'B')) ? 1 : 0;
                    $stmt->execute([$busId, $sNum, 'lower', 'seater', $r, $colNum, $isLadies, 0.00]);
                }
            }
        } elseif ($layout === 'mixed_seater_sleeper') {
            // Lower Deck: 20 Seater Seats (5 rows x 4 seats)
            for ($r = 1; $r <= 5; $r++) {
                $cols = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
                foreach ($cols as $colChar => $colNum) {
                    $sNum = 'S' . $r . $colChar;
                    $isLadies = ($r === 1 && ($colChar === 'A' || $colChar === 'B')) ? 1 : 0;
                    $stmt->execute([$busId, $sNum, 'lower', 'seater', $r, $colNum, $isLadies, 0.00]);
                }
            }
            // Upper Deck: 10 Sleeper Berths (5 rows x 2 sleeper berths)
            for ($r = 1; $r <= 5; $r++) {
                $sNum1 = 'U' . ($r * 2 - 1);
                $stmt->execute([$busId, $sNum1, 'upper', 'sleeper', $r, 1, 0, 150.00]);
                $sNum2 = 'U' . ($r * 2);
                $stmt->execute([$busId, $sNum2, 'upper', 'sleeper', $r, 2, 0, 150.00]);
            }
        }
    }
    echo "[✓] Bus seat layouts created.\n";

    // 7. Seed Routes
    echo "[...] Seeding routes...\n";
    $cityMap = [];
    $allCities = $pdo->query("SELECT id, name FROM indian_cities")->fetchAll();
    foreach ($allCities as $c) {
        $cityMap[$c['name']] = $c['id'];
    }

    $routesData = [
        [
            'from' => 'Ahmedabad',
            'to' => 'Mumbai',
            'dist' => 525,
            'duration' => '8 hrs 30 mins',
            'minutes' => 510,
            'boarding' => [
                ['name' => 'Paldi Cross Road (Shreenathji Travels HO)', 'time_offset' => 0, 'landmark' => 'Nr. Mahalaxmi Cross Roads'],
                ['name' => 'Geeta Mandir Bus Stand', 'time_offset' => 20, 'landmark' => 'Near ST Stand Gate No. 2'],
                ['name' => 'C.T.M. Express Highway Toll', 'time_offset' => 45, 'landmark' => 'Below BRTS Flyover']
            ],
            'dropping' => [
                ['name' => 'Borivali East', 'time_offset' => 480, 'landmark' => 'Opp. National Park Bridge'],
                ['name' => 'Andheri East', 'time_offset' => 500, 'landmark' => 'Gundavali Metro Station'],
                ['name' => 'Sion Circle', 'time_offset' => 525, 'landmark' => 'Near Cinemax Cinema'],
                ['name' => 'Vashi Plaza', 'time_offset' => 550, 'landmark' => 'Near Center One Mall']
            ]
        ],
        [
            'from' => 'Mumbai',
            'to' => 'Ahmedabad',
            'dist' => 525,
            'duration' => '8 hrs 30 mins',
            'minutes' => 510,
            'boarding' => [
                ['name' => 'Borivali East', 'time_offset' => 0, 'landmark' => 'National Park Flyover'],
                ['name' => 'Andheri East', 'time_offset' => 30, 'landmark' => 'Near Bisleri Compound'],
                ['name' => 'Thane Teen Hath Naka', 'time_offset' => 60, 'landmark' => 'Eternity Mall Service Road']
            ],
            'dropping' => [
                ['name' => 'C.T.M. Cross Road', 'time_offset' => 480, 'landmark' => 'Express Toll Plaza'],
                ['name' => 'Geeta Mandir', 'time_offset' => 500, 'landmark' => 'ST Workshop Gate'],
                ['name' => 'Paldi Shreenathji Office', 'time_offset' => 520, 'landmark' => 'Shreenathji Complex']
            ]
        ],
        [
            'from' => 'Ahmedabad',
            'to' => 'Udaipur',
            'dist' => 260,
            'duration' => '5 hrs 15 mins',
            'minutes' => 315,
            'boarding' => [
                ['name' => 'Paldi', 'time_offset' => 0, 'landmark' => 'Paldi Bus Station'],
                ['name' => 'Naroda Patiya', 'time_offset' => 30, 'landmark' => 'Galaxy Cinema'],
                ['name' => 'Chiloda Circle', 'time_offset' => 55, 'landmark' => 'Highway Hotel']
            ],
            'dropping' => [
                ['name' => 'Paras Circle', 'time_offset' => 290, 'landmark' => 'Paras Talkies Road'],
                ['name' => 'Udiapole Central Bus Stand', 'time_offset' => 315, 'landmark' => 'Near Shreenathji Travels Udaipur Office']
            ]
        ],
        [
            'from' => 'Udaipur',
            'to' => 'Ahmedabad',
            'dist' => 260,
            'duration' => '5 hrs 15 mins',
            'minutes' => 315,
            'boarding' => [
                ['name' => 'Udiapole', 'time_offset' => 0, 'landmark' => 'Near Bus Stand'],
                ['name' => 'Goverdhan Vilas', 'time_offset' => 20, 'landmark' => 'Highway Overbridge']
            ],
            'dropping' => [
                ['name' => 'Naroda Patiya', 'time_offset' => 280, 'landmark' => 'Galaxy Cinema'],
                ['name' => 'Paldi Shreenathji HO', 'time_offset' => 315, 'landmark' => 'Shreenathji Travels']
            ]
        ],
        [
            'from' => 'Surat',
            'to' => 'Pune',
            'dist' => 415,
            'duration' => '7 hrs 45 mins',
            'minutes' => 465,
            'boarding' => [
                ['name' => 'Kamrej Char Rasta', 'time_offset' => 0, 'landmark' => 'Opp. Kamrej Police Station'],
                ['name' => 'Varachha Central Office', 'time_offset' => 25, 'landmark' => 'Hirabaug Circle'],
                ['name' => 'Kadodara Char Rasta', 'time_offset' => 45, 'landmark' => 'Highway Crossing']
            ],
            'dropping' => [
                ['name' => 'Wakad Flyover', 'time_offset' => 420, 'landmark' => 'Near Ginger Hotel'],
                ['name' => 'Shivajinagar', 'time_offset' => 450, 'landmark' => 'Bank of Maharashtra'],
                ['name' => 'Swargate', 'time_offset' => 480, 'landmark' => 'Laxmi Narayan Cinema']
            ]
        ],
        [
            'from' => 'Pune',
            'to' => 'Surat',
            'dist' => 415,
            'duration' => '7 hrs 45 mins',
            'minutes' => 465,
            'boarding' => [
                ['name' => 'Swargate', 'time_offset' => 0, 'landmark' => 'Swargate Bus Stand'],
                ['name' => 'Shivajinagar', 'time_offset' => 20, 'landmark' => 'Near Sancheti Hospital'],
                ['name' => 'Wakad Hinjewadi Bridge', 'time_offset' => 45, 'landmark' => 'Highway Chowk']
            ],
            'dropping' => [
                ['name' => 'Kadodara', 'time_offset' => 420, 'landmark' => 'Toll Gate'],
                ['name' => 'Kamrej Char Rasta', 'time_offset' => 445, 'landmark' => 'Kamrej Junction'],
                ['name' => 'Varachha', 'time_offset' => 465, 'landmark' => 'Hirabaug']
            ]
        ],
        [
            'from' => 'Jaipur',
            'to' => 'New Delhi',
            'dist' => 280,
            'duration' => '5 hrs 30 mins',
            'minutes' => 330,
            'boarding' => [
                ['name' => 'Sindhi Camp', 'time_offset' => 0, 'landmark' => 'Near Metro Station Gate 1'],
                ['name' => 'Narayan Singh Circle', 'time_offset' => 20, 'landmark' => 'Near City Palace Gate'],
                ['name' => 'Transport Nagar', 'time_offset' => 40, 'landmark' => 'Delhi Bypass Road']
            ],
            'dropping' => [
                ['name' => 'Dhaula Kuan', 'time_offset' => 300, 'landmark' => 'Metro Station Pillar 32'],
                ['name' => 'Karol Bagh', 'time_offset' => 320, 'landmark' => 'Metro Gate 8'],
                ['name' => 'Kashmere Gate ISBT', 'time_offset' => 340, 'landmark' => 'ISBT Arrival Bay 4']
            ]
        ],
        [
            'from' => 'New Delhi',
            'to' => 'Jaipur',
            'dist' => 280,
            'duration' => '5 hrs 30 mins',
            'minutes' => 330,
            'boarding' => [
                ['name' => 'Kashmere Gate ISBT', 'time_offset' => 0, 'landmark' => 'Departure Bay 2'],
                ['name' => 'Dhaula Kuan', 'time_offset' => 35, 'landmark' => 'Near Indian Oil Petrol Pump'],
                ['name' => 'IFFCO Chowk Gurugram', 'time_offset' => 60, 'landmark' => 'Gurugram Highway']
            ],
            'dropping' => [
                ['name' => 'Transport Nagar', 'time_offset' => 300, 'landmark' => 'Jaipur Entry Checkpost'],
                ['name' => 'Sindhi Camp', 'time_offset' => 330, 'landmark' => 'Central Bus Stand']
            ]
        ],
        [
            'from' => 'Ahmedabad',
            'to' => 'Rajkot',
            'dist' => 215,
            'duration' => '4 hrs 15 mins',
            'minutes' => 255,
            'boarding' => [
                ['name' => 'Paldi Shreenathji HO', 'time_offset' => 0, 'landmark' => 'Near Paldi Bus Stand'],
                ['name' => 'Satellite / ISKCON', 'time_offset' => 25, 'landmark' => 'ISKCON Cross Roads BRTS'],
                ['name' => 'Bopal Ring Road', 'time_offset' => 40, 'landmark' => 'Vakil Saheb Bridge']
            ],
            'dropping' => [
                ['name' => 'Greenland Chokdi', 'time_offset' => 220, 'landmark' => 'Highway Circle'],
                ['name' => 'Moti Tanki Chowk', 'time_offset' => 245, 'landmark' => 'Limda Chowk'],
                ['name' => 'Rajkot ST Stand Area', 'time_offset' => 255, 'landmark' => 'Opp. Central Bus Station']
            ]
        ],
        [
            'from' => 'Rajkot',
            'to' => 'Ahmedabad',
            'dist' => 215,
            'duration' => '4 hrs 15 mins',
            'minutes' => 255,
            'boarding' => [
                ['name' => 'Moti Tanki Chowk', 'time_offset' => 0, 'landmark' => 'Shreenathji Travels Office'],
                ['name' => 'Greenland Chokdi', 'time_offset' => 25, 'landmark' => 'Ahmedabad Highway Touch']
            ],
            'dropping' => [
                ['name' => 'ISKCON Cross Road', 'time_offset' => 225, 'landmark' => 'Near Fun Republic'],
                ['name' => 'Paldi Central Office', 'time_offset' => 255, 'landmark' => 'Shreenathji Travels']
            ]
        ],
        [
            'from' => 'Bengaluru',
            'to' => 'Panaji',
            'dist' => 560,
            'duration' => '11 hrs 00 mins',
            'minutes' => 660,
            'boarding' => [
                ['name' => 'Majestic / Anand Rao Circle', 'time_offset' => 0, 'landmark' => 'Near SRS Travels'],
                ['name' => 'Yeshwantpur', 'time_offset' => 30, 'landmark' => 'Govardhan Theater'],
                ['name' => 'Nelamangala Toll', 'time_offset' => 60, 'landmark' => 'Highway Flyover']
            ],
            'dropping' => [
                ['name' => 'Mapusa Court', 'time_offset' => 620, 'landmark' => 'Mapusa Bus Stand'],
                ['name' => 'Panaji KTC Bus Stand', 'time_offset' => 660, 'landmark' => 'Patto Plaza']
            ]
        ]
    ];

    $routeInsert = $pdo->prepare("INSERT INTO routes (from_city_id, to_city_id, distance_km, estimated_duration, duration_minutes, boarding_points, dropping_points, is_active)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                                  ON DUPLICATE KEY UPDATE distance_km=VALUES(distance_km), estimated_duration=VALUES(estimated_duration), 
                                                          duration_minutes=VALUES(duration_minutes), boarding_points=VALUES(boarding_points), dropping_points=VALUES(dropping_points)");

    foreach ($routesData as $rData) {
        if (!isset($cityMap[$rData['from']]) || !isset($cityMap[$rData['to']])) {
            continue;
        }
        $fromId = $cityMap[$rData['from']];
        $toId = $cityMap[$rData['to']];
        $routeInsert->execute([
            $fromId,
            $toId,
            $rData['dist'],
            $rData['duration'],
            $rData['minutes'],
            json_encode($rData['boarding'], JSON_UNESCAPED_UNICODE),
            json_encode($rData['dropping'], JSON_UNESCAPED_UNICODE)
        ]);
    }
    echo "[✓] Core routes seeded.\n";

    // 8. Generate Schedules for next 30 days starting from 2026-10-04
    echo "[...] Generating schedules for current and upcoming dates...\n";
    $allRoutes = $pdo->query("SELECT r.id, r.duration_minutes, fc.name as from_city, tc.name as to_city 
                              FROM routes r 
                              JOIN indian_cities fc ON r.from_city_id = fc.id 
                              JOIN indian_cities tc ON r.to_city_id = tc.id")->fetchAll();

    // Map specific bus assignments and times for variety
    $busTemplates = [
        ['bus_id' => 1, 'dep_time' => '21:00:00', 'base_fare' => 1150.00, 'sleeper_fare' => 1250.00], // Volvo Multi-Axle
        ['bus_id' => 2, 'dep_time' => '07:30:00', 'base_fare' => 680.00,  'sleeper_fare' => null],    // BharatBenz Seater Morning
        ['bus_id' => 3, 'dep_time' => '22:30:00', 'base_fare' => 1100.00, 'sleeper_fare' => 1200.00], // Garuda Sleeper Night
        ['bus_id' => 4, 'dep_time' => '14:00:00', 'base_fare' => 750.00,  'sleeper_fare' => 950.00],  // Mixed Afternoon
        ['bus_id' => 5, 'dep_time' => '23:15:00', 'base_fare' => 1350.00, 'sleeper_fare' => 1450.00], // Scania Gold Class
        ['bus_id' => 6, 'dep_time' => '16:45:00', 'base_fare' => 520.00,  'sleeper_fare' => null]     // Non-AC Seater
    ];

    $schedStmt = $pdo->prepare("INSERT INTO schedules (bus_id, route_id, departure_date, departure_time, arrival_date, arrival_time, base_fare, sleeper_fare, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");

    $startDate = new DateTime('2026-10-04');
    $schedCount = 0;

    // Check if schedules exist for this range
    $existingSched = $pdo->query("SELECT COUNT(*) FROM schedules WHERE departure_date >= '2026-10-04'")->fetchColumn();
    if ($existingSched < 50) {
        for ($day = 0; $day < 21; $day++) {
            $currentDate = clone $startDate;
            $currentDate->modify("+{$day} days");
            $dateStr = $currentDate->format('Y-m-d');

            foreach ($allRoutes as $route) {
                $routeId = $route['id'];
                $durMin = (int)$route['duration_minutes'];

                // Assign 2 to 4 buses per route per day
                $selectedTemplates = [
                    $busTemplates[($routeId) % count($busTemplates)],
                    $busTemplates[($routeId + 2) % count($busTemplates)]
                ];
                if ($routeId % 2 == 0) {
                    $selectedTemplates[] = $busTemplates[($routeId + 4) % count($busTemplates)];
                }

                foreach ($selectedTemplates as $tmpl) {
                    $depTime = $tmpl['dep_time'];
                    $depDateTime = new DateTime("{$dateStr} {$depTime}");
                    $arrDateTime = clone $depDateTime;
                    $arrDateTime->modify("+{$durMin} minutes");

                    $arrDateStr = $arrDateTime->format('Y-m-d');
                    $arrTimeStr = $arrDateTime->format('H:i:s');

                    $schedStmt->execute([
                        $tmpl['bus_id'],
                        $routeId,
                        $dateStr,
                        $depTime,
                        $arrDateStr,
                        $arrTimeStr,
                        $tmpl['base_fare'],
                        $tmpl['sleeper_fare']
                    ]);
                    $schedCount++;
                }
            }
        }
        echo "[✓] Generated {$schedCount} schedules for dates {$startDate->format('d M Y')} onwards.\n";
    } else {
        echo "[i] Schedules already exist ({$existingSched} schedules found).\n";
    }

    // 9. Create a sample confirmed booking for demonstration
    $existingBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    if ($existingBookings == 0) {
        echo "[...] Creating sample completed/confirmed booking for demo user...\n";
        $sched = $pdo->query("SELECT s.*, r.boarding_points, r.dropping_points 
                              FROM schedules s 
                              JOIN routes r ON s.route_id = r.id 
                              WHERE s.departure_date = '2026-10-06' LIMIT 1")->fetch();

        if ($sched) {
            $bpList = json_decode($sched['boarding_points'], true);
            $dpList = json_decode($sched['dropping_points'], true);
            $bpName = $bpList[0]['name'] ?? 'Main Bus Stand';
            $dpName = $dpList[0]['name'] ?? 'Destination Stop';

            $pnr = 'ST-2026-DEMO01';
            $baseAmt = (float)$sched['base_fare'] * 2;
            $taxAmt = round($baseAmt * 0.05, 2);
            $convFee = 25.00;
            $totalAmt = $baseAmt + $taxAmt + $convFee;

            $bStmt = $pdo->prepare("INSERT INTO bookings (pnr, user_id, schedule_id, journey_date, contact_name, contact_email, contact_mobile, boarding_point, dropping_point, total_seats, base_amount, tax_amount, convenience_fee, total_amount, payment_status, booking_status, payment_method, created_at)
                                    VALUES (?, 1, ?, '2026-10-06', 'Rahul Sharma', 'user@shreenathji.com', '9876543210', ?, ?, 2, ?, ?, ?, ?, 'paid', 'confirmed', 'razorpay', NOW())");
            $bStmt->execute([$pnr, $sched['id'], $bpName, $dpName, $baseAmt, $taxAmt, $convFee, $totalAmt]);
            $bId = $pdo->lastInsertId();

            // Insert passengers
            $pStmt = $pdo->prepare("INSERT INTO booking_passengers (booking_id, seat_number, passenger_name, age, gender, seat_fare) VALUES (?, ?, ?, ?, ?, ?)");
            $pStmt->execute([$bId, 'L4', 'Rahul Sharma', 32, 'male', $sched['base_fare']]);
            $pStmt->execute([$bId, 'L5', 'Pooja Sharma', 29, 'female', $sched['base_fare']]);

            // Insert payment record
            $payStmt = $pdo->prepare("INSERT INTO payments (booking_id, razorpay_order_id, razorpay_payment_id, razorpay_signature, amount, currency, payment_method, status, raw_response, created_at)
                                      VALUES (?, 'order_demo_1001', 'pay_demo_verified_9988', 'simulated_test_sig', ?, 'INR', 'upi', 'captured', '{\"status\":\"captured\",\"method\":\"upi\"}', NOW())");
            $payStmt->execute([$bId, $totalAmt]);

            echo "[✓] Demo booking created with PNR {$pnr}.\n";
        }
    }

    echo "\n=== Database Installation & Seeding Successfully Completed! ===\n";
    echo "Default Admin Login: admin@shreenathji.com / Admin@12345\n";
    echo "Default User Login:  user@shreenathji.com  / User@12345\n";

} catch (Exception $e) {
    echo "[-] Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
