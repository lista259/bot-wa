<?php

//Terima data dari API
$input = json_decode(file_get_contents("php://input"), true);

$from = $input["sender"] ?? "";
$text = strtolower(trim($input["message"] ?? ""));
$name = $input["name"] ?? "customer";

if (!$input){ echo "No Data"; exit; }


//Fungsi balas pesan
$API_KEY = "X19GmB88bDUv8ebpu3p7"; //Sesuaikan token API nya

function reply($to, $msg, $API_KEY){

    $ch = curl_init("https://api.fonnte.com/send");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: $API_KEY"],
        CURLOPT_POSTFIELDS => [
            "target"  => $to,
            "message" => $msg
        ]
    ]);
    curl_exec($ch);
    curl_close($ch);
}

//Ambil state percakapan dari file JSON
$state_file = "state.json";
$state = file_exists($state_file)
    ? json_decode(file_get_contents($state_file), true)
    : [];

if (!isset($state[$from])) {
    $state[$from] = ["step" => "tanya_nama"];
}

$step = $state[$from]["step"];

//LOGIKA PROSES PERCAKAPAN

$MENU =
"1. Jam buka kantor desa\n".
"2. Syarat surat keterangan domisili\n".
"3. Syarat surat keterangan usaha (SKU)\n".
"4. Syarat surat keterangan tidak mampu (SKTM)\n".
"5. Syarat KK baru / pindah domisili\n".
"6. Syarat akta kelahiran / kematian\n".
"7. Jadwal posyandu / vaksinasi\n".
"8. Informasi bantuan sosial (BLT, PKH, dll)\n".
"9. Hubungi perangkat desa";


//1. Bot tanya nama user diawal percakapan
if ($step === "tanya_nama") {
    reply($from, "Halo ka, boleh disebutkan namanya? 😊", $API_KEY);
    $state[$from]["step"] = "dapat_nama";
}

//2. Simpan nama user
elseif ($step === "dapat_nama") {

    $state[$from]["nama"] = ucfirst($text);

    reply($from,
        "Hai *{$state[$from]['nama']}*, selamat datang di Desa Cilopang! \n\n".
        "Ada yang bisa kami bantu, Bapak/Ibu?",
        $API_KEY);

    $state[$from]["step"] = "menu";
}

// Jawab pertanyaan berdasarkan keyword

elseif ($step === "menu") {

    // Tentukan keyword dari input user
    $map = [
        "1" => "jam",
        "2" => "domisili",
        "3" => "sku",
        "4" => "sktm",
        "5" => "kk",
        "6" => "akta",
        "7" => "posyandu",
        "8" => "bansos",
        "9" => "kontak"
    ];
    if (isset($map[$text])) $text = $map[$text];

    // Buat variasi keyword
    $keywords = [
        "jam"      => ["jam", "buka", "layanan"],
        "domisili" => ["domisili"],
        "sku"      => ["usaha", "sku"],
        "sktm"     => ["sktm", "tidak mampu"],
        "kk"       => ["kk", "kartu keluarga", "pindah"],
        "akta"     => ["akta", "lahir", "kematian"],
        "posyandu" => ["posyandu", "vaksin"],
        "bansos"   => ["bansos", "blt", "pkh"],
        "kontak"   => ["kontak", "perangkat", "admin"]
    ];

    foreach ($keywords as $key => $arr) {
        foreach ($arr as $word) {
            if (strpos($text, $word) !== false) {
                $text = $key;
                break 2;
            }
        }
    }

    //Jawab pertanyaan berdasarkan keyword
    switch ($text) {

        case "jam":
            reply($from, "🕘 *Jam Layanan Kantor Desa*\n".
            "Senin – Jumat\n".
            "08.00 – 14.00 WIB\n\n".
            "Libur: Sabtu, Minggu & Hari Besar",
            $API_KEY);
            break;

        case "domisili":
            reply($from, "*Syarat Surat Keterangan Domisili:*\n".
            "• FC KTP\n• FC KK\n• Surat pengantar RT/RW",
            $API_KEY);
            break;

        case "fasilitas":
            reply($from,
                "*Syarat Surat Keterangan Usaha (SKU):*\n".
                "• FC KTP\n• FC KK\n• Surat pengantar RT/RW",
                $API_KEY);
            break;

        case "sktm":
            reply($from,
                "*Syarat SKTM:*\n".
                "• FC KTP\n• FC KK\n• Surat pengantar RT/RW",
                $API_KEY);
            break;

        case "kk":
            reply($from,
                "*Syarat KK Baru / Pindah Domisili:*\n".
                "• FC KTP\n• FC KK lama\n• Surat pindah\n• Surat pengantar RT/RW",
                $API_KEY);
            break;

        case "akta":
            reply($from,
                "*Syarat Akta Kelahiran / Kematian:*\n".
                "• FC KTP orang tua\n• FC KK\n• Surat keterangan lahir / kematian",
                $API_KEY);
            break;

        case "posyandu":
        reply($from,
            "*Jadwal Posyandu / Vaksinasi:*\n".
            "Setiap hari Rabu minggu pertama\n".
            "Pukul 08.00 WIB\n".
            "Bertempat di Balai Desa",
            $API_KEY);
        break;

    case "bansos":
        reply($from,
            "*Informasi Bantuan Sosial:*\n".
            "Jenis: BLT, PKH, BPNT\n\n".
            "Silakan datang ke kantor desa untuk pengecekan data penerima",
            $API_KEY);
        break;

    case "kontak":
        reply($from,
            "📞 *Kontak Kantor Desa*\n".
            "WhatsApp: 08xxxxxxxxxx\n".
            "Silakan hubungi jika membutuhkan bantuan lain",
            $API_KEY);
        break;   

        default:
            reply($from,
                "Mohon maaf, Bapak/Ibu, silakan pilih menu berikut ya 😊\n\n$MENU\n\nKetik nomornya saja",
                $API_KEY);
    }
}


//Simpan histori dan step user di file JSON
file_put_contents($state_file, json_encode($state, JSON_PRETTY_PRINT));

echo "OK";
?>