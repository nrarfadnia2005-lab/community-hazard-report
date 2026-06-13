<?php
// Returns Malaysian states and districts list
header('Content-Type: application/json');

$locations = [
    "Johor" => ["Batu Pahat", "Johor Bahru", "Kluang", "Kota Tinggi", "Kulai", "Mersing", "Muar", "Pontian", "Segamat", "Tangkak"],
    "Kedah" => ["Baling", "Bandar Baharu", "Kota Setar", "Kuala Muda", "Kubang Pasu", "Kulim", "Langkawi", "Padang Terap", "Pendang", "Pokok Sena", "Sik", "Yan"],
    "Kelantan" => ["Bachok", "Gua Musang", "Jeli", "Kota Bharu", "Kuala Krai", "Machang", "Pasir Mas", "Pasir Puteh", "Tanah Merah", "Tumpat"],
    "Melaka" => ["Alor Gajah", "Central Melaka", "Jasin"],
    "Negeri Sembilan" => ["Jelebu", "Jempol", "Kuala Pilah", "Port Dickson", "Rembau", "Seremban", "Tampin"],
    "Pahang" => ["Bentong", "Bera", "Cameron Highlands", "Jerantut", "Kuantan", "Lipis", "Maran", "Pekan", "Raub", "Rompin", "Temerloh"],
    "Penang" => ["Central Seberang Perai", "North Seberang Perai", "Northeast Penang Island", "South Seberang Perai", "Southwest Penang Island"],
    "Perak" => ["Bagan Datuk", "Batang Padang", "Hilir Perak", "Hulu Perak", "Kampar", "Kerian", "Kinta", "Kuala Kangsar", "Larut, Matang and Selama", "Manjung", "Muallim", "Perak Tengah"],
    "Perlis" => ["Perlis"],
    "Sabah" => ["Beaufort", "Beluran", "Keningau", "Kinabatangan", "Kota Belud", "Kota Kinabalu", "Kota Marudu", "Kuala Penyu", "Kudat", "Kunak", "Lahad Datu", "Nabawan", "Papar", "Penampang", "Putatan", "Pitas", "Ranau", "Sandakan", "Semporna", "Sipitang", "Tambunan", "Tawau", "Telupid", "Tenom", "Tongod", "Tuaran"],
    "Sarawak" => ["Asajaya", "Bau", "Belaga", "Beluru", "Betong", "Bintulu", "Bukit Mabong", "Dalat", "Daro", "Julau", "Kabong", "Kanowit", "Kapit", "Kuching", "Lawas", "Limbang", "Lubok Antu", "Lundu", "Marudi", "Matu", "Meradong", "Miri", "Mukah", "Pakan", "Pusa", "Samarahan", "Saratok", "Sarikei", "Sebauh", "Selangau", "Serian", "Sibu", "Simunjan", "Song", "Sri Aman", "Subis", "Tanjung Manis", "Tatau", "Tebedu"],
    "Selangor" => ["Gombak", "Hulu Langat", "Hulu Selangor", "Klang", "Kuala Langat", "Kuala Selangor", "Petaling", "Sabak Bernam", "Sepang"],
    "Terengganu" => ["Besut", "Dungun", "Hulu Terengganu", "Kemaman", "Kuala Nerus", "Kuala Terengganu", "Marang", "Setiu"],
    "W.P. Kuala Lumpur" => ["Bukit Bintang", "Titiwangsa", "Setiawangsa", "Wangsa Maju", "Batu", "Kepong", "Segambut", "Lembah Pantai", "Seputeh", "Bandar Tun Razak", "Cheras"],
    "W.P. Labuan" => ["Labuan"],
    "W.P. Putrajaya" => ["Putrajaya"]
];

echo json_encode($locations);
?>
