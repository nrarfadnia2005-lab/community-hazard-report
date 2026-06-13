<?php
// Shared helper functions used across the project

function respond($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function generateReportId(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $id = 'CHR-';
    for ($i = 0; $i < 6; $i++) {
        $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}

function cleanInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function handleFileUpload(string $fieldName, string $uploadDir): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$fieldName];
    
    // Validate by both mime type and extension to be robust against mobile phone upload differences
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif', 'application/octet-stream'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $isMimeAllowed = in_array($file['type'], $allowedMimes);
    $isExtAllowed  = in_array($ext, $allowedExts);
    
    if (!$isMimeAllowed && !$isExtAllowed) {
        return null;
    }
    
    // Support larger modern smartphone photos (up to 30MB) matching the 40MB post/upload php.ini limit
    if ($file['size'] > 30 * 1024 * 1024) {
        return null;
    }
    
    $filename = uniqid('img_', true) . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/' . $uploadDir . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
        return $filename;
    }
    return null;
}

function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

function getExifGps(string $filePath): ?array {
    // 1. Try standard exif extension first
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($filePath);
        if ($exif && isset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'])) {
            $lat = getGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
            $lng = getGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
            
            if (abs($lat) < 0.0001 && abs($lng) < 0.0001) return null;
            
            return ['lat' => $lat, 'lng' => $lng];
        }
    }
    
    // 2. Fall back to robust pure PHP binary parser (in case exif PHP extension is disabled/unavailable)
    $gps = getExifGpsPurePHP($filePath);
    if ($gps && abs($gps['lat']) < 0.0001 && abs($gps['lng']) < 0.0001) return null;
    return $gps;
}

function getExifGpsPurePHP(string $filePath): ?array {
    $fp = @fopen($filePath, 'rb');
    if (!$fp) return null;
    
    $hdr = fread($fp, 2);
    if ($hdr !== "\xFF\xD8") {
        fclose($fp);
        return null;
    }
    
    $data = null;
    while (!feof($fp)) {
        $marker = fread($fp, 2);
        if (strlen($marker) < 2) break;
        if ($marker[0] !== "\xFF") break;
        
        $mType = ord($marker[1]);
        if ($mType === 0xD9 || $mType === 0xDA) break; // End of image or Start of scan
        
        $lenData = fread($fp, 2);
        if (strlen($lenData) < 2) break;
        $len = unpack('n', $lenData)[1] - 2;
        
        if ($mType === 0xE1) { // APP1 (EXIF)
            $data = fread($fp, $len);
            break;
        } else {
            fseek($fp, $len, SEEK_CUR);
        }
    }
    fclose($fp);
    
    if (!$data || substr($data, 0, 6) !== "Exif\0\0") return null;
    
    $tiffData = substr($data, 6);
    if (strlen($tiffData) < 8) return null;
    
    $byteOrder = substr($tiffData, 0, 2);
    $isLittleEndian = ($byteOrder === 'II');
    if ($byteOrder !== 'II' && $byteOrder !== 'MM') return null;
    
    $magic = $isLittleEndian ? "\x2A\x00" : "\x00\x2A";
    if (substr($tiffData, 2, 2) !== $magic) return null;
    
    $ifdOffset = $isLittleEndian ? unpack('V', substr($tiffData, 4, 4))[1] : unpack('N', substr($tiffData, 4, 4))[1];
    
    $gpsOffset = null;
    while ($ifdOffset > 0 && $ifdOffset < strlen($tiffData)) {
        if ($ifdOffset + 2 > strlen($tiffData)) break;
        $numEntries = $isLittleEndian ? unpack('v', substr($tiffData, $ifdOffset, 2))[1] : unpack('n', substr($tiffData, $ifdOffset, 2))[1];
        $entryOffset = $ifdOffset + 2;
        
        for ($i = 0; $i < $numEntries; $i++) {
            if ($entryOffset + 12 > strlen($tiffData)) break;
            $tag = $isLittleEndian ? unpack('v', substr($tiffData, $entryOffset, 2))[1] : unpack('n', substr($tiffData, $entryOffset, 2))[1];
            
            if ($tag === 0x8825) { // GPS Info IFD Pointer
                $gpsOffset = $isLittleEndian ? unpack('V', substr($tiffData, $entryOffset + 8, 4))[1] : unpack('N', substr($tiffData, $entryOffset + 8, 4))[1];
                break 2;
            }
            $entryOffset += 12;
        }
        
        $nextIfdOffset = $isLittleEndian ? unpack('V', substr($tiffData, $entryOffset, 4))[1] : unpack('N', substr($tiffData, $entryOffset, 4))[1];
        $ifdOffset = $nextIfdOffset;
    }
    
    if (!$gpsOffset || $gpsOffset >= strlen($tiffData)) return null;
    
    $numEntries = $isLittleEndian ? unpack('v', substr($tiffData, $gpsOffset, 2))[1] : unpack('n', substr($tiffData, $gpsOffset, 2))[1];
    $entryOffset = $gpsOffset + 2;
    
    $lat = null; $lng = null;
    $latRef = 'N'; $lngRef = 'E';
    
    for ($i = 0; $i < $numEntries; $i++) {
        if ($entryOffset + 12 > strlen($tiffData)) break;
        $tag = $isLittleEndian ? unpack('v', substr($tiffData, $entryOffset, 2))[1] : unpack('n', substr($tiffData, $entryOffset, 2))[1];
        $count = $isLittleEndian ? unpack('V', substr($tiffData, $entryOffset + 4, 4))[1] : unpack('N', substr($tiffData, $entryOffset + 4, 4))[1];
        
        if ($tag === 1) { // GPSLatitudeRef
            $latRef = substr($tiffData, $entryOffset + 8, 1);
        } elseif ($tag === 2) { // GPSLatitude
            $valOffset = $isLittleEndian ? unpack('V', substr($tiffData, $entryOffset + 8, 4))[1] : unpack('N', substr($tiffData, $entryOffset + 8, 4))[1];
            $lat = parseRationalCoords($tiffData, $valOffset, $count, $isLittleEndian);
        } elseif ($tag === 3) { // GPSLongitudeRef
            $lngRef = substr($tiffData, $entryOffset + 8, 1);
        } elseif ($tag === 4) { // GPSLongitude
            $valOffset = $isLittleEndian ? unpack('V', substr($tiffData, $entryOffset + 8, 4))[1] : unpack('N', substr($tiffData, $entryOffset + 8, 4))[1];
            $lng = parseRationalCoords($tiffData, $valOffset, $count, $isLittleEndian);
        }
        $entryOffset += 12;
    }
    
    if ($lat && $lng && count($lat) >= 3 && count($lng) >= 3) {
        $latVal = $lat[0] + ($lat[1]/60) + ($lat[2]/3600);
        $lngVal = $lng[0] + ($lng[1]/60) + ($lng[2]/3600);
        if ($latRef === 'S') $latVal = -$latVal;
        if ($lngRef === 'W') $lngVal = -$lngVal;
        return ['lat' => $latVal, 'lng' => $lngVal];
    }
    
    return null;
}

