# Depo

Küçük işletmelerin ürün kataloğunu, stok hareketlerini ve satış siparişlerini yönetmesi için Laravel uygulaması.

## Özellikler

- İşletme hesabı ve işletmeye göre ayrılmış ürün verileri
- SKU, fiyat, birim maliyet, stok miktarı ve yeniden sipariş eşiği
- Açılış stoğu ve elle stok giriş/çıkışı kaydı
- Birden fazla üründen sipariş oluşturma
- Sipariş açıldığında stoğu ayırma, iptal edilince stoğu geri ekleme
- Düşük stok listesi ve stok hareket geçmişi
- Stok miktarının sıfırın altına düşmesini engelleme

## Gereksinimler

- PHP 8.2 veya üzeri
- Composer
- SQLite PDO eklentisi

## Yerel kurulum

Proje klasöründe PowerShell ile:

```powershell
composer install
Copy-Item .env.example .env
./start-local.ps1
```

Başlangıç betiği SQLite eklentilerini açar, uygulama anahtarını ve yerel veritabanını hazırlar, migrasyonları çalıştırır ve geliştirme sunucusunu başlatır. Uygulamayı `http://127.0.0.1:8000` adresinde aç. **İşletme hesabı aç** sayfasından hesap oluşturduktan sonra ürünü ve başlangıç stokunu ekleyebilirsin.

## Stok tutarlılığı

Sipariş oluşturma sırasında stok, veritabanı işlemi içinde tekrar kontrol edilir ve ayrılır. Sipariş iptali aynı işlem içinde stoğu geri ekleyip hareket kaydı oluşturur. Eşzamanlı stok güncellemelerinde satır kilitlemesinden yararlanmak için canlı ortamda MySQL veya PostgreSQL kullanılması önerilir. SQLite yerel geliştirme içindir.
