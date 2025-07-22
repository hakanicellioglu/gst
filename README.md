# GST Teklif Yönetim Sistemi

Bu depo, PHP ile geliştirilmiş bir teklif yönetim uygulamasıdır. Kullanıcılar müşteri kayıtlarını tutabilir, ürünleri yönetebilir ve PDF formatında teklifler oluşturabilir. Ürün görselleri veritabanındaki BLOB alanlarında saklanır ve sayfalarda base64 kodlanmış veri URL'leriyle görüntülenir.

## Kurulum

1. `teklif.sql` dosyasını kullanarak MySQL veritabanınızı oluşturun.
2. `config.php` içindeki veritabanı bağlantı ayarlarını kendi ortamınıza göre düzenleyin.
3. PHP 8 ve Composer yüklü bir sunucuda projeyi çalıştırabilirsiniz. Basit bir geliştirme ortamı için komut satırında şu komutu kullanabilirsiniz:

```bash
php -S localhost:8000
```

Ardından tarayıcınızdan `http://localhost:8000` adresini ziyaret edin.

## Özellikler

- Kullanıcı oturum yönetimi ve yetkilendirme
- Temayı açık ve koyu renk seçenekleriyle değiştirebilme
- Ürün görsellerini veritabanında saklama
- Teklifleri PDF olarak dışa aktarma
- Kullanıcı işlemlerini kaydeden denetim (audit) yapısı
- Uygulama ve kullanıcı bazında ayarlanabilen genel ayar sistemi

## Örnek

`image_upload_example.php` dosyasında temel resim yükleme ve BLOB olarak saklama örneği bulunmaktadır.

## Lisans

Bu proje MIT lisansı ile lisanslanmıştır. Ayrıntılar için `LİCENSE.md` dosyasına bakabilirsiniz.
