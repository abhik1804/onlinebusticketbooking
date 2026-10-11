-- Sample Data for Shreenathji Travels
USE `shreenathji_travels`;

-- 1. Insert Admins
INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`) VALUES
(1, 'Super Administrator', 'admin@shreenathji.com', '$2y$10$JClIkPLmNBwapTeyq1r1x.UoSCuSEZAgEkNiDZvgvRIPWy6W/.3vC', 'super_admin', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. Insert Users
INSERT INTO `users` (`id`, `full_name`, `email`, `mobile`, `password_hash`, `gender`, `address`, `is_active`) VALUES
(1, 'Rahul Sharma', 'user@shreenathji.com', '9876543210', '$2y$10$MvpO.R5agwi0Vs/2GMW1D.JA6lCuWr2woPsaPpjQ.jKI0WVcuPV9a', 'male', 'Satellite, Ahmedabad, Gujarat', 1)
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- 3. Insert Website Settings
INSERT INTO `website_settings` (`key_name`, `key_value`, `description`) VALUES
('site_name', 'Shreenathji Travels', 'Website Brand Name'),
('site_tagline', 'Your Journey, Our Responsibility', 'Website Tagline'),
('site_phone', '+91 79 2658 9999', 'Customer Care Phone'),
('site_emergency_phone', '+91 98250 12345', '24/7 Helpline Number'),
('site_email', 'support@shreenathjitravels.com', 'Support Email'),
('site_address', 'Shreenathji House, Nr. Paldi Cross Roads, Ellisbridge, Ahmedabad, Gujarat 380006', 'Head Office Address'),
('currency_symbol', '₹', 'Currency Symbol'),
('tax_percentage', '5.00', 'GST Percentage applicable on bus tickets'),
('convenience_fee', '25.00', 'Flat convenience fee per booking'),
('razorpay_key_id', 'rzp_test_1DP5mmOlF5G5ag', 'Razorpay Test Key ID'),
('razorpay_key_secret', 's9P7qW9t0Zk1xY2a3b4c5d6e', 'Razorpay Test Key Secret'),
('razorpay_webhook_secret', 'st_whsec_test_998877', 'Razorpay Webhook Secret'),
('cancellation_policy_24h', '90', 'Refund percentage if cancelled > 24 hours before departure'),
('cancellation_policy_12h', '70', 'Refund percentage if cancelled 12-24 hours before departure'),
('cancellation_policy_0h', '0', 'Refund percentage if cancelled < 12 hours before departure'),
('maintenance_mode', '0', 'Set to 1 for maintenance mode')
ON DUPLICATE KEY UPDATE `key_value`=VALUES(`key_value`);

-- 4. Insert FAQs
INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`, `is_active`) VALUES
('How do I book a ticket on Shreenathji Travels?', 'Select your From and To cities, choose your journey date, select your preferred bus, pick your favorite seats on the interactive layout, fill in passenger details, and pay securely using Razorpay (UPI, Cards, NetBanking). You will receive instant ticket confirmation with PNR.', 'Booking', 1, 1),
('Can I cancel my ticket and get a refund?', 'Yes, tickets can be cancelled online from "My Bookings" or the "Track PNR" section. If cancelled more than 24 hours prior to departure, a 90% refund is processed. For 12 to 24 hours before departure, a 70% refund applies. Cancellation within 12 hours is non-refundable.', 'Cancellation & Refund', 2, 1),
('Do I need to carry a printed copy of the ticket?', 'A digital copy (PDF ticket on your smartphone) along with a valid government photo ID (Aadhar, PAN, Driving License, or Passport) is completely acceptable by our bus captains.', 'Boarding & Travel', 3, 1),
('What amenities are provided in Shreenathji luxury buses?', 'Our premium fleet features high-speed Wi-Fi, individual charging ports, sanitized blankets & fresh pillows (in sleepers), complimentary packaged water bottles, GPS live tracking, CCTV surveillance, and emergency panic buttons.', 'Amenities', 4, 1),
('Is it safe for solo female travelers?', 'Absolutely. We provide dedicated Ladies-reserved seat options where male travelers cannot book adjacent seats without authorization. All buses have active GPS tracking and 24/7 route monitoring from our central control room.', 'Safety', 5, 1);

-- 5. Insert Buses
INSERT INTO `buses` (`id`, `bus_name`, `bus_number`, `operator_name`, `bus_type`, `seating_layout`, `total_seats`, `primary_image`, `image_type`, `amenities`, `cancellation_policy`, `is_active`) VALUES
(1, 'Shreenathji Royal Cruiser Multi-Axle Volvo', 'GJ-01-ST-1008', 'Shreenathji Travels', 'Volvo Multi-Axle AC Sleeper', 'sleeper_2x1', 30, 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80', 'url', '["wifi", "water_bottle", "charging_point", "blanket", "reading_light", "gps_tracking", "cctv"]', 'Free cancellation up to 24 hours before departure. 10% fee applies within 24h.', 1),
(2, 'Shreenathji BharatBenz Diamond Seater', 'GJ-01-ST-2024', 'Shreenathji Travels', 'BharatBenz AC Seater (2+2)', 'seater_2x2', 40, 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&w=800&q=80', 'url', '["wifi", "water_bottle", "charging_point", "reading_light", "gps_tracking"]', 'Cancel up to 12 hours before departure for 70% refund.', 1),
(3, 'Shreenathji Garuda Dream Sleeper', 'GJ-05-ST-3301', 'Shreenathji Travels', 'AC Sleeper (2+1)', 'sleeper_2x1', 30, 'https://images.unsplash.com/photo-1557223562-6c77ef16210f?auto=format&fit=crop&w=800&q=80', 'url', '["wifi", "water_bottle", "charging_point", "blanket", "cctv", "first_aid"]', 'Standard cancellation policy applies.', 1),
(4, 'Shreenathji Express Hybrid Comfort', 'GJ-03-ST-4590', 'Shreenathji Travels', 'AC Seater + Sleeper', 'mixed_seater_sleeper', 30, 'https://images.unsplash.com/photo-1598971861713-54ad16a7e72e?auto=format&fit=crop&w=800&q=80', 'url', '["charging_point", "reading_light", "gps_tracking", "water_bottle"]', 'Standard policy applies.', 1),
(5, 'Shreenathji Gold Class Scania 9600', 'GJ-27-ST-8899', 'Shreenathji Travels', 'Volvo Multi-Axle AC Sleeper', 'sleeper_2x1', 30, 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80', 'url', '["wifi", "water_bottle", "charging_point", "blanket", "reading_light", "gps_tracking", "cctv", "first_aid"]', 'Premium flexi-cancel policy.', 1),
(6, 'Shreenathji Star Express Non-AC', 'GJ-01-ST-5511', 'Shreenathji Travels', 'Non-AC Seater (2+2)', 'seater_2x2', 40, 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&w=800&q=80', 'url', '["first_aid", "reading_light"]', 'Standard policy applies.', 1)
ON DUPLICATE KEY UPDATE `bus_name`=VALUES(`bus_name`);
