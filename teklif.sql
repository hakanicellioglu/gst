-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 05 Tem 2025, 09:20:03
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `teklif`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ayarlar`
--

CREATE TABLE `ayarlar` (
  `id` int(11) NOT NULL,
  `doviz_kuru` decimal(10,4) DEFAULT 1.0000,
  `kdv_oran` decimal(5,2) DEFAULT 18.00,
  `varsayilan_tema` enum('light','dark') DEFAULT 'light',
  `varsayilan_dil` varchar(5) DEFAULT 'tr',
  `firma_bilgileri` text DEFAULT NULL,
  `bildirim_tercihleri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bildirim_tercihleri`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `genelteklif`
--

CREATE TABLE `genelteklif` (
  `id` int(11) NOT NULL,
  `firma` varchar(150) DEFAULT NULL,
  `yetkili` varchar(100) DEFAULT NULL,
  `tel_no` varchar(30) DEFAULT NULL,
  `adres` varchar(255) DEFAULT NULL,
  `teklif_tarihi` date DEFAULT NULL,
  `hazirlayan` varchar(100) DEFAULT NULL,
  `mail` varchar(150) DEFAULT 'satis@alumann.com.tr',
  `teslim_sekli` varchar(100) DEFAULT NULL,
  `odeme_sekli` varchar(100) DEFAULT NULL,
  `odeme_vadesi` varchar(100) DEFAULT NULL,
  `teklif_suresi` varchar(50) DEFAULT NULL,
  `vade` varchar(50) DEFAULT NULL,
  `toplam_tutar` decimal(15,2) DEFAULT NULL,
  `iskonto_oran` decimal(5,2) DEFAULT NULL,
  `iskonto_tutari` decimal(15,2) DEFAULT NULL,
  `kdv_oran` decimal(5,2) DEFAULT NULL,
  `kdv_tutari` decimal(15,2) DEFAULT NULL,
  `vergiler_dahil` tinyint(1) DEFAULT 0,
  `notlar` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `genelteklifkalemler`
--

CREATE TABLE `genelteklifkalemler` (
  `id` int(11) NOT NULL,
  `teklif_id` int(11) NOT NULL,
  `urun_adi` varchar(150) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `miktar` int(11) DEFAULT NULL,
  `birim_fiyat` decimal(10,2) DEFAULT NULL,
  `ara_toplam` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `giyotinteklif`
--

CREATE TABLE `giyotinteklif` (
  `id` int(11) NOT NULL,
  `musteri_id` int(11) DEFAULT NULL,
  `giyotin_sistemi` varchar(100) DEFAULT NULL,
  `genislik` decimal(10,2) DEFAULT NULL,
  `yukseklik` decimal(10,2) DEFAULT NULL,
  `sistem_adedi` int(11) DEFAULT NULL,
  `motor_sistemi` varchar(100) DEFAULT NULL,
  `kumanda_sistemi` varchar(100) DEFAULT NULL,
  `kumanda_adedi` int(11) DEFAULT NULL,
  `ral_kodu` varchar(50) DEFAULT NULL,
  `cam_ic` varchar(50) DEFAULT NULL,
  `cam_hava` varchar(50) DEFAULT NULL,
  `cam_dis` varchar(50) DEFAULT NULL,
  `temper` tinyint(1) DEFAULT 0,
  `cam_hava_boslugu` enum('argon','duz') DEFAULT NULL,
  `cam_en` decimal(10,2) DEFAULT NULL,
  `cam_boy` decimal(10,2) DEFAULT NULL,
  `cam_adet` int(11) DEFAULT NULL,
  `cam_renk` varchar(50) DEFAULT NULL,
  `sistem_metrekare` decimal(10,2) DEFAULT NULL,
  `aluminyum_kg` decimal(10,2) DEFAULT NULL,
  `cam_metrekare` decimal(10,2) DEFAULT NULL,
  `delta` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `giyotinteklifkalemler`
--

CREATE TABLE `giyotinteklifkalemler` (
  `id` int(11) NOT NULL,
  `teklif_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `olcu` varchar(50) DEFAULT NULL,
  `adet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL,
  `isim` varchar(100) DEFAULT NULL,
  `soyisim` varchar(100) DEFAULT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `parola` varchar(255) NOT NULL,
  `eposta` varchar(150) DEFAULT NULL,
  `rol_id` int(11) NOT NULL,
  `tema` enum('light','dark') DEFAULT 'light',
  `dil` varchar(5) DEFAULT 'tr',
  `durum` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `isim`, `soyisim`, `kullanici_adi`, `parola`, `eposta`, `rol_id`, `tema`, `dil`, `durum`, `created_at`, `updated_at`) VALUES
(2, 'Hakan Berke', 'İÇELLİOĞLU', 'admin', '$2y$10$ZszRTh2fatZh6xa8.WO8DuGitHTA0wi2oapITK8kSzqjzRzwoqXCi', 'hakanicellioglu@gmail.com', 1, 'light', 'tr', 1, '2025-07-04 08:03:46', '2025-07-04 08:03:46'),
(3, 'Hakan Berke', 'İÇELLİOĞLU', 'test', '$2y$10$NSUNTc547qHuMNqq/Kl9euCQZWPt3mtTjuJLCJwGzMGRd6mAyirGW', 'hakanicellioglu@gmail.com', 1, 'light', 'tr', 1, '2025-07-04 08:31:25', '2025-07-04 08:31:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `islem_turu` enum('Ekleme','Guncelleme','Silme') NOT NULL,
  `islem_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `tablo_adi` varchar(100) DEFAULT NULL,
  `sutun_adi` varchar(100) DEFAULT NULL,
  `kayit_id` int(11) DEFAULT NULL,
  `onceki_deger` text DEFAULT NULL,
  `sonraki_deger` text DEFAULT NULL,
  `aciklama` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `musteriler`
