# WarNight Shell (warnshell.php)

## Genel Bakış

WarNight Shell, WarNight Hack Team üyeleri için özel olarak tasarlanmış, PHP ile yazılmış gelişmiş bir web kabuğudur. Bu araç, dosya yönetimi, komut çalıştırma, veritabanı etkileşimi ve çeşitli siber güvenlik araçları dahil olmak üzere geniş bir yelpazede işlevsellik sunar.

![WarNight Shell Arayüzü](https://cdn.discordapp.com/attachments/1400185555173769408/1401334575916912721/image.png?ex=688fe606&is=688e9486&hm=0c51650ac65a674fa48fdaba6a75cbd824a51384db7a39617cc41fc4abbebece&)

## Özellikler

* **Güvenli Erişim:** MD5 hash ile korunan parola tabanlı kimlik doğrulama sistemi bulunmaktadır. Parola `warnight` olarak ayarlanmıştır.
* **Dosya Yöneticisi:** Sunucudaki dosyaları ve dizinleri listeleme, indirme, silme, yeniden adlandırma, düzenleme ve zip arşivi oluşturma gibi temel dosya yönetimi işlemleri sunar.
* **Komut Çalıştırma:** `system`, `shell_exec`, `passthru` ve `exec` gibi PHP fonksiyonlarını kullanarak sunucuda komutlar çalıştırma imkanı tanır.
* **Veritabanı İstemcisi:** `wp-config.php`, `configuration.php`, `.env` gibi yaygın yapılandırma dosyalarını tarayarak veritabanı kimlik bilgilerini bulabilir ve SQL sorguları çalıştırmaya olanak tanır.
* **Gelişmiş Araçlar:**
    * **Metin Düzenleyici:** Dosyaları doğrudan tarayıcı üzerinden düzenleyebilir.
    * **URL'den Yükle:** Uzak bir URL'deki dosyayı sunucuya indirebilir.
    * **PHP Değerlendirici:** PHP kodunu doğrudan çalıştırabilir.
    * **Mail Gönderici:** Sunucu üzerinden e-posta gönderebilir.
    * **Port Tarayıcı:** Belirlenen bir IP adresindeki açık portları tarayabilir.
    * **Web Tarayıcı (Proxy):** Sunucuyu bir proxy gibi kullanarak web sayfalarının içeriğini çekebilir.
    * **Hash Üretici & Base64 Çevirici:** Metinleri farklı algoritmalarla şifreleyebilir veya Base64 formatına dönüştürüp çözebilir.
    * **Discord Spammer:** Belirtilen bir Discord webhook URL'sine otomatik mesajlar gönderebilir.
* **Anonimlik:** HTTP başlıklarını (X-Forwarded-For, X-Real-IP vb.) rastgele oluşturarak anonimliği destekler.

## Kullanım

1.  `warnshell.php` dosyasını hedef web sunucusuna yükleyin.
2.  Tarayıcınızdan `http://hedefsite.com/warnshell.php` adresine gidin.
3.  Giriş sayfasında parola olarak `warnight` girin.
4.  Giriş yaptıktan sonra, sol taraftaki menüyü kullanarak farklı işlevlere erişebilirsiniz.

## Not

Bu araç, sızma testleri ve yetkilendirilmiş güvenlik denetimleri gibi yasal amaçlar için tasarlanmıştır. Bu aracı yetkisiz erişim sağlamak veya kötü niyetli faaliyetlerde bulunmak için kullanmak yasa dışıdır.

## ⭐ Destek Olun

Projeyi faydalı bulduysanız lütfen bir ⭐ bırakmayı unutmayın.  
Bu, geliştirmeye devam etmemiz için önemli bir motivasyon kaynağıdır.