function parseRationalCoords($tiffData, $offset, $count, $isLittleEndian) {
    $coords = [];
    for ($i = 0; $i < $count; $i++) {
        $itemOffset = $offset + ($i * 8);
        if ($itemOffset + 8 > strlen($tiffData)) break;
        $num = $isLittleEndian ? unpack('V', substr($tiffData, $itemOffset, 4))[1] : unpack('N', substr($tiffData, $itemOffset, 4))[1];
        $den = $isLittleEndian ? unpack('V', substr($tiffData, $itemOffset + 4, 4))[1] : unpack('N', substr($tiffData, $itemOffset + 4, 4))[1];
        $coords[] = $den > 0 ? $num / $den : $num;
    }
    return $coords;
}

function getGpsCoordinate($coordinate, $ref): float {
    if (!$coordinate || !is_array($coordinate)) return 0.0;

    $degrees = count($coordinate) > 0 ? gpsFractionToFloat($coordinate[0]) : 0.0;
    $minutes = count($coordinate) > 1 ? gpsFractionToFloat($coordinate[1]) : 0.0;
    $seconds = count($coordinate) > 2 ? gpsFractionToFloat($coordinate[2]) : 0.0;

    $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
    return ($ref === 'S' || $ref === 'W') ? -$decimal : $decimal;
}

function gpsFractionToFloat($fraction): float {
    if (is_float($fraction) || is_int($fraction)) return (float)$fraction;
    $parts = explode('/', $fraction);
    if (count($parts) <= 0) return 0.0;
    if (count($parts) == 1) return (float)$parts[0];
    if ((float)$parts[1] != 0) {
        return (float)$parts[0] / (float)$parts[1];
    }
    return 0.0;
}

function isOriginalCameraPhoto(string $filePath): bool {
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($filePath);
        // A genuine camera photo must have brand/model/hardware metadata tags (Make, Model, DateTimeOriginal, or FocalLength)
        if ($exif && (isset($exif['Make']) || isset($exif['Model']) || isset($exif['DateTimeOriginal']) || isset($exif['FocalLength']))) {
            // Reject if edited in Photoshop, GIMP, or Illustrator
            if (isset($exif['Software'])) {
                $software = strtolower($exif['Software']);
                if (strpos($software, 'photoshop') !== false || strpos($software, 'gimp') !== false || strpos($software, 'illustrator') !== false) {
                    return false;
                }
            }
            return true;
        }
    }
    return false;
}
?>