--

CREATE TABLE `musteriler` (
  `id` int(11) NOT NULL,
  `firma_adi` varchar(150) NOT NULL,
  `yetkili_adi` varchar(100) DEFAULT NULL,
  `telefon` varchar(30) DEFAULT NULL,
  `adres` varchar(255) DEFAULT NULL,
  `eposta` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roller`
--

CREATE TABLE `roller` (
  `id` int(11) NOT NULL,
  `rol_adi` varchar(50) NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `roller`
--

INSERT INTO `roller` (`id`, `rol_adi`, `aciklama`, `created_at`) VALUES
(1, 'Kullanici', 'Varsayilan kullanici rolu', '2025-07-04 08:02:43');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `surmeteklif`
--

CREATE TABLE `surmeteklif` (
  `id` int(11) NOT NULL,
  `musteri_id` int(11) DEFAULT NULL,
  `surme_sistemi` varchar(100) DEFAULT NULL,
  `genislik` decimal(10,2) DEFAULT NULL,
  `yukseklik` decimal(10,2) DEFAULT NULL,
  `sistem` varchar(100) DEFAULT NULL,
  `sistem_adedi` int(11) DEFAULT NULL,
  `ral_kodu` varchar(50) DEFAULT NULL,
  `kenet` varchar(50) DEFAULT NULL,
  `cam_ic` varchar(50) DEFAULT NULL,
  `cam_hava` varchar(50) DEFAULT NULL,
  `cam_dis` varchar(50) DEFAULT NULL,
  `temper` tinyint(1) DEFAULT 0,
  `cam_en` decimal(10,2) DEFAULT NULL,
  `cam_boy` decimal(10,2) DEFAULT NULL,
  `cam_adet` int(11) DEFAULT NULL,
  `sistem_metrekare` decimal(10,2) DEFAULT NULL,
  `aluminyum_kg` decimal(10,2) DEFAULT NULL,
  `cam_metrekare` decimal(10,2) DEFAULT NULL,
  `delta` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `surmeteklifkalemler`
--

CREATE TABLE `surmeteklifkalemler` (
  `id` int(11) NOT NULL,
  `teklif_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `olcu` varchar(50) DEFAULT NULL,
  `adet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `urunler`
--

CREATE TABLE `urunler` (
  `id` int(11) NOT NULL,
  `isim` varchar(150) NOT NULL,
  `kod` varchar(50) DEFAULT NULL,
  `olcu` varchar(50) DEFAULT NULL,
  `adet` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yetkiler`
--

CREATE TABLE `yetkiler` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `yetki_adi` varchar(100) NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `yetkiler`
--

INSERT INTO `yetkiler` (`id`, `rol_id`, `yetki_adi`, `aciklama`, `created_at`) VALUES
(1, 1, 'giris', 'Temel giris yetkisi', '2025-07-04 08:02:43');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ayarlar`
--
ALTER TABLE `ayarlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `genelteklif`
--
ALTER TABLE `genelteklif`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `genelteklifkalemler`
--
ALTER TABLE `genelteklifkalemler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teklif_id` (`teklif_id`);

--
-- Tablo için indeksler `giyotinteklif`
--
ALTER TABLE `giyotinteklif`
  ADD PRIMARY KEY (`id`),
  ADD KEY `musteri_id` (`musteri_id`);

--
-- Tablo için indeksler `giyotinteklifkalemler`
--
ALTER TABLE `giyotinteklifkalemler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teklif_id` (`teklif_id`),
  ADD KEY `urun_id` (`urun_id`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Tablo için indeksler `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kullanici_id` (`kullanici_id`);

--
-- Tablo için indeksler `musteriler`
--
ALTER TABLE `musteriler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `roller`
--
ALTER TABLE `roller`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `surmeteklif`
--
ALTER TABLE `surmeteklif`
  ADD PRIMARY KEY (`id`),
  ADD KEY `musteri_id` (`musteri_id`);

--
-- Tablo için indeksler `surmeteklifkalemler`
--
ALTER TABLE `surmeteklifkalemler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teklif_id` (`teklif_id`),
  ADD KEY `urun_id` (`urun_id`);

--
-- Tablo için indeksler `urunler`
--
ALTER TABLE `urunler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `yetkiler`
--
ALTER TABLE `yetkiler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ayarlar`
--
ALTER TABLE `ayarlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `genelteklif`
--
ALTER TABLE `genelteklif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `genelteklifkalemler`
--
ALTER TABLE `genelteklifkalemler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `giyotinteklif`
--
ALTER TABLE `giyotinteklif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `giyotinteklifkalemler`
--
ALTER TABLE `giyotinteklifkalemler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `log`
--
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `musteriler`
--
ALTER TABLE `musteriler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `roller`
--
ALTER TABLE `roller`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `surmeteklif`
--
ALTER TABLE `surmeteklif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `surmeteklifkalemler`
--
ALTER TABLE `surmeteklifkalemler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `urunler`
--
ALTER TABLE `urunler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `yetkiler`
--
ALTER TABLE `yetkiler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `genelteklifkalemler`
--
ALTER TABLE `genelteklifkalemler`
  ADD CONSTRAINT `genelteklifkalemler_ibfk_1` FOREIGN KEY (`teklif_id`) REFERENCES `genelteklif` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `giyotinteklif`
--
ALTER TABLE `giyotinteklif`
  ADD CONSTRAINT `giyotinteklif_ibfk_1` FOREIGN KEY (`musteri_id`) REFERENCES `musteriler` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `giyotinteklifkalemler`
--
ALTER TABLE `giyotinteklifkalemler`
  ADD CONSTRAINT `giyotinteklifkalemler_ibfk_1` FOREIGN KEY (`teklif_id`) REFERENCES `giyotinteklif` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `giyotinteklifkalemler_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`);

--
-- Tablo kısıtlamaları `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD CONSTRAINT `kullanicilar_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roller` (`id`);

--
-- Tablo kısıtlamaları `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `log_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `surmeteklif`
--
ALTER TABLE `surmeteklif`
  ADD CONSTRAINT `surmeteklif_ibfk_1` FOREIGN KEY (`musteri_id`) REFERENCES `musteriler` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `surmeteklifkalemler`
--
ALTER TABLE `surmeteklifkalemler`
  ADD CONSTRAINT `surmeteklifkalemler_ibfk_1` FOREIGN KEY (`teklif_id`) REFERENCES `surmeteklif` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surmeteklifkalemler_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`);

--
-- Tablo kısıtlamaları `yetkiler`
--
ALTER TABLE `yetkiler`
  ADD CONSTRAINT `yetkiler_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roller` (`id`) ON DELETE CASCADE;
-- BEGIN custom tables
-- --------------------------------------------------------
--
-- Tablo için tablo yapısı `firmalar`
--
CREATE TABLE `firmalar` (
  `id` int(11) NOT NULL,
  `firma_adi` varchar(150) NOT NULL,
  `eposta` varchar(150) DEFAULT NULL,
  `telefon` varchar(30) DEFAULT NULL,
  `adres` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Tablo için tablo yapısı `yetkililer`
--
CREATE TABLE `yetkililer` (
  `id` int(11) NOT NULL,
  `firma_id` int(11) DEFAULT NULL,
  `isim` varchar(100) NOT NULL,
  `soyisim` varchar(100) DEFAULT NULL,
  `hitap` enum('bey','hanımefendi') DEFAULT 'bey',
  `eposta` varchar(150) DEFAULT NULL,
  `telefon` varchar(30) DEFAULT NULL,
  `adres` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo için indeksler `firmalar`
--
ALTER TABLE `firmalar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `yetkililer`
--
ALTER TABLE `yetkililer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `firma_id` (`firma_id`);

--
-- Tablo için AUTO_INCREMENT değeri `firmalar`
--
ALTER TABLE `firmalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `yetkililer`
--
ALTER TABLE `yetkililer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo kısıtlamaları `yetkililer`
--
ALTER TABLE `yetkililer`
  ADD CONSTRAINT `yetkililer_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
